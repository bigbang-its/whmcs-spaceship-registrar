<?php

namespace Spaceship;

class ApiClient
{
    private $apiKey;
    private $apiSecret;
    private $testMode;
    private $baseUrl = 'https://spaceship.dev/api/v1';

    private $lastRequest;
    private $lastResponse;

    /**
     * ApiClient constructor.
     *
     * @param string $apiKey
     * @param string $apiSecret
     * @param bool $testMode
     */
    public function __construct($apiKey, $apiSecret, $testMode = false)
    {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->testMode = (bool) $testMode;

        if ($this->testMode) {
            $this->baseUrl = 'https://sandbox.spaceship.dev/api/v1';
        }
    }

    /**
     * Send a request to the Spaceship API
     *
     * @param string $method GET, POST, PUT, DELETE
     * @param string $endpoint API endpoint (e.g., /domains)
     * @param array $params Request parameters
     * @param string $action Optional action name for WHMCS logging
     * @return array API response
     * @throws \Exception
     */
    public function request($method, $endpoint, $params = [], $action = '')
    {
        $url = $this->baseUrl . $endpoint;
        $ch = \curl_init();

        $headers = [
            'X-API-Key: ' . $this->apiKey,
            'X-API-Secret: ' . $this->apiSecret,
            'Content-Type: application/json',
            'Accept: application/json',
            'User-Agent: WHMCS-Spaceship-Module/2.2.2',
        ];

        $requestBody = '';
        if ($method === 'GET' && !empty($params)) {
            $url .= '?' . \http_build_query($params);
        } elseif (\in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $requestBody = \json_encode($params);
            \curl_setopt($ch, \CURLOPT_POSTFIELDS, $requestBody);
        }

        $this->lastRequest = [
            'url' => $url,
            'method' => $method,
            'headers' => $headers,
            'body' => $params
        ];

        \curl_setopt($ch, \CURLOPT_URL, $url);
        \curl_setopt($ch, \CURLOPT_RETURNTRANSFER, true);
        \curl_setopt($ch, \CURLOPT_CUSTOMREQUEST, $method);
        \curl_setopt($ch, \CURLOPT_HTTPHEADER, $headers);
        \curl_setopt($ch, \CURLOPT_SSL_VERIFYPEER, true);
        \curl_setopt($ch, \CURLOPT_SSL_VERIFYHOST, 2);
        \curl_setopt($ch, \CURLOPT_TIMEOUT, 30);

        // BB patch: capture response headers — long-running operations return
        // HTTP 202 with the operation id in spaceship-async-operationid.
        $responseHeaders = [];
        \curl_setopt($ch, \CURLOPT_HEADERFUNCTION, function ($ch, $header) use (&$responseHeaders) {
            $parts = \explode(':', $header, 2);
            if (\count($parts) === 2) {
                $responseHeaders[\strtolower(\trim($parts[0]))] = \trim($parts[1]);
            }
            return \strlen($header);
        });

        $response = \curl_exec($ch);
        $httpCode = \curl_getinfo($ch, \CURLINFO_HTTP_CODE);
        $error = \curl_error($ch);
        \curl_close($ch);

        $this->lastResponse = [
            'httpCode' => $httpCode,
            'body' => $response,
            'error' => $error
        ];

        // Log to WHMCS Module Log if function exists
        if (\function_exists('logModuleCall') && !empty($action)) {
            $processedResponse = \json_decode($response, true);

            // If response is empty but we have a success code, provide a helpful message
            if (empty($response) && $httpCode >= 200 && $httpCode < 300) {
                $processedResponse = ['status' => 'success', 'http_code' => $httpCode, 'message' => 'Empty response (typical for 204 No Content)'];
            } elseif (empty($response)) {
                $processedResponse = ['status' => 'error', 'http_code' => $httpCode, 'message' => 'Empty response from server'];
            }

            \logModuleCall(
                'spaceship',
                $action,
                $this->lastRequest,
                $response, // raw
                $processedResponse, // processed
                [$this->apiKey, $this->apiSecret] // Sensitive data to mask
            );
        }

        if ($error) {
            throw new \Exception("Spaceship API Curl Error: " . $error);
        }

        $result = \json_decode($response, true);

        if ($httpCode >= 400) {
            $message = isset($result['detail']) ? $result['detail'] : 'Unknown API Error';
            throw new \Exception("Spaceship API Error ($httpCode): " . $message);
        }

        // BB patch: a 202 means the operation was only accepted, not completed.
        // Poll the async-operations endpoint so callers get the real outcome.
        if ($httpCode === 202 && !empty($responseHeaders['spaceship-async-operationid'])) {
            return $this->waitForAsyncOperation($responseHeaders['spaceship-async-operationid'], $action, $result);
        }

        return $result;
    }

    /**
     * Poll an async operation until it reaches a terminal state.
     *
     * @param string $operationId
     * @param string $action Action name for logging
     * @param mixed $initialResult Body of the original 202 response
     * @return array
     * @throws \Exception when the operation fails or stays pending too long
     */
    private function waitForAsyncOperation($operationId, $action, $initialResult)
    {
        $deadline = \time() + 90;
        do {
            \sleep(3);
            $op = $this->request('GET', '/async-operations/' . $operationId, [], $action ? $action . ':Poll' : '');
            $status = isset($op['status']) ? \strtolower($op['status']) : '';
            if ($status === 'failed') {
                $detail = isset($op['detail']) ? $op['detail'] : \json_encode($op);
                throw new \Exception("Spaceship async operation failed: " . $detail);
            }
            if ($status !== '' && $status !== 'pending' && $status !== 'processing') {
                return \is_array($op) ? $op : (array) $initialResult;
            }
        } while (\time() < $deadline);

        throw new \Exception(
            "Spaceship async operation {$operationId} is still pending after 90s. "
            . "Check GET /async-operations/{$operationId} before retrying — the operation may still complete."
        );
    }

    public function getLastRequest()
    {
        return $this->lastRequest;
    }
    public function getLastResponse()
    {
        return $this->lastResponse;
    }
}
