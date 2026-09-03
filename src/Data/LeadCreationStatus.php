<?php

declare(strict_types=1);

namespace Homlity\Sdk\Data;

enum LeadCreationStatus: string
{
    case CREATED = 'created';
    case REUSED = 'reused';
    case DUPLICATE = 'duplicate';
    case UNKNOWN = 'unknown';
}
