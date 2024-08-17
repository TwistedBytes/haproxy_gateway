<?php

namespace App\Lib\Haproxy\Model;

class ActionResult {

    private bool $succes;
    private string $message;
    private array $messages;

    public function __construct(bool $succes, string $message=null, array $messages = []) {
        $this->succes = $succes;
        $this->message = $message;
        $this->messages = $messages;
    }

    public function isSucces(): bool {
        return $this->succes;
    }

    public function getMessage(): string {
        return trim($this->message);
    }

    public function toArray(): array {
        $arr = [
            'succes' => $this->succes,
        ];

        if ($this->message) {
            $arr['message'] = trim($this->message);
        }

        if ($this->messages) {
            $arr['messages'] = $this->messages;
        }

        return $arr;
    }
}
