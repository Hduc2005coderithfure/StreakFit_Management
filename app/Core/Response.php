<?php
declare(strict_types=1);

namespace App\Core;

final class Response
{
    public function __construct(private string $content = '', private int $status = 200, private array $headers = []) {}
    public function send(): never
    {
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) header($name . ': ' . $value);
        echo $this->content;
        exit;
    }
    public static function redirect(string $location, int $status = 303): self { return new self('', $status, ['Location' => $location]); }
    public static function json(array $data, int $status = 200): self
    {
        return new self((string)json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $status, ['Content-Type' => 'application/json; charset=utf-8']);
    }
}
