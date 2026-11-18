<?php

declare(strict_types=1);

namespace Bannerstop\MentionApiLaravel\Tests;

use Bannerstop\MentionApi\MentionApiClient;

final class ClientTest extends TestCase
{
    public function testTheClientIsResolvableByTypeAndCallsTheConfiguredBaseUrlWithTheApiKey(): void
    {
        $this->useApiKey();
        $this->fake()->enqueueRows([['id' => 1]]);

        $result = $this->app->make(MentionApiClient::class)->run('getThings');

        self::assertSame([['id' => 1]], $result->getRows());
        $request = $this->fake()->requests[0];
        self::assertSame('POST', $request['method']);
        self::assertStringStartsWith(self::BASE_URL . '/v2/', $request['url']);
        self::assertSame(['Bearer the-api-key'], $request['headers']['authorization']);
    }

    public function testClientCredentialsFetchATokenThatIsKeptInTheCache(): void
    {
        $this->useClientCredentials();
        $this->enqueueToken();
        $this->fake()->enqueueRows([])->enqueueRows([]);

        $client = $this->app->make(MentionApiClient::class);
        $client->run('getThings');
        $client->run('getThings');

        $requests = $this->fake()->requests;
        self::assertCount(3, $requests, 'One token request, then the two calls.');
        self::assertSame('https://sso.example.com/realms/example/protocol/openid-connect/token', $requests[0]['url']);
        parse_str($requests[0]['body'], $form);
        self::assertSame(['grant_type' => 'client_credentials', 'client_id' => 'my-app', 'client_secret' => 'the-client-secret', 'scope' => 'mention-api'], $form);
        self::assertSame(['Bearer token-1'], $requests[2]['headers']['authorization']);
    }

    public function testTheTokenSurvivesANewClientBecauseItIsInTheDefaultCacheStore(): void
    {
        $this->useClientCredentials();
        $this->enqueueToken();
        $this->fake()->enqueueRows([])->enqueueRows([]);

        $this->app->make(MentionApiClient::class)->run('getThings');
        $this->app->forgetInstance(MentionApiClient::class);
        $this->app->make(MentionApiClient::class)->run('getThings');

        self::assertCount(3, $this->fake()->requests, 'The second client reuses the cached token.');
    }

    public function testTheTokenGoesToTheConfiguredCacheStore(): void
    {
        $this->app['config']->set('cache.stores.mention', ['driver' => 'array']);
        $this->app['config']->set('mention-api.cache_store', 'mention');
        $this->useClientCredentials();
        $this->enqueueToken();
        $this->fake()->enqueueRows([]);

        $this->app->make(MentionApiClient::class)->run('getThings');

        $cache = $this->app->make('cache');
        $cache->store()->flush();
        $this->app->forgetInstance(MentionApiClient::class);
        $this->fake()->enqueueRows([]);
        $this->app->make(MentionApiClient::class)->run('getThings');
        self::assertCount(3, $this->fake()->requests, 'Emptying the default store does not matter: the token is in the configured one.');

        $cache->store('mention')->flush();
        $this->app->forgetInstance(MentionApiClient::class);
        $this->enqueueToken('token-2');
        $this->fake()->enqueueRows([]);
        $this->app->make(MentionApiClient::class)->run('getThings');
        self::assertCount(5, $this->fake()->requests, 'Emptying the configured store makes the client fetch a new token.');
    }

    public function testTheCacheCanBeSwitchedOff(): void
    {
        $this->app['config']->set('mention-api.cache_store', false);
        $this->useClientCredentials();
        $this->enqueueToken('token-1');
        $this->fake()->enqueueRows([]);
        $this->enqueueToken('token-2');
        $this->fake()->enqueueRows([]);

        $this->app->make(MentionApiClient::class)->run('getThings');
        $this->app->forgetInstance(MentionApiClient::class);
        $this->app->make(MentionApiClient::class)->run('getThings');

        self::assertCount(4, $this->fake()->requests, 'Every client fetches its own token.');
    }
}
