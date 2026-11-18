<?php

declare(strict_types=1);

namespace Bannerstop\MentionApiLaravel\Tests;

use Bannerstop\MentionApi\Http\TimeoutAware;
use Nyholm\Psr7\Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Stands in for mention-api and the token endpoint: answers with what the test queued and records the requests.
 */
final class FakeMentionApi implements ClientInterface, TimeoutAware
{
    /** @var float|null the timeout the client handed to it, in seconds */
    public $timeout;

    /** @var list<array{method: string, url: string, headers: array<string, list<string>>, body: string}> */
    public $requests = [];

    /** @var list<ResponseInterface> */
    private $responses = [];

    /**
     * @param array<string, mixed> $body
     */
    public function enqueueJson(array $body, int $status = 200): self
    {
        $this->responses[] = new Response($status, ['Content-Type' => 'application/json'], json_encode($body, JSON_THROW_ON_ERROR));

        return $this;
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    public function enqueueRows(array $rows): self
    {
        return $this->enqueueJson(['data' => $rows, 'meta' => ['query' => 'getThings', 'version' => 1, 'rows' => \count($rows), 'duration_ms' => 3, 'request_id' => 'req_test']]);
    }

    public function enqueueProblem(int $status, string $code, string $requestId): self
    {
        $this->responses[] = new Response($status, ['Content-Type' => 'application/problem+json', 'X-Request-Id' => $requestId], json_encode([
            'type' => 'urn:bannerstop:mention-api:problem:' . $code,
            'title' => 'Problem',
            'status' => $status,
            'detail' => 'Something is wrong.',
            'request_id' => $requestId,
        ], JSON_THROW_ON_ERROR));

        return $this;
    }

    public function withTimeout(float $seconds): ClientInterface
    {
        $this->timeout = $seconds;

        return $this;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->requests[] = [
            'method' => $request->getMethod(),
            'url' => (string) $request->getUri(),
            'headers' => array_change_key_case($request->getHeaders(), CASE_LOWER),
            'body' => (string) $request->getBody(),
        ];
        $response = array_shift($this->responses);
        if (null === $response) {
            throw new \LogicException(sprintf('The test queued no answer for %s %s.', $request->getMethod(), $request->getUri()));
        }

        return $response;
    }
}
