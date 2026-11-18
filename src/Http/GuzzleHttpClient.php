<?php

declare(strict_types=1);

namespace Bannerstop\MentionApiLaravel\Http;

use Bannerstop\MentionApi\Http\TimeoutAware;
use Bannerstop\MentionApi\MentionApiConfig;
use GuzzleHttp\Client;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * The PSR-18 client the package uses unless you configure another one: Guzzle, with the timeout of the configuration.
 * Guzzle itself cannot take the timeout from the client of this package, because PSR-18 has no way to set it.
 */
final class GuzzleHttpClient implements ClientInterface, TimeoutAware
{
    private const CONNECT_TIMEOUT = 10.0;

    /** @var array<string, mixed> */
    private $options;
    /** @var Client */
    private $client;

    /**
     * @param array<string, mixed> $options Guzzle request options and the handler
     */
    public function __construct(array $options = [])
    {
        $this->options = $options + ['timeout' => MentionApiConfig::DEFAULT_TIMEOUT, 'connect_timeout' => self::CONNECT_TIMEOUT];
        $this->client = new Client($this->options);
    }

    public function withTimeout(float $seconds): ClientInterface
    {
        return new self(['timeout' => $seconds, 'connect_timeout' => min(self::CONNECT_TIMEOUT, $seconds)] + $this->options);
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        return $this->client->sendRequest($request);
    }
}
