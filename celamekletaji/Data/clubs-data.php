<?php
// Data/clubs-data.php
declare(strict_types=1);

/**
 * Central place for assets used in map/tooltips/modals.
 * Change this once if you move icons.
 */
const ASSET_BASE = 'assets/images/';

/**
 * Programs dictionary (key => label + icon)
 * Keep icon paths relative to site root or current page (your current setup uses relative paths).
 */
$programs = [
    'CM' => ['label' => 'Ceļa meklētāji (10–15)',        'icon' => ASSET_BASE . 'CM.png'],
    'PM' => ['label' => 'Piedzīvojumu meklētāji (5–9)',  'icon' => ASSET_BASE . 'PM.png'],
    'MG' => ['label' => 'Mastergaidi (vadītāji)',        'icon' => ASSET_BASE . 'MG.png'],
    'V'  => ['label' => 'Vēstneši (16+)',                'icon' => ASSET_BASE . 'vestnesi.png'],
    'J'  => ['label' => 'Mazie jēriņi (4+)',             'icon' => ASSET_BASE . 'jerini.png'],
];

/**
 * Helper: normalize a club row so JS always gets consistent shape.
 * - Ensures leader exists
 * - Casts lat/lng to float
 * - Removes duplicate program codes
 */
$normalizeClub = static function(array $c) use ($programs): array {
    $c['id']      = (string)($c['id'] ?? '');
    $c['name']    = (string)($c['name'] ?? 'Klubs');
    $c['address'] = (string)($c['address'] ?? '');
    $c['church']  = (string)($c['church'] ?? '');

    $c['lat'] = isset($c['lat']) ? (float)$c['lat'] : 0.0;
    $c['lng'] = isset($c['lng']) ? (float)$c['lng'] : 0.0;

    // Leader always present
    $leader = $c['leader'] ?? [];
    $c['leader'] = [
        'name'  => (string)($leader['name'] ?? ''),
        'email' => (string)($leader['email'] ?? ''),
        'phone' => (string)($leader['phone'] ?? ''),
    ];

    // Programs: unique + only known codes (prevents typos from breaking UI)
    $codes = $c['programs'] ?? [];
    if (!is_array($codes)) $codes = [];
    $codes = array_values(array_unique(array_map('strval', $codes)));
    $codes = array_values(array_filter($codes, static fn($code) => isset($programs[$code])));
    $c['programs'] = $codes;

    return $c;
};

/**
 * Clubs list.
 * NOTE: coordinates are examples; replace with accurate lat/lng.
 */
$clubs = [
    [
        'id' => 'riga-7',
        'name' => 'Rīga CM “7”',
        'address' => 'Rīga, (ieliec precīzu adresi)',
        'church' => 'Rīgas draudze',
        'lat' => 56.9496,
        'lng' => 24.1052,
        'leader' => [
            'name' => 'Vārds Uzvārds',
            'email' => 'vaditajs.riga@example.lv',
            'phone' => '+371 29 000 000',
        ],
        'programs' => ['CM','PM','MG','V','J'],
    ],
    [
        'id' => 'liepaja',
        'name' => 'Liepāja Klubs',
        'address' => 'Liepāja, (ieliec precīzu adresi)',
        'church' => 'Liepājas draudze',
        'lat' => 56.5047,
        'lng' => 21.0108,
        'leader' => [
            'name' => 'Vārds Uzvārds',
            'email' => 'vaditajs.liepaja@example.lv',
            'phone' => '+371 29 000 001',
        ],
        'programs' => ['CM','PM'],
    ],
    [
        'id' => 'cesis',
        'name' => 'Cēsis CM',
        'address' => 'Cēsis, (ieliec precīzu adresi)',
        'church' => 'Cēsu draudze',
        'lat' => 57.3119,
        'lng' => 25.2746,
        'leader' => [
            'name' => 'Vārds Uzvārds',
            'email' => 'vaditajs.cesis@example.lv',
            'phone' => '+371 29 000 002',
        ],
        'programs' => ['CM'],
    ],
    [
        'id' => 'valmiera',
        'name' => 'Valmiera CM',
        'address' => 'Valmiera, (ieliec precīzu adresi)',
        'church' => 'Valmieras draudze',
        'lat' => 57.5385,
        'lng' => 25.4264,
        'leader' => [
            'name' => 'Vārds Uzvārds',
            'email' => 'vaditajs.valmiera@example.lv',
            'phone' => '+371 29 000 003',
        ],
        'programs' => ['CM','PM'],
    ],
    [
        'id' => 'daugavpils',
        'name' => 'Daugavpils CM',
        'address' => 'Daugavpils, (ieliec precīzu adresi)',
        'church' => 'Daugavpils draudze',
        'lat' => 55.8747,
        'lng' => 26.5362,
        'leader' => [
            'name' => 'Vārds Uzvārds',
            'email' => 'vaditajs.daugavpils@example.lv',
            'phone' => '+371 29 000 004',
        ],
        'programs' => ['CM','V'],
    ],
];

/* ---------- Normalize + sanity checks (dev-friendly) ---------- */

// Normalize everything (consistent structure for JS)
$clubs = array_map($normalizeClub, $clubs);

// Prevent duplicate IDs (saves hours of “why is marker wrong?”)
$ids = array_map(static fn($c) => $c['id'], $clubs);
$dupes = array_diff_assoc($ids, array_unique($ids));
if (!empty($dupes)) {
    // In production you might want to log instead of die()
    trigger_error('Duplicate club id(s): ' . implode(', ', array_unique($dupes)), E_USER_WARNING);
}
