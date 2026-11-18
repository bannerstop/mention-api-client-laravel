<?php

declare(strict_types=1);

namespace Bannerstop\MentionApiLaravel;

use Bannerstop\MentionApi\MentionApiClient;
use Bannerstop\MentionApiLaravel\Console\GenerateCommand;
use Bannerstop\MentionApiLaravel\Config\ConfigFactory;
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
                ConfigFactory::create($options),
                null === ($options['http_client'] ?? null) ? new GuzzleHttpClient() : $app->make($options['http_client']),
                $factory,
                $factory,
                $this->cache($app, $options['cache_store'] ?? null)
            );
        });
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

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__ . '/../config/mention-api.php' => $this->app->configPath('mention-api.php')], 'mention-api-config');
            $this->commands([GenerateCommand::class]);
        }
    }
}
