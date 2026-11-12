<?php
declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Request;
use App\Core\Response;

final class CsrfMiddleware
{
    public function handle(Request $request, callable $next): mixed
    {
        if ($request->method() !== 'POST') return $next();
        $token = (string)$request->input('_csrf', '');
        $sessionToken = (string)($_SESSION['_csrf'] ?? $_SESSION['sf_csrf'] ?? '');
        if ($token === '' || $sessionToken === '' || !hash_equals($sessionToken, $token)) return new Response('Phiên đã hết hạn. Hãy tải lại trang.', 419);
        return $next();
    }
}
