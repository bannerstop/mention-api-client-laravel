<?php

declare(strict_types=1);

namespace Bannerstop\MentionApiLaravel\Tests;

use Illuminate\Support\Facades\Artisan;

final class GenerateCommandTest extends TestCase
{
    /** @var string */
    private $projectDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->projectDir = sys_get_temp_dir() . '/mention-api-client-laravel-' . getmypid() . '-' . bin2hex(random_bytes(4));
        mkdir($this->projectDir);
        copy(__DIR__ . '/Fixtures/composer-php74.json', $this->projectDir . '/composer.json');
        $this->app->setBasePath($this->projectDir);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->remove($this->projectDir);
    }

    private function remove(string $path): void
    {
        if (is_dir($path) && !is_link($path)) {
            foreach (scandir($path) ?: [] as $entry) {
                if ('.' !== $entry && '..' !== $entry) {
                    $this->remove($path . '/' . $entry);
                }
            }
            rmdir($path);
        } elseif (file_exists($path)) {
            unlink($path);
        }
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array{0: int, 1: string} the exit status and the output
     */
    private function generate(array $options = []): array
    {
        $status = Artisan::call('mention-api:generate', $options);

        return [$status, Artisan::output()];
    }

    private function queueDocument(): void
    {
        $this->fake()->enqueueJson(json_decode((string) file_get_contents(__DIR__ . '/Fixtures/openapi.json'), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testTheCommandIsRegisteredWithArtisan(): void
    {
        $command = Artisan::all()['mention-api:generate'] ?? null;

        self::assertNotNull($command);
        self::assertNotSame('', $command->getDescription());
    }

    public function testItFetchesTheDocumentWithTheConfiguredClientAndWritesTheWrappers(): void
    {
        $this->useApiKey();
        $this->queueDocument();

        [$status, $output] = $this->generate(['--namespace' => 'App\Mention', '--dir' => 'app/Mention']);

        self::assertSame(0, $status, $output);
        self::assertStringContainsString('Wrote', $output);
        self::assertFileExists($this->projectDir . '/app/Mention/Queries.php');
        self::assertFileExists($this->projectDir . '/app/Mention/Routines.php');
        self::assertStringContainsString('namespace App\Mention;', (string) file_get_contents($this->projectDir . '/app/Mention/Queries.php'));
        $request = $this->fake()->requests[0];
        self::assertSame('GET', $request['method']);
        self::assertSame(self::BASE_URL . '/v2/openapi.json', $request['url']);
        self::assertSame(['Bearer the-api-key'], $request['headers']['authorization']);
    }

    public function testNamespaceAndDirectoryDefaultToTheAppNamespaceAndDirectory(): void
    {
        $this->useApiKey();
        $this->queueDocument();

        [$status, $output] = $this->generate();

        self::assertSame(0, $status, $output);
        self::assertStringContainsString('namespace App\Mention;', (string) file_get_contents($this->projectDir . '/app/Mention/Queries.php'));
    }

    public function testNamespaceAndDirectoryCanBeConfigured(): void
    {
        $this->useApiKey();
        $this->app['config']->set('mention-api.generator', ['namespace' => 'App\Remote', 'directory' => 'src/Remote']);
        $this->queueDocument();

        [$status, $output] = $this->generate();

        self::assertSame(0, $status, $output);
        self::assertStringContainsString('namespace App\Remote;', (string) file_get_contents($this->projectDir . '/src/Remote/Queries.php'));
    }

    public function testCheckPassesForCurrentFilesAndFailsOnceTheyDrift(): void
    {
        $this->useApiKey();
        $this->queueDocument();
        $this->generate();

        $this->queueDocument();
        [$status, $output] = $this->generate(['--check' => true]);
        self::assertSame(0, $status, $output);
        self::assertStringContainsString('up to date', $output);

        file_put_contents($this->projectDir . '/app/Mention/Queries.php', "<?php\n// edited by hand\n", FILE_APPEND);
        $this->queueDocument();
        [$status, $output] = $this->generate(['--check' => true]);
        self::assertSame(1, $status);
        self::assertStringContainsString('Queries.php', $output);
    }

    public function testAMissingNamespaceIsExplainedAndNothingIsFetched(): void
    {
        $this->useApiKey();
        $this->app['config']->set('mention-api.generator.namespace', null);

        [$status, $output] = $this->generate();

        self::assertSame(2, $status);
        self::assertStringContainsString('--namespace', $output);
        self::assertStringContainsString('generator.namespace', $output);
        self::assertSame([], $this->fake()->requests);
    }

    public function testADocumentFileNeedsNoCredentialsAndNoRequest(): void
    {
        copy(__DIR__ . '/Fixtures/openapi.json', $this->projectDir . '/openapi.json');

        [$status, $output] = $this->generate(['--source' => 'openapi.json']);

        self::assertSame(0, $status, $output);
        self::assertSame([], $this->fake()->requests);
        self::assertFileExists($this->projectDir . '/app/Mention/Queries.php');
    }

    public function testThePhpTargetIsReadFromComposerJsonAndCanBeOverridden(): void
    {
        $this->useApiKey();
        $this->queueDocument();
        $this->generate();
        $plain = (string) file_get_contents($this->projectDir . '/app/Mention/Query/ListWidgetsRow.php');

        $this->queueDocument();
        $this->generate(['--dir' => 'app/Modern', '--php' => '8.2']);
        $modern = (string) file_get_contents($this->projectDir . '/app/Modern/Query/ListWidgetsRow.php');

        self::assertStringNotContainsString('final readonly class', $plain);
        self::assertStringContainsString('final readonly class', $modern);
    }

    public function testAnErrorOfMentionApiIsReportedWithItsRequestId(): void
    {
        $this->useApiKey();
        $this->fake()->enqueueProblem(401, 'unauthorized', 'req_denied');

        [$status, $output] = $this->generate();

        self::assertSame(2, $status);
        self::assertStringContainsString('req_denied', $output);
        self::assertFileDoesNotExist($this->projectDir . '/app/Mention/Queries.php');
    }

    public function testAnInvalidConfigurationIsReportedByNameInsteadOfAStackTrace(): void
    {
        [$status, $output] = $this->generate();

        self::assertSame(2, $status);
        self::assertStringContainsString('mention-api.base_url', $output);
    }
}
