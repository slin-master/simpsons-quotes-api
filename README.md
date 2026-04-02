# Simpsons Quotes API

Laravel 13 / PHP 8.5 API for authenticated Simpsons quote retrieval with per-user quote history.

## Features

- Laravel 13 with token-based API authentication via Sanctum
- Protected endpoint that fetches a random Simpsons quote and persists it per user
- Retention logic that keeps only the latest five quotes per user
- SQLite-backed persistence for simple local and Docker-based execution
- Thin controllers with application logic delegated to dedicated services
- JSON responses serialized through Laravel API Resources
- Feature and unit tests covering authentication, provider behavior, persistence, and retention
- Static analysis via PHPStan/Larastan
- CI-oriented `Makefile` and `Jenkinsfile`
- OpenAPI description available at `public/docs/openapi.yaml`
- Bruno collection for manual API testing available under `bruno/simpsons-quotes-api`

## Architecture

The application uses a resilient quote provider setup:

- Primary source: `thesimpsonsapi.com`
- Fallback source: local mock dataset bundled in the repository

This keeps the project reproducible even if third-party APIs become unavailable.

The current implementation fetches the quote during the request flow. For a production-oriented design, I would decouple this concern and import or synchronize quotes ahead of time, then serve random quotes from the local database. That would reduce runtime dependency on a third-party API and improve reliability, latency, and operational control.

Authentication is currently implemented with Laravel Sanctum personal access tokens. This satisfies the bearer-token requirement of the task, but it is not a JWT-based implementation. If strict JWT usage were required, the authentication layer would need to be switched to a dedicated JWT solution.

Implementation overview:

- `LoginService` handles credential validation and token issuance
- `UserQuoteService` handles quote retrieval, persistence, and five-item retention
- API Resources define the outward JSON contract independently of controller code
- No policies are required for the current scope because quote access is limited to the authenticated user via `auth:sanctum`

## Demo Credentials

The database seeder creates two local demo users so user-specific quote histories can be verified easily:

- User 1: `springfield-demo` / `Springfield123!`
- User 2: `springfield-demo-2` / `Springfield123!`

## Run With Docker

```bash
docker compose up --build
```

The API is available locally at [http://localhost:8080](http://localhost:8080).
The public deployment is available at [https://simpsons-quotes-api.friedrichs-it.de](https://simpsons-quotes-api.friedrichs-it.de).

Local development uses `compose.yaml` plus `compose.override.yaml`. The override file is applied automatically by Docker Compose and mounts the repository into the container for fast iteration.

Useful endpoints:

- `POST /api/auth/login`
- `POST /api/auth/logout`
- `POST /api/quotes`
- `GET /docs/openapi.yaml`

For live deployment, `compose.yaml` exposes the application on host port `8087`, which is consumed by the HAProxy frontend for `simpsons-quotes-api.friedrichs-it.de`.

## Bruno Collection

A Bruno collection is included under `bruno/simpsons-quotes-api`.

Suggested usage:

- Open the folder `bruno/simpsons-quotes-api` as a Bruno collection
- Select the `local` environment
- Run `Auth/Login User 1` or `Auth/Login User 2`
- Then use the matching `Quotes/Create Quote User 1` or `Quotes/Create Quote User 2` request
- Use `Auth/Logout User 1` or `Auth/Logout User 2` to invalidate the respective token

## Example Flow

Login:

```bash
curl --request POST 'http://localhost:8080/api/auth/login' \
  --header 'Content-Type: application/json' \
  --data '{
    "username": "springfield-demo",
    "password": "Springfield123!"
  }'
```

Fetch and persist a quote:

```bash
curl --request POST 'http://localhost:8080/api/quotes' \
  --header 'Authorization: Bearer <TOKEN>' \
  --header 'Content-Type: application/json'
```

## Local Development

Install dependencies:

```bash
docker run --rm -u $(id -u):$(id -g) -v "$PWD":/app -w /app composer:2 composer install
```

Run migrations and seeders:

```bash
docker run --rm -u $(id -u):$(id -g) -v "$PWD":/app -w /app composer:2 php artisan migrate:fresh --seed
```

Run tests:

```bash
make test
```
or
```bash
docker run --rm -u $(id -u):$(id -g) -v "$PWD":/app -w /app composer:2 php artisan test
```

Run tests with code coverage:

```bash
make test-coverage
```

Run static analysis:

```bash
make phpstan
```

Run the local CI checks:

```bash
make ci
```

The generated test and coverage artifacts are written to `storage/test-reports/`.

## Notes

- The original Glitch-hosted Simpsons Quotes API is no longer available. The implementation therefore uses a stable public Simpsons data source with a local fallback dataset.
- The same API contract is available publicly via `https://simpsons-quotes-api.friedrichs-it.de`.
- The project includes automated tests, code coverage reporting, and a clean PHPStan/Larastan run.
