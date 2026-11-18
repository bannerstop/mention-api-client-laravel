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

        try {
            Artisan::call('vendor:publish', ['--tag' => 'mention-api-config']);

            self::assertFileExists($target);
            self::assertStringContainsString('MENTION_API_URL', (string) file_get_contents($target));
        } finally {
            @unlink($target);
        }
    }
}
