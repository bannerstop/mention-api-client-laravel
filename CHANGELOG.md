# Changelog

All notable changes to this package are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the package follows
[Semantic Versioning](https://semver.org/).

## [1.0.0] - 2026-11-18

### Added

- `MentionApiServiceProvider` (registered by package discovery) with the configuration file `config/mention-api.php`,
  publishable with the tag `mention-api-config`: `base_url`, `api_key` or `client_credentials`, `timeout`,
  `cache_store`, `http_client` and `generator`, each read from an environment variable. A wrong configuration is
  rejected with a message that names the option and its environment variable.
- A `Bannerstop\MentionApi\MentionApiClient` bound as a singleton and resolvable by type; by default it sends requests
  with Guzzle using the configured timeout and keeps the access token of client credentials in the cache.
- The `MentionApi` facade.
- The Artisan command `mention-api:generate`, which fetches the OpenAPI document of your Consumer with the configured
  client and writes the typed wrappers of the core package; `--check` fails when the committed classes are out of date.
- Support for PHP 7.4 to 8.5 and Laravel 8 to 13.

[1.0.0]: https://github.com/bannerstop/mention-api-client-laravel/releases/tag/v1.0.0
