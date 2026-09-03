<?php

declare(strict_types=1);

namespace Homlity\Sdk\Data;

enum ClientVerificationStatus: string
{
    case CLIENT = 'client';
    case NOT_CLIENT = 'not_client';
    case INVALID_DOCUMENT = 'invalid_document';
    case MULTIPLE_MATCHES = 'multiple_matches';
}
