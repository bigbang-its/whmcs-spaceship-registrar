# Spaceship Registrar Module for WHMCS

**A free, production-hardened WHMCS registrar module for [Spaceship.com](https://www.spaceship.com) — maintained by [BIGBANG ITS](https://its.ae) (Big Bang Information Technology Solutions), Sharjah & Dubai, UAE.**

Automate the full domain lifecycle over the Spaceship Public API: registration, renewal, transfer, nameservers, WHOIS contacts, registrar lock, EPP codes, DNS records, ID protection, child nameservers, and **TLD pricing sync** — with **no paid add-ons, no licence checks, and no third-party code ever loaded**.

> This is an unofficial module. It is not affiliated with or endorsed by Spaceship. Everything here runs against the documented public API at [docs.spaceship.dev](https://docs.spaceship.dev).

---

## Features

| Capability | Notes |
|---|---|
| Register / Renew / Transfer | Async-aware: HTTP 202 operations are polled to completion, so WHMCS records the **real** outcome, not just "request accepted" |
| Availability search | Parses the live `POST /domains/available` response shape, verified against production; unknown results default to *registered* (safe) |
| Premium domains | Marked **reserved** instead of being sold at standard TLD prices |
| Nameservers | Get/save, plus child (personal) nameserver register/modify/delete |
| WHOIS contacts | Get/save with contact deduplication (`PUT /contacts`) |
| Registrar lock | EPP-status aware (`clientTransferProhibited` fallback) |
| ID Protection | Reads the live `privacyProtection.level` field |
| EPP / auth codes | `GET /domains/{domain}/transfer/auth-code` |
| DNS records | Get/save for domains on Spaceship nameservers |
| Domain sync | Expiry + lifecycle status mapping for the WHMCS daily cron |
| **TLD Pricing Sync** | Built-in and free — see below |
| Rate-limit protection | Persistent database cache tuned to Spaceship's 5-requests-per-300s single-domain limits |
| Hardened logging | API credentials are masked everywhere, **including error paths** |

## Requirements

- WHMCS 8.x or 9.x
- PHP 7.4+ (8.1+ recommended; tested on 8.3)
- PHP cURL extension
- A Spaceship API key & secret from the [API Manager](https://www.spaceship.com/application/api-manager/) with scopes: `domains:read`, `domains:write`, `domains:transfer`, `domains:billing`, `contacts:read`, `contacts:write`, `dnsrecords:read`, `dnsrecords:write`, `asyncoperations:read`

## Installation

1. Copy this repository into `<whmcs_root>/modules/registrars/spaceship/` (the folder must be named `spaceship`).
2. In the WHMCS admin area open **Configuration → Apps & Integrations → Domain Registrars**, activate **Spaceship**, and enter your API key and secret.
3. Assign TLDs to Spaceship under **Configuration → Domain Pricing** (auto-registration column).
4. Optional: import cost prices via TLD Pricing Sync (below).

## Configuration options

| Option | Purpose |
|---|---|
| API Key / API Secret | From the Spaceship API Manager; stored encrypted by WHMCS |
| Test Mode | Reserved — Spaceship currently offers no public sandbox, leave off |
| TLD Price Feed Path | Optional absolute path to your `tld_prices.json` feed (recommended: outside the web root). Empty = `tld_prices.json` inside the module folder |

## TLD Pricing Sync — how it works

Spaceship's public API does not expose a TLD price list, and their website cannot be fetched server-side. This module therefore syncs prices from a **local JSON feed that you maintain** — a deliberate design choice: your WHMCS never depends on an undocumented endpoint or a third-party licence server, and every price that enters your billing system is one you have reviewed.

Create `tld_prices.json` (start from [`tld_prices.example.json`](tld_prices.example.json)):

```json
{
  "updated": "2026-09-08",
  "currency": "USD",
  "tlds": {
    ".com": {"register": 9.08, "renew": 10.18, "transfer": 9.68, "minYears": 1, "maxYears": 10},
    ".ai":  {"register": 79.98, "renew": 79.98, "transfer": 79.98, "minYears": 2, "maxYears": 10}
  }
}
```

- `currency` must exist in your WHMCS **Setup → Currencies**.
- Take the numbers from Spaceship's public per-TLD pages (e.g. `spaceship.com/domains/gtld/com/`) — register prices there may be first-year promotions, which is genuinely what your account pays.
- Then run **Configuration → Domain Pricing → registrar price sync** in WHMCS to review costs and apply your own margins.
- If the feed is older than 45 days the module writes a reminder to the WHMCS Activity Log (and keeps serving it).

## Security notes

- Credentials are stored encrypted by WHMCS and masked in every Module Log entry, including error paths.
- Point `TLD Price Feed Path` outside the web root and keep the feed file `600`.
- The module talks to exactly one host: `https://spaceship.dev/api/v1`. Read the source — it's short on purpose.

## Free installation support

BIGBANG ITS offers **free installation and configuration help** to any hosting provider or reseller using this module — see [SUPPORT.md](SUPPORT.md) for contacts. For bugs and feature requests please open a GitHub issue.

## Credits & license

Originally based on the MIT-licensed `whmcs-spaceship-registrar` by Tanvir Israq; independently audited line by line, substantially reworked, and maintained since v3.0.0 by **BIGBANG ITS** ([its.ae](https://its.ae)). See [CHANGELOG.md](CHANGELOG.md) for what changed and why.

Released under the [MIT License](LICENSE).
