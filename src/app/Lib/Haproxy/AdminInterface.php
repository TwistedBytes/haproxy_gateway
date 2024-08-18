<?php
declare(strict_types=1);

namespace App\Lib\Haproxy;

use App\Lib\Haproxy\Exceptions\HaproxyException;
use App\Lib\Haproxy\Model\ActionResult;
use App\Lib\Haproxy\Model\BackendServer;
use App\Lib\Haproxy\Model\Map;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AdminInterface {

    private mixed $socket = null;
    private string $connection_string;

    private string $backend_defaultoptions = 'check';
    /**
     * @var mixed|string
     */
    private bool $skipSaveState = false;

    private string $backend_state_path;

    public function __construct(
        string $connection_string,
        string $backend_state_path = '/etc/haproxy/backendstate',
    ) {
        $this->connection_string = $connection_string;
        $this->backend_state_path = $backend_state_path;

        if (!is_dir($this->backend_state_path)) {
            mkdir($this->backend_state_path, 0750, true);
        }

    }

    public function socket(string $command) {
        return $this->executeSocket($command);
    }

    public function getBackends(): array {
        $result = $this->socket('show backend');
        $separator = "\n";
        $line = strtok($result, $separator);

        $backends = [];
        while ($line !== false) {
            if (!Str::startsWith($line, '#') && $line !== '') {
                $backends[] = $line;
            }
            $line = strtok($separator);
        }

        return $backends;
    }

    /**
     * return the backend servers, if backend is empty, return all servers with backend prefixed
     * if backend is not empty, return only the servers for that backend
     *
     * @param $backend
     * @return array{BackendServer}
     */
    public function getServers($backend = ''): array {
        $result = $this->socket("show servers conn {$backend}");
        $separator = "\n";
        $line = strtok($result, $separator);
        $backends = [];

        while ($line !== false) {
            if (!Str::startsWith($line, '#') && $line !== '') {
                $lineParts = explode(' ', $line);
                $serverParts = explode('/', $lineParts[0]);
                $backends[] = new BackendServer($serverParts[0], $serverParts[1], $lineParts[2], (int)$lineParts[3]);

            }
            $line = strtok($separator);
        }

        return $backends;
    }

    public function existsServer(BackendServer $server, bool $nameOnly = false): bool {
        $servers = $this->getServers($server->getBackend());
        /** @var BackendServer $s */
        foreach ($servers as $s) {
            if ($s->equals($server, $nameOnly)) {
                return true;
            }
        }
        return false;
    }

    public function enableServer(BackendServer $server) : ActionResult {
        $result =[];
        $result[] = $this->socket(
            sprintf('enable health %s/%s', $server->getBackend(), $server->getServer())
            .';'.
            sprintf('enable server %s/%s', $server->getBackend(), $server->getServer())
        );

        return new ActionResult(true, messages: $result);
    }

    public function disableServer(BackendServer $server) : ActionResult {
        // set server sp-api3-backends/sp-api-backend-16 state maint
        // wait 5 seconds
        // shutdown sessions server sp-api3-backends/sp-api-backend-16
        // del server sp-api3-backends/sp-api-backend-16

        $result =[];
        $result[] = $this->socket(sprintf('set server %s/%s state maint', $server->getBackend(), $server->getServer()));
        sleep(1);
        $result[] = $this->socket(sprintf('shutdown sessions server %s/%s', $server->getBackend(), $server->getServer()));
        // TODO: better check if server does not have any sessions.

        return new ActionResult(true, messages: $result);
    }

    public function addServer(BackendServer $server): ActionResult {
        if ($this->existsServer($server)) {
            $this->enableServer($server);
            return new ActionResult(true, "Server {$server} already exists");
        }

        // add server sp-api3-backends/sp-api-backend-16 10.4.6.136:80 check inter 5s downinter 15s rise 3 fall 2 slowstart 60s maxconn 2 maxqueue 128 weight 100
        $result = $this->socket(sprintf('add server %s/%s %s:%u %s', $server->getBackend(), $server->getServer(), $server->getAddress(), $server->getPort(), $server->getOptions()));
        $this->enableServer($server);

        $this->saveBackendState($server);

        return new ActionResult(true, $result);
    }

    public function deleteServer(BackendServer $server): ActionResult {
        if ($this->existsServer($server, true)) {
            $this->disableServer($server);
            $result = $this->socket(sprintf('del server  %s/%s', $server->getBackend(), $server->getServer()));

            $this->removeBackendState($server);

            return new ActionResult(true, $result);
        } else {
            return new ActionResult(true, "Server {$server} does not exists");
        }
    }

    public function getMaps(): array {
        $result = $this->socket('show map');
        $separator = "\n";
        $line = strtok($result, $separator);
        $maps = [];
        while ($line !== false) {
            if (!Str::startsWith($line, '#') && $line !== '') {
                $lineParts = explode(' ', $line);
                $path = trim($lineParts[1], '()');

                $maps[] = new Map($path, (int)$lineParts[0]);
            }
            $line = strtok($separator);
        }
        return $maps;
    }

    public function getMap(string $basename): Map {
        $maps = $this->getMaps();
        foreach ($maps as $map) {
            if (Str::endsWith($map->getPath(), $basename)) {
                return $map;
            }
        }
        throw new HaproxyException("Map {$basename} not found");
    }

    public function fillMap(Map $map): Map {
        $result = $this->socket('show map ' . $map->getPath());
        if (trim($result) === '') {
            Log::info("Empty map {$map->getPath()}");
            $map->clear();
            return $map;
        }
        if (Str::startsWith($result, 'Unknown keyword')) {
            Log::info("Unknown map {$map->getPath()}");
            return $map;
        }

        $separator = "\n";
        $line = strtok($result, $separator);

        $map->clear();
        while ($line !== false) {
            if (!Str::startsWith($line, '#') && $line !== '') {
                $lineParts = explode(' ', $line);
                $map->add($lineParts[1], $lineParts[2]);
            }
            $line = strtok($separator);
        }
        return $map;
    }

    /**
     * Util function, do not use in normal. usage
     *
     * @param Map $map
     * @return ActionResult
     */
    public function dedupMap(Map $map): ActionResult {
        $this->socket(sprintf('clear map %s', $map->getPath()));
        foreach ($map->getMap() as $key => $value) {
            $this->addToMap($map, $key, $value, true);
        }
        $this->saveMapState($map);

        return new ActionResult(true, 'Map deduped');
    }

    public function addToMap(Map $map, string $key, string $value, bool $skipSaveState = false): ActionResult {
        $result = $this->socket(sprintf('add map %s %s %s', $map->getPath(), $key, $value));
        $skipSaveState || $this->saveMapState($map);
        return new ActionResult(trim($result) === '', $result);
    }

    public function delFromMap(Map $map, string $key, bool $skipSaveState = false): ActionResult {
        $result = $this->socket(sprintf('del map %s %s', $map->getPath(), $key));
        $skipSaveState || $this->saveMapState($map);
        return new ActionResult(trim($result) === '', $result);
    }

    private function saveMapState(Map $map): void {
        $path = $map->getPath();
        if (is_file($path)) {
            $file = new \SplFileObject($path, 'w');
            if ($file->isWritable()) {
                $this->fillMap($map);
                foreach ($map->getMap() as $key => $value) {
                    $file->fwrite(sprintf("%s %s\n", $key, $value));
                }
            } else {
                Log::error("Could not write map to file {$path}");
            }
        } else {
            Log::error("Could not write map to file {$path}, does not exist.");
        }

        $file = null;
    }

    private function saveBackendState(BackendServer $server): void {
        if ($this->skipSaveState) {
            return;
        }

        Log::debug("Saving server {$server->getBackend()}/{$server->getServer()}");
        $backend_path = sprintf('%s/%s', $this->backend_state_path, $server->getBackend());
        if (!is_dir($backend_path)) {
            mkdir($backend_path, 0750, true);
        }
        file_put_contents(
            sprintf('%s/%s', $backend_path, $server->getServer()),
            sprintf('%s,%s,%s,%u,%s', $server->getBackend(), $server->getServer(), $server->getAddress(), $server->getPort(), $server->getOptions())
        );
        Log::debug("Saved server {$server->getBackend()}/{$server->getServer()}");
    }

    private function removeBackendState(BackendServer $server): void {
        Log::debug("Removing server {$server->getBackend()}/{$server->getServer()}");

        $backend_path = sprintf('%s/%s', $this->backend_state_path, $server->getBackend());
        $backend_file = sprintf('%s/%s', $backend_path, $server->getServer());
        is_file($backend_file) &&
        unlink($backend_file);

        Log::debug("Removed server {$server->getBackend()}/{$server->getServer()}");
    }

    public function getStateBackends(): array {
        return Arr::map(glob($this->backend_state_path . '/*', GLOB_ONLYDIR),
            function (string $value, string $key) {
                return basename($value);
            }
        );
    }

    public function loadBackendStates(array $backends): void {
        foreach ($backends as $backend) {
            $this->loadBackendState($backend);
        }
    }

    public function loadBackendState(string $backend): void {
        $backend_path = sprintf('%s/%s', $this->backend_state_path, $backend);
        $this->skipSaveState = true;
        $serversLoaded = 0;

        Log::debug("Loading servers from {$backend_path}", ['backend' => $backend]);
        foreach (glob("$backend_path/*") as $filename) {
            $lineParts = explode(',', file_get_contents($filename));
            $backend = new BackendServer($lineParts[0], $lineParts[1], $lineParts[2], (int)$lineParts[3]);
            $backend->setOptions($lineParts[4]);

            $this->addServer($backend);
            $serversLoaded++;
            Log::debug("Loaded server", ['server' => "{$backend->getBackend()}/{$backend->getServer()}"]);
        }
        Log::debug("Servers loaded: {$serversLoaded}");
        $this->skipSaveState = false;
    }

    protected function executeSocket(string $command): string {
        if ($this->socket === null) {
            $this->openSocket();
        }
        Log::debug("haproxy socket command: > {$command}");
        fwrite($this->socket, $command . "\n");
        $response = '';
        while (!feof($this->socket)) {
            $response .= fgets($this->socket, 1024);
        }
        $this->closeSocket();
        Log::debug('haproxy socket command: < ' . trim($response));

        return $response;
    }


    protected function openSocket() {
        if (strpos($this->connection_string, ':')) {
            // TCP Socket, @ to hide warnings.
            $this->socket = stream_socket_client('tcp://' . $this->connection_string, $errorno, $errorstr);
        } else if (filetype(realpath($this->connection_string)) == 'socket') {
            // UNIX Domain Socket, @ to hide warnings.
            $this->socket = stream_socket_client('unix://' . realpath($this->connection_string), $errorno, $errorstr);
        } else {
            throw new HaproxyException("Could not open a connection to \"$this->connection_string\": the connection string is invalid");
        }
        if (!$this->socket) {
            throw new HaproxyException("Could not open a connection to \"$this->connection_string\": $errorstr ($errorno)");
        }
    }

    private function closeSocket() {
        if ($this->socket) {
            fwrite($this->socket, "quit\n");
            fclose($this->socket);
            $this->socket = null;
        }
    }

    function __destruct() {
        $this->closeSocket();
    }

    public function getBackendDefaultoptions(): string {
        return $this->backend_defaultoptions;
    }

    public function setBackendDefaultoptions(string $backend_defaultoptions): void {
        $this->backend_defaultoptions = $backend_defaultoptions;
    }
}
