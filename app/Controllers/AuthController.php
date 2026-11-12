<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;

final class AuthController
{
    public function __construct(private readonly LegacyApplicationController $application) {}
    public function login(Request $request): void { $this->application->handle($request, null, 'login'); }
    public function register(Request $request): void { $this->application->handle($request, null, 'register'); }
    public function forgot(Request $request): void { $this->application->handle($request, null, 'forgot'); }
    public function reset(Request $request): void { $this->application->handle($request, null, 'reset'); }
}
