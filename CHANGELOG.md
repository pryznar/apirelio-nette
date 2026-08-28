# Changelog

## 1.0.0 - 2026-08-28

- Publish the first stable Nette SDK on PHP Core 1.x.
- Test the current Nette series through PHP 8.5 and PHPUnit 13.
- Report the published SDK version in request and business-event telemetry.

## 0.2.1 - 2026-07-31

- Use the active `https://apirelio.com` ingestion endpoint by default.

## 0.2.0 - 2026-07-30

- Rebrand the extension, namespace and service identifiers to Apirelio.
- Require the new `apirelio/php-core` package.
- Replace `tracium` configuration with `apirelio`.

## 0.1.0 - 2026-07-29

- Initial Nette integration built on `apirelio/php-core`.
- Native Nette DI extension and application lifecycle tracking.
- Sync HTTP delivery with retry and optional fail-safe file buffer.
- Customer, application, API version, error code and privacy-safe metadata support.
- Manual business event tracking through `ApirelioManager::track()`.
