<?php

namespace App\Http\Controllers;

use App\Lib\Haproxy\AdminInterface;
use App\Lib\Haproxy\HaproxyDataplane;
use App\Lib\Haproxy\Model\BackendServer;
use Symfony\Component\VarDumper\VarDumper;

class TestController extends Controller {

    public function __construct() {
    }

    public function index(AdminInterface $haproxyAdminInterface) {
        $out[] = "";

        # $out .= $stats->dumpServiceTree();

        //$out[] = $exec->execute(new AddServer('backend_test', 'sp-api-backend-17', '10.4.6.138:80', 'check'));
        # $out[] = nl2br($haproxyAdminInterface->socket('show backend'));
        # $out[] = nl2br($haproxyAdminInterface->socket('show servers conn'));

        $backends = $haproxyAdminInterface->getBackends();

        $servers = $haproxyAdminInterface->getServers('backend_test');
        $servers = $haproxyAdminInterface->getServers();
        VarDumper::dump($servers);

        $bs16 = new BackendServer('backend_test', 'sp-api-backend-16', '192.168.33.16', 80);
        $bs17 = new BackendServer('backend_test', 'sp-api-backend-17', '192.168.33.17', 80);
        $bs18 = new BackendServer('backend_test', 'sp-api-backend-18', '192.168.33.18', 80);

        $bs16->setOptions('check');
        $bs17->setOptions('check');
        $bs18->setOptions('check');

        //$result = $haproxyAdminInterface->addServer($bs18);
        //$result = $haproxyAdminInterface->deleteServer($bs16);

        $result = $haproxyAdminInterface->getMaps();
        VarDumper::dump($result);
        $map = $haproxyAdminInterface->fillMap($result[0]);
        VarDumper::dump($map);

        $result = $haproxyAdminInterface->addToMap($map, '10.10.4.2/32', 'ok');

        $result = $haproxyAdminInterface->addToMap($map, '2a06:6942:0:2:663a:7244:c6d0:dda6/64', '2a06:6942:0:2:663a:7244:c6d0:dda6/64');

        VarDumper::dump($result);

        $result = $haproxyAdminInterface->delFromMap($map, '10.89.3.26/32');
        VarDumper::dump($result);

        $haproxyAdminInterface->dedupMap($map);


        return '';

    }

    public function loadState(){
        $haproxyAdminInterface->loadServerState(['backend_test']);
    }

    public function testDataplane() {
        $HaDP = new HaproxyDataplane();
        $result = $HaDP->request2();
        //$result = $HaDP->getConfigVersion();

        VarDumper::dump($result);
        VarDumper::dump(json_decode($result->getBody()));

    }
}
