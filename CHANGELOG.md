# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [3.0.0] - 2026-09-08 — BIGBANG ITS build

Active maintenance transferred to **BIGBANG ITS** (https://its.ae). All premium/licensing code paths were removed — every feature, including TLD Pricing Sync, is free.

### Added
- Built-in **TLD Pricing Sync** (`GetTldPricing`) fed by a local `tld_prices.json` feed, with a configurable `PriceFeedPath` module setting and Activity Log staleness warnings (45 days).
- **Async operation handling**: HTTP 202 responses are polled via `/async-operations/{id}` (90s budget), so registrations, renewals and transfers report their real outcome instead of "accepted".

### Fixed
- **Availability checks** now parse the live API response shape (`domains[].result` with present-but-empty `premiumPricing`); the previous parser expected `items[].isAvailable` and always produced empty results.
- **ID Protection status** is read from the live `privacyProtection.level` field (previously always reported "off").
- `SaveContactDetails` now uses `PUT /contacts` (was `POST`).
- **Premium domains** are marked reserved instead of being offered at standard TLD prices.

### Security
- API credentials are no longer written to the Module Log by error-path logging (`_spaceship_safe_params`).

### Removed
- All Premium/JobFew/licensing bridges, upsell banners, and any loading of third-party code.


## [2.2.3] - 2026-03-16

### Changed
- **Product Transfer**: This module has been officially transferred to **Topeta**. License activation is now processed via [my.topeta.com](https://my.topeta.com).

### Fixed
- **Workflow Obfuscation Pathing**: Fixed a critical bug in `release.yml` that caused the obfuscated output to be silently ignored during packaging.
- **Free Version Upgrade Link**: Fixed an outdated link pointing to the old decommissioned store (product 402 → 424).
- **Licensing Resilience**: The module optimally relies on server-side redirects at `my.jobfew.com` (0 code changes required for existing installations).
- **Support Ecosystem**: Updated support email (`support@topeta.com`) and updated internal store references.

## [2.2.2] - 2026-03-03

### Fixed
- **API Endpoint Accuracy**: Reverted the domain registration endpoint back to the explicitly required `/v1/domains/{domain}` to comply with Spaceship proxy architecture.
- **Contact Data Formatting**: Sanitized all outbound contact details (trimmed spaces, filtered missing optional fields) to stop `422 Unprocessable Entity` formatting errors, and implemented rigorous fallbacks for required missing fields (like phone numbers).
- **Privacy Protection (WHOIS) Spec**: Fixed a missing data error by actively dispatching the explicitly required `privacyProtection.level` (evaluating to 'high' or 'public') and `privacyProtection.userConsent` boolean configurations.
- **Improved HTTP Methods**: Altered the Contact Create API method from `POST` to the strictly enforced `PUT` endpoint.

## [2.2.1] - 2026-02-23

### Fixed
- **PHP 8.4 & Namespace Compatibility**: Resolved critical "Class Not Found" errors by standardizing on fully qualified `\WHMCS\Database\Capsule`.
- **Surgical Obfuscation**: Implemented a "Safe-Logic" boundary in the release pipeline. Interface files (`spaceship.php`, `Config.php`) are now excluded from scrambling to ensure WHMCS hooks function correctly.
- **PRO Badge & Status UI**: Restored missing licensing UI components in the module settings page.
- **TLD Sync Resilience**: Fixed variable scrambling that prevented the pricing feed from being correctly indexed.

### Added
- **Production-Build Standards**: Integrated comprehensive release scrubbing to remove development artifacts from the distributed ZIP.
- **Framework Hardening**: Full compatibility verified with JobFew Helper v1.0.0.

## [2.2.0] - 2026-02-21

### Changed
- **Framework Migration (Pro Version)**: Migrated licensing and bridge logic to the centralized **JobFew Helper** framework for improved stability and management.
- **TLD Sync Fix (Pro Version)**: Improved compatibility for TLD synchronization across different WHMCS versions.
- **Live-Only Pricing Mandate (Pro Version)**: Fully transitioned to a live-relay pricing model, ensuring users always see the most accurate TLD costs.

## [2.1.1] - 2026-02-20

### Added
- **Persistent Database Cache**: Replaced in-memory caching with a database-backed system.
- **Auto-Initialization**: Added `spaceship_activate` hook for cache table setup.

### Fixed
- **Privacy Toggle (ID Protection)**: Implemented official `privacyLevel` and `userConsent` spec.
- **DNS Record Management**: Standardized on `PUT` requests with `items` wrapper.

## [2.0.0] - 2026-02-19

### Added
- **Smart Rate-Limit Protection**: Initial global in-memory caching engine.
- **Account Balance Display**: Support for viewing wallet balance in WHMCS settings.
