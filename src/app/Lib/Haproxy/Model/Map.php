<?php
declare(strict_types=1);

namespace App\Lib\Haproxy\Model;

use Symfony\Component\VarDumper\VarDumper;

class Map {
    private string $path;
    private int $id;

    private array $map = [];

    public function __construct(string $path, int $id) {
        $this->path = $path;
        $this->id = $id;
    }

    public function getPath(): string {
        return $this->path;
    }

    public function getId(): int {
        return $this->id;
    }

    public function add(string $key, string $value) {
        $this->map[$key] = $value;
    }

    public function getMap(): array {
        return $this->map;
    }

    public function get(string $key): string {
        return $this->map[$key];
    }

    public function clear() {
        return $this->map = [];
    }

}
