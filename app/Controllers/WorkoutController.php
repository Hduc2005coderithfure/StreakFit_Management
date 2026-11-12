<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;

final class WorkoutController
{
    public function __construct(private readonly LegacyApplicationController $application) {}
    public function index(Request $request): void { $this->application->handle($request, 'checkin'); }
    public function history(Request $request): void { $this->application->handle($request, 'history'); }
}
