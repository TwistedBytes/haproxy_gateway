<?php

namespace App\Http\Controllers;

use App\Lib\Haproxy\AdminInterface;
use App\Lib\Haproxy\Model\BackendServer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class HaproxyBackendServerController extends Controller {

    public function __construct() {
    }

    public function add(AdminInterface $haproxyadmin, string $backend, string $server, string $address, int $port) : \Illuminate\Http\JsonResponse {
        $bs = new BackendServer($backend, $server, $address, $port);
        $bs->setOptions($haproxyadmin->getBackendDefaultoptions());
        $result = $haproxyadmin->addServer($bs);
        return response()->json($result->toArray());
    }

    public function del(AdminInterface $haproxyadmin, string $backend, string $server) : \Illuminate\Http\JsonResponse {
        $bs = new BackendServer($backend, $server);
        $result = $haproxyadmin->deleteServer($bs);

        return response()->json($result->toArray());
    }


    public function enable(AdminInterface $haproxyadmin, string $backend, string $server) : \Illuminate\Http\JsonResponse {
        $bs = new BackendServer($backend, $server);
        $result = $haproxyadmin->enableServer($bs);

        return response()->json($result->toArray());
    }


    public function disable(AdminInterface $haproxyadmin, string $backend, string $server) : \Illuminate\Http\JsonResponse {
        $bs = new BackendServer($backend, $server);
        $result = $haproxyadmin->disableServer($bs);

        return response()->json($result->toArray());
    }

}
