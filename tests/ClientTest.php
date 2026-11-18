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
}
