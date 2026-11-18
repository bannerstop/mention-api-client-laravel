<?php

declare(strict_types=1);

namespace Bannerstop\MentionApiLaravel\Facades;

use Bannerstop\MentionApi\MentionApiClient;
use Illuminate\Support\Facades\Facade;

/**
 * The MentionApiClient of the application.
 *
 * @method static \Bannerstop\MentionApi\Result\QueryResult run(string $name, array<string, mixed> $parameters = [], ?string $database = null)
 * @method static \Bannerstop\MentionApi\Result\QueryList listQueries()
 * @method static \Bannerstop\MentionApi\Result\QueryDescription describeQuery(string $name)
 * @method static array<string, mixed> openApi()
 * @method static \Bannerstop\MentionApi\Result\RoutineList listRoutines()
 * @method static \Bannerstop\MentionApi\Result\RoutineResult runRoutine(string $name, array<string, mixed> $parameters = [], ?string $database = null)
 *
 * @see MentionApiClient
 */
class MentionApi extends Facade
{
    protected static function getFacadeAccessor()
    {
        return MentionApiClient::class;
    }
}
