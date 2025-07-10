## 🧭 **Pre-1.0 Roadmap for `laravel-telemetry-reporter`**

### 🔜 Planned Pre-1.0 Milestones

> **Note:** The roadmap is a living document and plans may change as the project evolves.

---

### ✅ `v0.1` – Minimal Viable Package (MVP)

* Implement basic telemetry collection using PHP attributes to annotate service methods.
* Support configurable intervals for data collection with default interval fallback.
* Schedule automatic telemetry reporting integrated with Laravel's scheduler.
* Send collected telemetry data grouped by application host to a configurable central server endpoint.
* Provide a basic configuration file for essential settings like server URL, cache store, and default intervals.

---

### 🧪 `v0.2` – Developer Tools & Debugging

**Goal:** Improve developer experience by providing useful tools for inspecting and managing telemetry data.

* Add Artisan command `telemetry:list` to display all registered telemetry keys and their intervals. This helps developers verify which telemetry collectors are active.
* Introduce configuration options to enable verbose logging of telemetry payloads before sending. This helps diagnose issues and confirm data integrity.

---

### 🔐 `v0.3` – Secure Transport Layer

**Goal:** Ensure telemetry data is sent securely.

* Add support for an authentication token (TELEMETRY_AUTH_TOKEN) sent with each telemetry HTTP request, configurable via .env.
* Allow configuring additional HTTP headers for enhanced security or integration with custom receivers.
* Provide clear documentation and examples on securing telemetry data in transit.
* Plan for future support of encryption or signing payloads to improve data integrity and privacy.

---

### 🌐 `v0.4` – Remote Feature Flag Sync

**Goal:** Let the central server send back active feature flags.

* Sync feature flags on telemetry response
* Optional command: `telemetry:sync-flags`

---

### 🚀 `v1.0` – First Stable Release

**Goal:** Solidified features, full test coverage, clear upgrade path.

* All major telemetry types stabilized
* Basic receiver implementation published (as example or separate package)
* Tests + CI setup complete
* Tagged as `v1.0.0` and optionally published to Packagist
