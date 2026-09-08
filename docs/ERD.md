# ERD & Data Dictionary

Placeholder for the System Architect / BA team's entity-relationship diagram and
data dictionary (Sprint 1 deliverable per `docs/ARCHITECTURE.md`).

Current schema (from the Laravel scaffold):

- `users` — id, name, email, email_verified_at, password, remember_token, timestamps
- `personal_access_tokens` — Sanctum API tokens (tokenable polymorphic)
- `cache`, `cache_locks` — cache table driver (unused while `CACHE_STORE=redis`)
- `jobs`, `job_batches`, `failed_jobs` — queue tables (unused while `QUEUE_CONNECTION=redis`, kept for reference)

Add domain entities (e.g. products, orders, content types) and their
relationships here as they're designed, along with the ERD diagram itself.
