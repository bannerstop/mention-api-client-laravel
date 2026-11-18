<?php

declare(strict_types=1);

namespace Bannerstop\MentionApiLaravel;

use Bannerstop\MentionApi\MentionApiClient;
use Bannerstop\MentionApi\MentionApiConfig;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Nyholm\Psr7\Factory\Psr17Factory;

final class MentionApiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/mention-api.php', 'mention-api');

        $this->app->singleton(MentionApiClient::class, function (Application $app): MentionApiClient {
            $options = (array) $app->make('config')->get('mention-api');
            $factory = new Psr17Factory();

            return new MentionApiClient(
                MentionApiConfig::forApiKey((string) $options['base_url'], (string) $options['api_key']),
                $app->make($options['http_client']),
                $factory,
                $factory
            );
        });
    }
}
