<?php
declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Response;
use App\Core\Session;

final class AuthMiddleware
{
    public function handle(callable $next): mixed
    {
        if (Session::userId() < 1) return Response::redirect(url('login'));
        return $next();
    }
}
