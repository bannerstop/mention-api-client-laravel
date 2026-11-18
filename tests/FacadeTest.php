<?php

declare(strict_types=1);

namespace Bannerstop\MentionApiLaravel\Tests;

use Bannerstop\MentionApi\MentionApiClient;
use Bannerstop\MentionApiLaravel\Facades\MentionApi;

final class FacadeTest extends TestCase
{
    public function testTheFacadeRunsQueriesWithTheClientOfTheContainer(): void
    {
        $this->useApiKey();
        $this->fake()->enqueueRows([['id' => 7]]);

        $result = MentionApi::run('getThings', ['id' => 7]);

        self::assertSame([['id' => 7]], $result->getRows());
        self::assertSame(['Bearer the-api-key'], $this->fake()->requests[0]['headers']['authorization']);
        self::assertSame(json_encode(['parameters' => ['id' => 7]]), $this->fake()->requests[0]['body']);
    }

    public function testTheFacadeAndTheTypeHintShareOneClient(): void
    {
        $this->useApiKey();

        self::assertSame($this->app->make(MentionApiClient::class), MentionApi::getFacadeRoot());
    }

    public function testComposerRegistersTheProviderAndTheAliasForPackageDiscovery(): void
    {
        $composer = json_decode((string) file_get_contents(__DIR__ . '/../composer.json'), true, 512, JSON_THROW_ON_ERROR);

        $laravel = $composer['extra']['laravel'];
        self::assertTrue(class_exists($laravel['providers'][0]));
        self::assertSame(MentionApi::class, $laravel['aliases']['MentionApi']);
        self::assertTrue(class_exists($laravel['aliases']['MentionApi']));
    }
}
