<?php

declare(strict_types=1);

namespace Homlity\Sdk\Api;

final class LocationsApi extends BaseApi
{
    /**
     * GET /location/{name}
     */
    public function search(string $name): mixed
    {
        return $this->send('GET', '/location/' . $this->encodePath($name));
    }
}
