<?php

declare(strict_types=1);

namespace Bannerstop\MentionApiLaravel\Tests;

use Bannerstop\MentionApiLaravel\MentionApiServiceProvider;
use Orchestra\Testbench\TestCase as Testbench;

abstract class TestCase extends Testbench
{
    protected const BASE_URL = 'https://mention-api.example.com';

    /**
     * @param \Illuminate\Foundation\Application $app
     *
     * @return string[]
     */
    protected function getPackageProviders($app)
    {
        return [MentionApiServiceProvider::class];
    }

    /**
     * Every test talks to a fake instead of the network: the configured PSR-18 client is the fake, and the test gets
     * the same instance from fake().
     *
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $app->singleton(FakeMentionApi::class);
        $app['config']->set('mention-api.http_client', FakeMentionApi::class);
    }

    protected function fake(): FakeMentionApi
    {
        return $this->app->make(FakeMentionApi::class);
    }

    protected function useApiKey(string $key = 'the-api-key'): void
    {
        $this->app['config']->set('mention-api.base_url', self::BASE_URL);
        $this->app['config']->set('mention-api.api_key', $key);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    protected function useClientCredentials(array $overrides = []): void
    {
        $this->app['config']->set('mention-api.base_url', self::BASE_URL);
        $this->app['config']->set('mention-api.client_credentials', array_replace([
            'token_url' => 'https://sso.example.com/realms/example/protocol/openid-connect/token',
            'client_id' => 'my-app',
            'client_secret' => 'the-client-secret',
            'scope' => 'mention-api',
        ], $overrides));
    }

    protected function enqueueToken(string $token = 'token-1'): void
    {
        $this->fake()->enqueueJson(['access_token' => $token, 'expires_in' => 300, 'token_type' => 'Bearer']);
    }
}
