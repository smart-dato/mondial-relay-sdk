# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Package Overview

`smart-dato/mondial-relay-sdk` — a Laravel SDK for the Mondial Relay shipping APIs. **API V1 (SOAP)** is implemented: pickup point search and parcel tracking. **API V2 (Dual Carrier REST)** for shipments + labels is planned but not started.

Integration overview and test credentials are in `docs/Mondial Relay.pdf`. The V1 API surface was derived from the live WSDL (`https://api.mondialrelay.com/Web_Services.asmx?WSDL`); the official docs PDF (mondialrelay.fr) is behind Cloudflare and not fetchable programmatically.

## Commands

```bash
composer test                    # Run all tests (Pest)
vendor/bin/pest tests/ExampleTest.php        # Run a single test file
vendor/bin/pest --filter="test name"         # Run a single test by name
composer test-coverage           # Tests with coverage
composer analyse                 # PHPStan (larastan, level 5, on src)
composer format                  # Laravel Pint (code style)
```

`composer prepare` (runs automatically post-autoload-dump) executes Testbench package discovery.

## Architecture

Standard Spatie package structure built on `spatie/laravel-package-tools`:

- **Namespace**: `SmartDato\MondialRelay` → `src/`
- **`MondialRelayServiceProvider`** registers the config file in `configurePackage()` and binds `V1\Client` and `MondialRelay` as singletons in `packageRegistered()`, wired from `config/mondial-relay-sdk.php`.
- **`V1\Client`** is the SOAP transport: it builds the envelope, computes the `Security` MD5 hash (all request params concatenated in WSDL order + private key, uppercased), POSTs via Laravel's `Http` client, parses the response with SimpleXML, and maps error `STAT` codes to `MondialRelayWebServiceException`. Version-specific code lives under `src/V1/` (Queries, Data objects, Enums); V2 should follow the same pattern under `src/V2/`. Constructing a `Client` directly (`new Client($enseigne, $privateKey, $url = DEFAULT_URL)`) supports ad-hoc accounts beside the config-driven singleton. The raw SOAP exchange is kept on the client (`lastRawRequest()`/`lastRawResponse()`) and attached to every thrown `MondialRelayException` (`rawRequest`/`rawResponse`).
- **`MondialRelay`** is the thin public entry point (`searchPickupPoints`, `pickupPoint`, `trackParcel`); the facade resolves it from the container.
- Responses are mapped to readonly data objects (`V1/Data/*`) via `fromXml()` named constructors — the API returns padded strings and comma-decimal coordinates, which are normalized there.

## Testing

- Pest 4 with Orchestra Testbench; `tests/TestCase.php` boots the service provider and sets test credentials (`TESTTEST`/`PrivateK`) used by the signed-request assertions. All tests use this TestCase via `tests/Pest.php`.
- HTTP is faked with `Http::fake()` + SOAP XML fixtures in `tests/Fixtures/v1/`. Pest's built-in `fixture()` helper returns the absolute path to a fixture file (wrap with `file_get_contents`).
- Live tests against the real Mondial Relay test API (credentials in `docs/Mondial Relay.pdf`) are in the `integration` group, skipped unless `MONDIAL_RELAY_LIVE=1`: `MONDIAL_RELAY_LIVE=1 vendor/bin/pest --group=integration`. Run them after touching `V1\Client` request building — they validate the Security hash against the live endpoint.
- `tests/ArchTest.php` enforces that `dd`, `dump`, and `ray` are never committed.
- CI matrix: PHP 8.4–8.5 × Laravel 12/13 × prefer-lowest/prefer-stable on Ubuntu and Windows. `composer.json` requires PHP `^8.4` — keep code compatible with the full CI matrix. All GitHub Actions must be pinned to full-length commit SHAs (org policy).
