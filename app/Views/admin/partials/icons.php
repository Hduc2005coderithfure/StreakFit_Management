<?php
$adminIcon = static function (string $name): string {
    $paths = [
        'overview' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        'members' => '<circle cx="9" cy="8" r="3"/><path d="M3 21v-2a6 6 0 0 1 12 0v2M17 5a3 3 0 0 1 0 6m2 10v-2a6 6 0 0 0-2-4"/>',
        'reviews' => '<path d="m12 3 8 3v6c0 5-8 9-8 9s-8-4-8-9V6z"/><path d="m8 12 3 3 5-6"/>',
        'fund' => '<rect x="3" y="5" width="18" height="15" rx="3"/><path d="M3 8V6a3 3 0 0 1 3-3h11M21 11h-6v5h6m-3-2.5h.01"/>',
        'reports' => '<path d="M4 3v18h17M8 16v-5m5 5V7m5 9V4"/>',
        'settings' => '<path d="m9 3-.5 3-2 1-3-.5-2 4 2.5 2v2L2 17l2 4 3-.5 2 1 .5 2h5l.5-2 2-1 3 .5 2-4-2.5-2v-2l2.5-2-2-4-3 .5-2-1L14 3z" transform="translate(1 -1) scale(.9)"/><circle cx="12" cy="12" r="3"/>',
        'permissions' => '<circle cx="8" cy="15" r="5"/><path d="m12 11 9-9m-4 4 3 3m-6-3 3 3"/>',
        'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>',
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'activity' => '<path d="M3 12h4l3-8 4 16 3-8h4"/>',
        'check' => '<path d="m5 12 4 4L19 6"/>',
        'alert' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v6m0 4h.01"/>',
        'arrow' => '<path d="M5 12h14m-5-5 5 5-5 5"/>',
        'logout' => '<path d="M9 3H4v18h5m5-14 5 5-5 5M8 12h12"/>',
    ];
    return '<svg class="admin-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($paths[$name] ?? $paths['overview']) . '</svg>';
};
