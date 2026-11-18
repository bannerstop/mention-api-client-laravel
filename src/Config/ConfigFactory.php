<?php

declare(strict_types=1);

namespace Bannerstop\MentionApiLaravel\Config;

use Bannerstop\MentionApi\MentionApiConfig;
use Bannerstop\MentionApiLaravel\Exception\ConfigurationException;

/**
 * Builds the MentionApiConfig of the client from the configuration file and rejects what cannot work with a message
 * that names the option and its environment variable. Empty strings count as not set, because that is what an empty
 * line in an .env file produces.
 *
 * @internal
 */
final class ConfigFactory
{
    private const CREDENTIAL_OPTIONS = [
        'token_url' => 'MENTION_API_TOKEN_URL',
        'client_id' => 'MENTION_API_CLIENT_ID',
        'client_secret' => 'MENTION_API_CLIENT_SECRET',
    ];

    /**
     * @param array<string, mixed> $options
     *
     * @throws ConfigurationException
     */
    public static function create(array $options): MentionApiConfig
    {
        $baseUrl = self::string($options['base_url'] ?? null);
        if (null === $baseUrl) {
            throw new ConfigurationException('mention-api.base_url (MENTION_API_URL) is required: the address of mention-api, e.g. https://mention-api.example.com');
        }
        self::assertUrl($baseUrl, 'mention-api.base_url', 'MENTION_API_URL');
        if (1 === preg_match('#/v2/?$#', $baseUrl)) {
            throw new ConfigurationException('mention-api.base_url (MENTION_API_URL) must be the address of mention-api without /v2, e.g. https://mention-api.example.com: the client adds the path.');
        }

        $config = self::authenticated($baseUrl, $options);

        return $config->withTimeout(self::timeout($options['timeout'] ?? MentionApiConfig::DEFAULT_TIMEOUT));
    }

    /**
     * @param array<string, mixed> $options
     */
    private static function authenticated(string $baseUrl, array $options): MentionApiConfig
    {
        $apiKey = self::string($options['api_key'] ?? null);
        $credentials = [];
        foreach ((array) ($options['client_credentials'] ?? []) as $name => $value) {
            $credentials[$name] = self::string($value);
        }
        $credentialsUsed = [] !== array_filter(array_intersect_key($credentials, self::CREDENTIAL_OPTIONS), static function (?string $value): bool {
            return null !== $value;
        });

        if (null !== $apiKey && $credentialsUsed) {
            throw new ConfigurationException('Configure either mention-api.api_key (MENTION_API_KEY) or mention-api.client_credentials, not both.');
        }
        if (null !== $apiKey) {
            return MentionApiConfig::forApiKey($baseUrl, $apiKey);
        }
        if (!$credentialsUsed) {
            throw new ConfigurationException('mention-api needs a credential: set mention-api.api_key (MENTION_API_KEY), or mention-api.client_credentials (MENTION_API_TOKEN_URL, MENTION_API_CLIENT_ID, MENTION_API_CLIENT_SECRET).');
        }
        foreach (self::CREDENTIAL_OPTIONS as $name => $variable) {
            if (null === ($credentials[$name] ?? null)) {
                throw new ConfigurationException(sprintf('mention-api.client_credentials.%s (%s) is required when client credentials are used.', $name, $variable));
            }
        }
        self::assertUrl((string) $credentials['token_url'], 'mention-api.client_credentials.token_url', 'MENTION_API_TOKEN_URL');

        return MentionApiConfig::forClientCredentials(
            $baseUrl,
            (string) $credentials['token_url'],
            (string) $credentials['client_id'],
            (string) $credentials['client_secret'],
            $credentials['scope'] ?? MentionApiConfig::DEFAULT_SCOPE
        );
    }

    /**
     * @param mixed $value
     */
    private static function timeout($value): float
    {
        if (!is_numeric($value) || (float) $value <= 0) {
            throw new ConfigurationException(sprintf('mention-api.timeout (MENTION_API_TIMEOUT) must be a number of seconds greater than zero, got %s.', \is_scalar($value) ? '"' . $value . '"' : \gettype($value)));
        }

        return (float) $value;
    }

    private static function assertUrl(string $url, string $option, string $variable): void
    {
        if (1 !== preg_match('#^https?://[^/\s]#i', $url)) {
            throw new ConfigurationException(sprintf('%s (%s) must be an http(s) URL such as https://mention-api.example.com, got "%s".', $option, $variable, $url));
        }
    }

    /**
     * @param mixed $value
     */
    private static function string($value): ?string
    {
        if (null === $value || (\is_string($value) && '' === trim($value))) {
            return null;
        }

        return \is_scalar($value) ? (string) $value : null;
    }
}
