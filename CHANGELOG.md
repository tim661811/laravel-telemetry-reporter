### 📝 CHANGELOG

## 0.1 (2025-07-09)

### Added

- `#[TelemetryData]` attribute for tagging methods with telemetry metadata
- Automatic classmap-based discovery of telemetry methods
- Artisan command `telemetry:report` to collect & send telemetry
- Automatically scheduled telemetry report command using the service provider
- Config file for enabling, interval control, cache store, and server URL
- Pest tests for attribute and reporting logic
