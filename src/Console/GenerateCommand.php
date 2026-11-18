<?php

declare(strict_types=1);

namespace Bannerstop\MentionApiLaravel\Console;

use Bannerstop\MentionApi\Exception\MentionApiException;
use Bannerstop\MentionApi\Generator\Console\Application;
use Bannerstop\MentionApi\MentionApiClient;
use Bannerstop\MentionApiLaravel\Exception\ConfigurationException;
use Illuminate\Console\Command;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Generates typed wrappers for the Queries and Routines of your Consumer, or checks that the committed ones are
 * current. It reads the OpenAPI document with the client of the application and hands it to the generator of
 * bannerstop/mention-api-client, so both write the same files.
 */
final class GenerateCommand extends Command
{
    /** @var string */
    protected $signature = 'mention-api:generate
        {--namespace= : PHP namespace of the generated classes (default: generator.namespace of config/mention-api.php)}
        {--dir= : Directory to write them to, relative to the base path (default: generator.directory)}
        {--check : Write nothing; exit with status 1 when the files differ from what would be generated}
        {--source= : Read the OpenAPI document from this file instead of fetching it}
        {--php= : The PHP the code is written for, 7.4 or 8.2 (default: decided by "require.php" of composer.json)}
        {--composer= : The composer.json to read the PHP version from (default: the one of the application)}';

    /** @var string */
    protected $description = 'Generates typed wrappers for the Queries and Routines of your mention-api Consumer';

    public function handle(): int
    {
        $stdout = function (string $text): void {
            $this->output->write(self::artisanHint($text), false, OutputInterface::OUTPUT_RAW);
        };
        $stderr = function (string $text): void {
            $this->output->getErrorStyle()->write(self::artisanHint($text), false, OutputInterface::OUTPUT_RAW);
        };

        $config = (array) $this->laravel->make('config')->get('mention-api.generator');
        $namespace = $this->option('namespace') ?? $config['namespace'] ?? null;
        $directory = $this->option('dir') ?? $config['directory'] ?? null;
        $missing = [];
        if (!\is_string($namespace) || '' === $namespace) {
            $missing[] = 'the namespace (--namespace, or generator.namespace in config/mention-api.php)';
        }
        if (!\is_string($directory) || '' === $directory) {
            $missing[] = 'the directory (--dir, or generator.directory in config/mention-api.php)';
        }
        if ([] !== $missing) {
            $stderr('Missing ' . implode(' and ', $missing) . ".\n");

            return 2;
        }

        $args = ['generate', '--namespace=' . $namespace, '--dir=' . $directory];
        foreach (['php', 'composer'] as $name) {
            $value = $this->option($name);
            if (\is_string($value) && '' !== $value) {
                $args[] = '--' . $name . '=' . $value;
            }
        }
        if (true === $this->option('check')) {
            $args[] = '--check';
        }

        $temporary = null;
        try {
            $source = $this->option('source');
            if (!\is_string($source) || '' === $source) {
                try {
                    $document = $this->laravel->make(MentionApiClient::class)->openApi();
                } catch (ConfigurationException $e) {
                    $stderr('Error: ' . $e->getMessage() . "\n");

                    return 2;
                } catch (MentionApiException $e) {
                    $stderr('Error: the OpenAPI document could not be fetched: ' . $e->getMessage() . "\n");

                    return 2;
                }
                $file = tempnam(sys_get_temp_dir(), 'mention-api-');
                if (false === $file) {
                    $stderr("Error: cannot create a temporary file for the OpenAPI document.\n");

                    return 2;
                }
                $temporary = $file;
                if (false === file_put_contents($file, json_encode($document))) {
                    $stderr("Error: cannot write the OpenAPI document to a temporary file.\n");

                    return 2;
                }
                $source = $file;
            }
            $args[] = '--source=' . $source;

            return (new Application($stdout, $stderr))->run($args, $this->laravel->basePath(), []);
        } finally {
            if (null !== $temporary && is_file($temporary)) {
                unlink($temporary);
            }
        }
    }

    /**
     * The core generator names its own binary in its hints; here the command is the Artisan one.
     */
    private static function artisanHint(string $text): string
    {
        return str_replace('vendor/bin/mention-api-client generate', 'php artisan mention-api:generate', $text);
    }
}
