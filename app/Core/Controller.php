<?php
declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected function render(string $view, array $data = []): Response
    {
        $file = dirname(__DIR__) . '/Views/' . ltrim($view, '/') . '.php';
        if (!is_file($file)) return new Response('View not found', 500);
        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        return new Response((string)ob_get_clean());
    }
    protected function redirect(string $path): Response { return Response::redirect(url($path)); }
}
