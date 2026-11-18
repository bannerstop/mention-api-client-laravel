# bannerstop/mention-api-client-laravel

Laravel integration of [bannerstop/mention-api-client](https://github.com/bannerstop/mention-api-client),
the PHP client for the `/v2` interface of mention-api. The package gives you:

- **A publishable configuration** for the base URL, the credential (API key or Keycloak client credentials),
  the cache store for the access token and the timeout, all from environment variables
- **A `MentionApiClient` in the container**, resolvable by type, and the **`MentionApi` facade**, to run Queries and
  Routines
- **The generator for typed wrappers as an Artisan command**, `mention-api:generate`

## Versions

There is one major version for every supported Laravel and PHP version. A new major version is released only when
a minimum version rises or a range cannot be held any longer; everything else ships in a minor version.

| Version | PHP        | Laravel                       |
|---------|------------|-------------------------------|
| 1.x     | 7.4 - 8.5+ | 8, 9, 10, 11, 12 and 13       |

Each Laravel version runs on the PHP versions it allows itself (for example Laravel 13 needs PHP 8.3 or later), and
the package follows. It requires `bannerstop/mention-api-client` `^1.0` and follows
[Semantic Versioning](https://semver.org/). Classes marked `@internal` are not part of the public API.

Laravel 8 to 10 no longer receive security fixes from the Laravel team. The package works with them; the decision to
stay on them is yours.

## Installation

```bash
composer require bannerstop/mention-api-client-laravel
```

Laravel finds the service provider and the `MentionApi` facade alias by itself. The package sends its requests with
Guzzle, which every Laravel application has, and uses `nyholm/psr7` for the PSR-17 factories; both are installed with
it.

Publish the configuration file if you want to change more than the environment variables allow:

```bash
php artisan vendor:publish --tag=mention-api-config
```

## Configuration

Your app calls mention-api as a *Consumer* with its own credential. The Scope of the Consumer decides which Queries and
Routines it may call. Pick one of two ways to authenticate, and set the base URL.

### API key

```dotenv
MENTION_API_URL=https://mention-api.example.com
MENTION_API_KEY=your-api-key
```

`MENTION_API_URL` is the address of mention-api **without** `/v2`: the client adds the path.

### Keycloak client credentials

The Keycloak client needs service accounts enabled and the client scope `mention-api`. The client fetches the access
token itself and keeps it in the cache until shortly before it expires.

```dotenv
MENTION_API_URL=https://mention-api.example.com
MENTION_API_TOKEN_URL=https://sso.example.com/realms/example/protocol/openid-connect/token
MENTION_API_CLIENT_ID=my-app
MENTION_API_CLIENT_SECRET=the-client-secret
# MENTION_API_SCOPE=mention-api   # this is the default
```

Use either the API key or the client credentials, not both. Use a cache store that all your processes share (Redis,
database, file): with a per-process store such as `array`, every PHP process fetches its own token.

### All options

| Option (`config/mention-api.php`) | Environment variable | Default | Meaning |
| --- | --- | --- | --- |
| `base_url` | `MENTION_API_URL` | none, required | Address of mention-api, `http(s)`, without `/v2` |
| `api_key` | `MENTION_API_KEY` | none | The API key of your Consumer |
| `client_credentials.token_url` | `MENTION_API_TOKEN_URL` | none | Token endpoint of Keycloak |
| `client_credentials.client_id` | `MENTION_API_CLIENT_ID` | none | |
| `client_credentials.client_secret` | `MENTION_API_CLIENT_SECRET` | none | |
| `client_credentials.scope` | `MENTION_API_SCOPE` | `mention-api` | |
| `timeout` | `MENTION_API_TIMEOUT` | `30` | Seconds to wait for an answer, see "Timeout" |
| `cache_store` | `MENTION_API_CACHE_STORE` | `null` | Name of the cache store for the token: `null` = the default store, `false` (in the file) = no cache |
| `http_client` | none | `null` | A PSR-18 client, see "Another HTTP client" |
| `generator.namespace` | none | `App\Mention` | Default namespace of `mention-api:generate` |
| `generator.directory` | none | `app/Mention` | Default directory, relative to the base path |

A wrong configuration is rejected with a message that names the option and its environment variable, for example
`mention-api.base_url (MENTION_API_URL) is required`. The check happens when the client is first resolved, not when the
application boots, so `artisan package:discover`, `config:cache` and your CI do not need credentials.

The access token is stored under a key derived from the token URL, the client id and the scope, so stores can be shared.
Whoever can read the cache can read access tokens: use a store that only your application can read.

### Timeout

`timeout` is the number of seconds to wait for an answer, 30 by default. With the default HTTP client it limits the whole
call (connecting takes at most 10 s of it), so raise it for Queries that take long. If you set `http_client` to your own
PSR-18 client, the timeout reaches it only when the client implements `Bannerstop\MentionApi\Http\TimeoutAware`;
**otherwise set the timeout on that client yourself.**

### Another HTTP client

Set `http_client` in the published configuration to the class or container binding of any PSR-18 client:

```php
// config/mention-api.php
'http_client' => App\Http\MentionHttpClient::class,   // implements Psr\Http\Client\ClientInterface
```

## Usage

Inject `Bannerstop\MentionApi\MentionApiClient` or use the facade:

```php
use Bannerstop\MentionApi\MentionApiClient;
use Bannerstop\MentionApiLaravel\Facades\MentionApi;

final class OrderReport
{
    public function total(MentionApiClient $mention, int $orderId): string
    {
        $result = $mention->run('getOrderData', ['orderId' => $orderId]);

        foreach ($result as $row) {
            return $row['total'];
        }

        throw new \RuntimeException(sprintf('No order data (request %s).', $result->getRequestId()));
    }
}

$rows = MentionApi::run('getOrderData', ['orderId' => 4711])->getRows();
```

Queries, Routines, errors (one exception class per error code, each with the request id) and retries (Queries only,
never Routines) are described in the
[README of the core package](https://github.com/bannerstop/mention-api-client#readme).

## Typed wrappers

The generator reads the OpenAPI document of your Consumer (`GET /v2/openapi.json`, exactly the Queries and Routines your
Consumer may call) and writes classes for your app: per Query a Parameters class, a Row class and a typed method, the
same for Routines. It fetches the document with the client of your application, so it uses your credentials and your
configuration, and it writes the files of the core generator, with the same output.

```bash
php artisan mention-api:generate --namespace='App\Mention' --dir=app/Mention
```

Commit the output. Nothing is generated at runtime, and the generated code calls the client, so authentication, errors,
retries and request ids behave as with `run()`. `--dir`, `--source` and `--composer` are relative to the base path of the application. Without options
the command writes to `app/Mention` in the namespace `App\Mention`; change the defaults in `generator` of the
configuration.

The generated `Queries` and `Routines` classes take the client in their constructor, so the container builds them:

```php
use App\Mention\Queries;
use App\Mention\Query\GetInvoiceTotalsParameters;

final class InvoiceReport
{
    public function totals(Queries $queries, int $customerId): void
    {
        $rows = $queries->getInvoiceTotals(new GetInvoiceTotalsParameters($customerId, '2026-01-01', '2026-01-31'));

        foreach ($rows as $row) {           // GetInvoiceTotalsRow objects
            echo $row->invoiceId, ' ', $row->total, "\n";
        }
        echo $rows->getRequestId();
    }
}
```

The classes, the Parameters and the Rows are described in the
[README of the core package](https://github.com/bannerstop/mention-api-client#typed-wrappers).

### Options of the command

| Option | Meaning |
| --- | --- |
| `--namespace=<ns>` | PHP namespace of the generated classes (default: `generator.namespace`) |
| `--dir=<dir>` | Directory to write them to (default: `generator.directory`) |
| `--check` | Write nothing; exit with status 1 when the files differ from what would be generated |
| `--source=<file>` | Read the OpenAPI document from a file instead of fetching it; needs no credentials |
| `--php=<7.4\|8.2>` | The PHP the code is written for; by default decided by `require.php` of your `composer.json` |
| `--composer=<file>` | The `composer.json` to read the PHP version from |

Exit status: 0 success, 1 the files differ (`--check`), 2 error.

### Keep the classes current

The output is deterministic. `--check` writes nothing, lists the files that differ and exits with status 1, so that your
CI notices when the catalog of your Consumer changed:

```bash
php artisan mention-api:generate --check
```

Run the command again without `--check` and commit the diff when it fails. The command only ever touches files that it
wrote itself.

## Development

```bash
composer install
vendor/bin/phpunit
vendor/bin/phpstan analyse
```

The tests boot a Laravel application with Orchestra Testbench and answer its HTTP client with scripted responses; no
network is needed. CI runs them on PHP 7.4 to 8.5 with Laravel 8 to 13, and with the lowest allowed dependencies.

## Security

See [SECURITY.md](SECURITY.md).

## License

MIT, see [LICENSE](LICENSE).
