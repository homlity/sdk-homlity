<?php

declare(strict_types=1);

namespace Homlity\Sdk\Api;

use Homlity\Sdk\Data\Channel;
use Homlity\Sdk\Exception\ValidationException;
use Homlity\Sdk\Support\ResponseData;

final class ChannelsApi extends BaseApi
{
    /** @return list<Channel> */
    public function list(): array
    {
        $response = $this->send('GET', '/v1/channels');
        $channels = [];

        foreach (ResponseData::list($response) as $item) {
            if (!is_array($item)) {
                throw new ValidationException('Channel data items must be objects.');
            }

            $channels[] = Channel::fromArray($item);
        }

        return $channels;
    }
}
