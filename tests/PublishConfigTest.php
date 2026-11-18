<?php

declare(strict_types=1);

namespace Bannerstop\MentionApiLaravel\Tests;

use Illuminate\Support\Facades\Artisan;

final class PublishConfigTest extends TestCase
{
    public function testTheConfigurationCanBePublishedIntoTheApplication(): void
    {
        $target = $this->app->configPath('mention-api.php');
        @unlink($target);

        Artisan::call('vendor:publish', ['--tag' => 'mention-api-config']);

        self::assertFileExists($target);
        $published = require $target;
        self::assertEqualsCanonicalizing(['base_url', 'api_key', 'client_credentials', 'cache_store', 'timeout', 'http_client', 'generator'], array_keys($published));
        unlink($target);
    }

    public function testTheDefaultsWorkWithoutPublishing(): void
    {
        self::assertSame(30, $this->app['config']->get('mention-api.timeout'));
        self::assertSame('mention-api', $this->app['config']->get('mention-api.client_credentials.scope'));
        self::assertSame('App\Mention', $this->app['config']->get('mention-api.generator.namespace'));
    }
}
