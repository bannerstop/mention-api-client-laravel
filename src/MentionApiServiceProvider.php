<?php

declare(strict_types=1);

namespace Bannerstop\MentionApiLaravel;

use Bannerstop\MentionApi\MentionApiClient;
use Bannerstop\MentionApi\MentionApiConfig;
use Bannerstop\MentionApiLaravel\Http\GuzzleHttpClient;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\SimpleCache\CacheInterface;

final class MentionApiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/mention-api.php', 'mention-api');

        $this->app->singleton(MentionApiClient::class, function (Application $app): MentionApiClient {
            $options = (array) $app->make('config')->get('mention-api');
            $factory = new Psr17Factory();

            return new MentionApiClient(
                $this->config($options),
                null === ($options['http_client'] ?? null) ? new GuzzleHttpClient() : $app->make($options['http_client']),
                $factory,
                $factory,
                $this->cache($app, $options['cache_store'] ?? null)
            );
        });
    }

    /**
     * @param array<string, mixed> $options
     */
    private function config(array $options): MentionApiConfig
    {
        if (null !== ($options['api_key'] ?? null)) {
            $config = MentionApiConfig::forApiKey((string) $options['base_url'], (string) $options['api_key']);

            return $config->withTimeout((float) $options['timeout']);
        }
        $credentials = (array) $options['client_credentials'];

        $config = MentionApiConfig::forClientCredentials(
            (string) $options['base_url'],
            (string) $credentials['token_url'],
            (string) $credentials['client_id'],
            (string) $credentials['client_secret'],
            (string) $credentials['scope']
        );

        return $config->withTimeout((float) $options['timeout']);
    }

    /**
     * @param string|false|null $store
     */
    private function cache(Application $app, $store): ?CacheInterface
    {
        if (false === $store) {
            return null;
        }

        return $app->make('cache')->store($store);
    }
}
