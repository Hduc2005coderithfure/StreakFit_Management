<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;

/** Temporary compatibility adapter while workflows are migrated into the feature controllers and services. */
final class LegacyApplicationController
{
    public function handle(Request $request, ?string $tab = null, ?string $mode = null): void
    {
        if ($tab !== null) $_GET['tab'] = $tab;
        if ($mode !== null) $_GET['mode'] = $mode;
        require dirname(__DIR__, 2) . '/app/Legacy/application.php';
    }
}
