<?php
declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $routes = [];
    public function __construct(private readonly Request $request) {}
    public function get(string $path, callable $handler): void { $this->map('GET', $path, $handler); }
    public function post(string $path, callable $handler): void { $this->map('POST', $path, $handler); }
    public function any(string $path, callable $handler): void { $this->map('*', $path, $handler); }
    private function map(string $method, string $path, callable $handler): void { $this->routes[$method . ' ' . $this->normalize($path)] = $handler; }
    public function dispatch(): Response|null
    {
        $key = $this->request->method() . ' ' . $this->request->path();
        $handler = $this->routes[$key] ?? $this->routes['* ' . $this->request->path()] ?? null;
        if (!$handler) return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        $result = $handler($this->request);
        return $result instanceof Response ? $result : null;
    }
    private function normalize(string $path): string { return '/' . trim($path, '/'); }
}
