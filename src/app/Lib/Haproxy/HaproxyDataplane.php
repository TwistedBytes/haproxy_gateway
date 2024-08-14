<?php
declare(strict_types=1);

namespace App\Lib\Haproxy;

class HaproxyDataplane {

    private string $connection_string;
    private \GuzzleHttp\Client $client;

    public function __construct() {
        $this->client = new \GuzzleHttp\Client([
            // Base URI is used with relative requests
            'base_uri' => 'https://haproxy-dataplaneapi.tbdocker.xyz:8443/',
            // You can set any number of default request options.
            'timeout' => 2.0,
            //'debug' => true,
            'auth' => ['admin', 'adminpwd'],
        ]);
    }

    public function request() {
        $requestOptions = [
            'query' => [
                'backend' => 'backend_test',
            ],
        ];

        //$response = $client->get('/v2/info');

        $response = $this->client->get('/v2/services/haproxy/configuration/servers', $requestOptions);


        return $response;
    }

    public function request2() {
        $requestOptions = [
            'query' => [
                'backend' => 'backend_test',
                'version' => $this->getConfigVersion(),
            ],
        ];

        //$response = $client->get('/v2/info');

        $serverTemplate = [
            'name' => 'sp-api-backend-17',
            'address' => '192.168.34.17',
            'port' => 80,
        ];

        $response = $this->client->post('/v2/services/haproxy/configuration/servers', [
            'query' => $requestOptions['query'],
            'json' => $serverTemplate,
        ]);


        return $response;
    }

    public function getConfigVersion(): int {
        $response = $this->client->get('/v2/services/haproxy/configuration/version');
        return (int) trim((string)$response->getBody());
    }

}
