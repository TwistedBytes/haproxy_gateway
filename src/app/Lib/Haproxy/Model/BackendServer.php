<?php

namespace App\Lib\Haproxy\Model;

use Symfony\Component\VarDumper\VarDumper;

class BackendServer {

    private string $backend;
    private string $server;
    private string $address;
    private int $port;

    private string $options = '';

    public function __construct(string $backend, string $server, string $address, int $port) {
        $this->backend = $backend;
        $this->server = $server;
        $this->address = $address;
        $this->port = $port;
    }

    public function getBackend(): string {
        return $this->backend;
    }

    public function getServer(): string {
        return $this->server;
    }

    public function getAddress(): string {
        return $this->address;
    }

    public function getPort(): int {
        return $this->port;
    }

    public function getOptions(): string {
        return $this->options;
    }

    public function setOptions(string $options): void {
        $this->options = $options;
    }

    public function __toString(): string {
        return $this->backend . '/' . $this->server;
    }

    public function equals(BackendServer $server, bool $nameOnly = false): bool {
        return $this->backend === $server->getBackend() &&
            $this->server === $server->getServer() &&
            ($nameOnly || (
                    $this->address === $server->getAddress() &&
                    $this->port === $server->getPort()
                ));
    }

}
