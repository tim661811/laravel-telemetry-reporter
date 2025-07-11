### 📝 CHANGELOG

## 0.4 (2025-07-12)

### Added

* `#[TelemetryResponseHandler]` attribute for designating methods that handle **incoming telemetry response data** from the server.

## 0.3 (2025-07-12)

### Added

* Support for including a bearer token in the `Authorization` header when sending telemetry, enabling authenticated transport.
* Automatic fetching and caching of bearer tokens from a configurable `auth_token_url`, with support for fallback to a static token if configured.
* Telemetry signing support for verifying payload authenticity using HMAC-SHA256:
    * Controlled via `telemetry-reporter.signing.enabled`
    * Uses a configurable signing key and header name
    * Signature is based on the raw JSON telemetry payload

## 0.2 (2025-07-10)

### Added

* New Artisan command `telemetry:list` to display all registered telemetry data collectors with their keys, classes, methods, and intervals for easier inspection and debugging.
* Configuration option `verbose_logging` to enable verbose output of telemetry payloads before sending, helping diagnose issues and verify data integrity.
* Added `telemetry:send` as an alias for the existing `telemetry:report` Artisan command for improved usability.

## 0.1.1 (2025-07-10)

### Changed

* Allowed `composer/class-map-generator` version `^1.1` in addition to `^1.6` for better compatibility with existing Laravel projects.
* Improve the readme to correct the config publish command so it actually works as expected.
* Changed the scheduler interval for the telemetry report command from 1 minute to 15 minutes to reduce overhead.

### Removed

* Removed unused configuration options for cleaner config files.

## 0.1 (2025-07-09)

### Added

- `#[TelemetryData]` attribute for tagging methods with telemetry metadata
- Automatic classmap-based discovery of telemetry methods
- Artisan command `telemetry:report` to collect & send telemetry
- Automatically scheduled telemetry report command using the service provider
- Config file for enabling, interval control, cache store, and server URL
- Pest tests for attribute and reporting logic
