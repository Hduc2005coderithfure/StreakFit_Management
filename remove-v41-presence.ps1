param([switch]$Preview)
$ErrorActionPreference = 'Stop'
$root = $PSScriptRoot
if (!(Test-Path (Join-Path $root 'public/index.php')) -or !(Test-Path (Join-Path $root 'app/Legacy/application.php'))) {
    throw 'Place this script in the StreakFit project root before running.'
}
$files = @(
    'app/Services/MemberPresenceService.php',
    'app/Controllers/Api/PresenceController.php',
    'app/Views/components/arena-presence-config.php',
    'database/migrations/2026_11_14_member_presence.sql',
    'tests/arena_presence.php',
    'tests/arena_presence.js'
)
foreach ($file in $files) {
    $path = Join-Path $root $file
    if (Test-Path -LiteralPath $path -PathType Leaf) {
        if ($Preview) { Write-Host "Would remove: $file" }
        else { Remove-Item -LiteralPath $path; Write-Host "Removed: $file" }
    }
}
Write-Host 'Finished. No database operations were performed.'
