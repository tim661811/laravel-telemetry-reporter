### 📝 CHANGELOG

### 📝 CHANGELOG

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
