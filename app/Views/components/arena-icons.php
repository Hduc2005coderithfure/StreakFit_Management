<?php
$arenaIcons = [
    'activity' => '<path d="M3 18h18M5 14V8m7 6V4m7 10v-4"/>',
    'grid' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
    'people' => '<circle cx="9" cy="8" r="3"/><path d="M3 20v-2a6 6 0 0 1 12 0v2M16 5a3 3 0 0 1 0 6m2 3a5 5 0 0 1 3 4v2"/>',
    'rank' => '<path d="m12 2 8 4v6c0 5-8 10-8 10S4 17 4 12V6z"/><path d="m12 6 3 5-3 5-3-5z"/>',
    'trophy' => '<path d="M8 3h8v8a4 4 0 0 1-8 0zM8 5H4v3a4 4 0 0 0 4 4m8-7h4v3a4 4 0 0 1-4 4m-4 3v5m-4 1h8"/>',
    'scale' => '<path d="M12 3v18M4 7h16M5 7l-3 7h6zm14 0-3 7h6zM8 21h8"/>',
    'plus' => '<path d="M12 5v14M5 12h14"/>',
    'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>',
    'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/>',
    'heart' => '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0l-1 1-1-1a5.5 5.5 0 0 0-7.8 7.8l8.8 8.6 8.8-8.6a5.5 5.5 0 0 0 0-7.8z"/>',
    'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4m8-4v4m-8 8h2m4 0h2"/>',
    'wallet' => '<rect x="3" y="5" width="18" height="15" rx="3"/><path d="M21 10h-5a3 3 0 0 0 0 6h5m-5-3h.01"/>',
    'lock' => '<rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3m-4 4v3"/>',
    'spark' => '<path d="m12 2 2.5 7.5L22 12l-7.5 2.5L12 22l-2.5-7.5L2 12l7.5-2.5z"/>',
    'bike' => '<circle cx="5.5" cy="17.5" r="4"/><circle cx="18.5" cy="17.5" r="4"/><path d="m5.5 17.5 5-9 5 9h3m-8-9H8m3 0 4-4h3m-3 0 3.5 13"/>',
    'history' => '<path d="M3 11a9 9 0 1 1 2 7M3 4v7h7m2-4v5l4 2"/>',
];
$arenaIcon = static fn(string $name): string => '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($arenaIcons[$name] ?? $arenaIcons['grid']) . '</svg>';
