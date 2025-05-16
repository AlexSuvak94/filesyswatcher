## Getting Started

1. Clone the repo
2. Run: composer install (in the project root folder)

PLEASE NOTE:

This repository was built as part of a technical assignment. The main goal was to demonstrate practical knowledge of Laravel, file operations, CLI commands, and external integrations. Some implementation choices were made for simplicity, clarity, and speed of delivery, and are not intended for production use as-is.

Known Limitations (Intentionally Accepted for This Test):
Infinite Loop Without Signal Handling
The watcher runs in a while (true) loop with no graceful shutdown support. In a production scenario, this would include signal handling (e.g., pcntl_signal) or use a daemon manager.

Polling-Based File Watcher
The watcher uses polling via sleep(5), which is not as efficient as event-driven systems (like inotify or Node's fs.watch). This was chosen for portability and simplicity in PHP.

Direct File Modifications
For test purposes, images and text files are modified in-place. Image metadata markers and appended strings (like bacon ipsum) are inserted directly, which might corrupt real-world binary formats — in production, a safer tagging/metadata approach would be preferred.

External APIs Used Without Caching
The use of open APIs (meme generation, bacon ipsum, webhook posting) is done in real-time with no retry, caching, or fallback logic. This could introduce delays or failures if APIs are slow/unavailable.

Hardcoded Configuration
Endpoints, API URLs, and paths are hardcoded for brevity. In real-world use, they would be abstracted into config files or .env variables.

No Unit Tests
While the logic is modular and testable, no formal unit tests are included due to time constraints and the project's scope.

This README and implementation reflect my awareness of real-world concerns and tradeoffs. I intentionally opted for clarity and coverage over edge-case robustness, given the test-oriented context of this task.