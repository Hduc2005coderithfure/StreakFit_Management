<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;

final class HomeController
{
    public function __construct(private readonly LegacyApplicationController $application) {}
    public function index(Request $request): void { $this->application->handle($request, is_string($request->query('tab')) ? (string)$request->query('tab') : null); }
}
