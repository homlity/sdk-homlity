<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Fincaraiz\Sdk\Homlity\Config;
use Fincaraiz\Sdk\Homlity\HomlityClient;

$client = new HomlityClient(new Config('api-key', 'https://tu-wp.com'));

$response = $client->properties()->push([
    'id' => '12345',
    'code' => 'EXT-12345',
    'status' => 'active',
    'available' => true,
    'featured' => false,
    'operation' => 'venta',
    'type' => 'apartamento',
    'category' => 'residencial',
    'country' => 'colombia',
    'state' => 'cundinamarca',
    'city' => 'bogota',
    'neighborhood' => 'chico',
    'address' => 'Calle 100 # 7-20',
    'latitude' => 4.676,
    'longitude' => -74.048,
    'price_sale' => 800000000,
    'currency_sale' => 'COP',
    'price_rent' => 0,
    'currency_rent' => 'COP',
    'price_admin' => 0,
    'currency_admin' => 'COP',
    'admin_included' => false,
    'area' => 120,
    'area_built' => 110,
    'area_private' => 105,
    'area_lot' => 0,
    'bedrooms' => 3,
    'bathrooms' => 2,
    'parking' => 2,
    'condition' => 'used',
    'age' => 5,
    'agent_name' => 'Agente Demo',
    'agent_email' => 'agente@example.com',
    'agent_phone' => '+57 3000000000',
    'features' => ['balcon', 'ascensor'],
    'nearby' => ['parques'],
    'tags' => ['premium'],
    'media' => [
        'photos' => [
            ['url' => 'https://example.com/photo1.jpg', 'featured' => true, 'title' => 'fachada'],
        ],
        'videos' => [
            ['url' => 'https://example.com/video.mp4'],
        ],
        'brochure' => ['url' => 'https://example.com/brochure.pdf'],
        'tour_360' => [],
        'photos_360' => [],
    ],
]);

var_dump($response);
