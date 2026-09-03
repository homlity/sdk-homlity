<?php

declare(strict_types=1);

namespace Homlity\Sdk\Api;

use Homlity\Sdk\Data\CompanyProfile;
use Homlity\Sdk\Support\ResponseData;

final class CompanyApi extends BaseApi
{
    public function profile(): CompanyProfile
    {
        $response = $this->send('GET', '/v1/inmobiliaria/profile');

        return CompanyProfile::fromArray(ResponseData::object($response));
    }
}
