<?php

declare(strict_types=1);

namespace Bannerstop\MentionApiLaravel\Exception;

/**
 * The configuration file mention-api.php (or the environment variables behind it) cannot be used. The message names
 * the option and the environment variable to fix.
 */
final class ConfigurationException extends \InvalidArgumentException
{
}
