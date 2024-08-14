<?php

namespace App\Lib\Haproxy\Model;

class ActionResult {

    private bool $succes;
    private string $message;

    public function __construct(bool $succes, string $message) {
        $this->succes = $succes;
        $this->message = $message;
    }

    public function isSucces(): bool {
        return $this->succes;
    }

    public function getMessage(): string {
        return trim($this->message);
    }

    public function toArray(): array {
        return [
            'succes' => $this->succes,
            'message' => trim($this->message),
        ];
    }
}
