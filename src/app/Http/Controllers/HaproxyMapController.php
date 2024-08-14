<?php

namespace App\Http\Controllers;

use App\Lib\Haproxy\AdminInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class HaproxyMapController extends Controller {

    public function __construct() {
    }

    public function add(AdminInterface $haproxyadmin, string $basename, string $ip, string $servername) : \Illuminate\Http\JsonResponse {
        $map = $haproxyadmin->getMap($basename);
        $ip = Str::replace('-', '/', $ip);
        $result = $haproxyadmin->addToMap($map, $ip, $servername);
        return response()->json($result->toArray());
    }

    public function addthisip(AdminInterface $haproxyadmin, Request $request, string $basename, string $servername) : \Illuminate\Http\JsonResponse {
        $ip = $request->ip();
        return $this->add($haproxyadmin, $basename, $ip, $servername);
    }
    public function delthisip(AdminInterface $haproxyadmin, Request $request, string $basename) : \Illuminate\Http\JsonResponse {
        $ip = $request->ip();
        return $this->del($haproxyadmin, $basename, $ip);
    }

    public function del(AdminInterface $haproxyadmin, string $basename, string $ip) : \Illuminate\Http\JsonResponse {
        $map = $haproxyadmin->getMap($basename);
        $ip = Str::replace('-', '/', $ip);
        $result = $haproxyadmin->delFromMap($map, $ip);
        return response()->json($result->toArray());
    }

}
