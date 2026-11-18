<?php

declare(strict_types=1);

namespace Bannerstop\MentionApiLaravel\Tests;

use Bannerstop\MentionApi\MentionApiClient;
use Bannerstop\MentionApiLaravel\Exception\ConfigurationException;

final class ConfigurationTest extends TestCase
{
    /**
     * @param array<string, mixed> $values
     */
    private function configure(array $values): void
    {
        foreach ($values as $key => $value) {
            $this->app['config']->set('mention-api.' . $key, $value);
        }
    }

    private function assertRejected(string ...$fragments): void
    {
        try {
            $this->app->make(MentionApiClient::class);
        } catch (ConfigurationException $e) {
            foreach ($fragments as $fragment) {
                self::assertStringContainsString($fragment, $e->getMessage());
            }

            return;
        }
        self::fail('The configuration was accepted.');
    }

    public function testTheApplicationBootsWithoutAnyConfigurationAndTheClientRefusesToBeBuilt(): void
    {
        $this->assertRejected('mention-api.base_url', 'MENTION_API_URL');
    }

    public function testAnEmptyBaseUrlFromAnEnvFileCountsAsMissing(): void
    {
        $this->configure(['base_url' => '', 'api_key' => 'the-api-key']);

        $this->assertRejected('mention-api.base_url', 'MENTION_API_URL');
    }

    public function testTheBaseUrlMustBeAnHttpUrl(): void
    {
        $this->configure(['base_url' => 'mention-api.example.com', 'api_key' => 'the-api-key']);

        $this->assertRejected('mention-api.base_url', 'http(s) URL', 'mention-api.example.com');
    }

    public function testTheBaseUrlMustNotEndInV2(): void
    {
        $this->configure(['base_url' => self::BASE_URL . '/v2', 'api_key' => 'the-api-key']);

        $this->assertRejected('mention-api.base_url', 'without /v2');
    }

    public function testACredentialIsRequired(): void
    {
        $this->configure(['base_url' => self::BASE_URL]);

        $this->assertRejected('mention-api.api_key', 'MENTION_API_KEY', 'mention-api.client_credentials');
    }

    public function testApiKeyAndClientCredentialsCannotBeCombined(): void
    {
        $this->useApiKey();
        $this->useClientCredentials();

        $this->assertRejected('either', 'api_key', 'client_credentials');
    }

    public function testIncompleteClientCredentialsNameWhatIsMissing(): void
    {
        $this->configure(['base_url' => self::BASE_URL, 'client_credentials' => ['token_url' => 'https://sso.example.com/token', 'client_id' => 'my-app', 'client_secret' => '', 'scope' => 'mention-api']]);

        $this->assertRejected('mention-api.client_credentials.client_secret', 'MENTION_API_CLIENT_SECRET');
    }

    public function testTheTokenUrlMustBeAnHttpUrl(): void
    {
        $this->useClientCredentials(['token_url' => 'sso.example.com/token']);

        $this->assertRejected('mention-api.client_credentials.token_url', 'http(s) URL');
    }

    public function testTheTimeoutMustBeANumberGreaterThanZero(): void
    {
        $this->useApiKey();
        foreach ([0, -1, 'soon'] as $timeout) {
            $this->configure(['timeout' => $timeout]);
            $this->app->forgetInstance(MentionApiClient::class);

            $this->assertRejected('mention-api.timeout', 'MENTION_API_TIMEOUT');
        }
    }

    public function testTheHttpClientMustBeAPsr18Client(): void
    {
        $this->useApiKey();
        $this->configure(['http_client' => \stdClass::class]);

        $this->assertRejected('mention-api.http_client', 'PSR-18');
    }

    public function testAnEmptyScopeFallsBackToTheDefault(): void
    {
        $this->useClientCredentials(['scope' => '']);
        $this->enqueueToken();
        $this->fake()->enqueueRows([]);

        $this->app->make(MentionApiClient::class)->run('getThings');

        parse_str($this->fake()->requests[0]['body'], $form);
        self::assertSame('mention-api', $form['scope']);
    }
}
