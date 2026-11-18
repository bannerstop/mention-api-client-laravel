<?php

declare(strict_types=1);

namespace Bannerstop\MentionApiLaravel\Tests;

use Bannerstop\MentionApiLaravel\Http\GuzzleHttpClient;
use GuzzleHttp\Promise\FulfilledPromise;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

final class GuzzleHttpClientTest extends TestCase
{
    /**
     * @param array<string, mixed> $seen the options Guzzle sent the request with
     */
    private function client(array &$seen, int $status = 200): GuzzleHttpClient
    {
        return new GuzzleHttpClient(['handler' => static function (RequestInterface $request, array $options) use (&$seen, $status) {
            $seen = $options;

            return new FulfilledPromise(new Response($status, [], 'answer'));
        }]);
    }

    public function testItWaitsAtMost30SecondsByDefault(): void
    {
        $seen = [];

        $this->client($seen)->sendRequest(new Request('GET', 'https://mention-api.example.com/v2/queries'));

        self::assertSame(30.0, (float) $seen['timeout']);
    }

    public function testTheTimeoutOfTheConfigurationReplacesIt(): void
    {
        $seen = [];

        $this->client($seen)->withTimeout(7.5)->sendRequest(new Request('GET', 'https://mention-api.example.com/v2/queries'));

        self::assertSame(7.5, (float) $seen['timeout']);
        self::assertSame(7.5, (float) $seen['connect_timeout'], 'Connecting never takes longer than the whole call may.');
    }

    public function testConnectingTakesAtMost10SecondsEvenWithALongTimeout(): void
    {
        $seen = [];

        $this->client($seen)->withTimeout(120)->sendRequest(new Request('GET', 'https://mention-api.example.com/v2/queries'));

        self::assertSame(120.0, (float) $seen['timeout']);
        self::assertSame(10.0, (float) $seen['connect_timeout']);
    }

    public function testAnErrorStatusIsAnAnswerNotAnException(): void
    {
        $seen = [];

        $response = $this->client($seen, 503)->sendRequest(new Request('GET', 'https://mention-api.example.com/v2/queries'));

        self::assertSame(503, $response->getStatusCode());
        self::assertSame('answer', (string) $response->getBody());
    }
}
