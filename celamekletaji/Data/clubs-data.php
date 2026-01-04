<?php
// data/clubs-data.php
declare(strict_types=1);

/**
 * Central place for assets used in map/tooltips/modals.
 * Change this once if you move icons.
 */
const ASSET_BASE = 'assets/images/';

/**
 * Programs dictionary (key => label + icon)
 * Keep icon paths relative to site root or current page.
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
 * - Filters program codes to known keys
 * - HARD validates coords (range check) => invalid -> 0.0 so JS can skip safely
 */
$normalizeClub = static function(array $c) use ($programs): array {
    $c['id']      = (string)($c['id'] ?? '');
    $c['name']    = (string)($c['name'] ?? 'Klubs');
    $c['address'] = (string)($c['address'] ?? 'N/A');
    $c['church']  = (string)($c['church'] ?? 'N/A');

    $lat = isset($c['lat']) ? (float)$c['lat'] : 0.0;
    $lng = isset($c['lng']) ? (float)$c['lng'] : 0.0;

    // FUTURE-PROOF: coordinate sanity (Leaflet will crash on invalid LatLng)
    if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
        $lat = 0.0;
        $lng = 0.0;
    }

    $c['lat'] = $lat;
    $c['lng'] = $lng;

    $leader = $c['leader'] ?? [];
    $c['leader'] = [
        'name'  => (string)($leader['name']  ?? 'N/A'),
        'email' => (string)($leader['email'] ?? 'N/A'),
        'phone' => (string)($leader['phone'] ?? 'N/A'),
    ];

    $codes = $c['programs'] ?? [];
    if (!is_array($codes)) $codes = [];
    $codes = array_values(array_unique(array_map('strval', $codes)));
    $codes = array_values(array_filter($codes, static fn($code) => isset($programs[$code])));
    $c['programs'] = $codes;

    return $c;
};

/**
 * Clubs list.
 * NOTE:
 * - If address unknown => "N/A"
 * - If exact building coords unknown => use city coords (better than 0,0)
 */
$clubs = [

    // =========================================================
    // CĒSIS
    // =========================================================
    [
        'id' => 'cesis-vindsors',
        'name' => 'Cēsu klubs “Vindsors”',
        'address' => 'Zaķu iela 11, Cēsis',
        'church' => 'Cēsu draudze',
        'lat' => 57.3119,
        'lng' => 25.2746,
        'leader' => [
            'name'  => 'Beāte',
            'email' => 'N/A',
            'phone' => '+371 26190060',
        ],
        'programs' => ['CM'],
    ],
    [
        'id' => 'cesis-spoza-bulta-cm',
        'name' => 'Cēsu klubs “Spožā bulta” (CM)',
        'address' => 'N/A',
        'church' => 'Cēsu draudze',
        'lat' => 57.3119,
        'lng' => 25.2746,
        'leader' => [
            'name'  => 'Ernests Trucis',
            'email' => 'N/A',
            'phone' => 'N/A',
        ],
        'programs' => ['CM'],
    ],
    [
        'id' => 'cesis-spoza-bulta-pm',
        'name' => 'Cēsu klubs “Spožā bulta” (PM)',
        'address' => 'N/A',
        'church' => 'Cēsu draudze',
        'lat' => 57.3119,
        'lng' => 25.2746,
        'leader' => [
            'name'  => 'Lāsma Kaimiņa',
            'email' => 'N/A',
            'phone' => 'N/A',
        ],
        'programs' => ['PM'],
    ],

    // =========================================================
    // DAUGAVPILS
    // =========================================================
    [
        'id' => 'daugavpils-dinaburgas-cietoksnis',
        'name' => 'Daugavpils klubs “Dinaburgas cietoksnis”',
        'address' => 'Tautas iela 54, Daugavpils',
        'church' => 'Daugavpils draudze',
        'lat' => 55.8796,
        'lng' => 26.5364,
        'leader' => [
            'name'  => 'Deniss',
            'email' => 'N/A',
            'phone' => '+371 26706104',
        ],
        'programs' => ['CM'],
    ],

    // =========================================================
    // DOBELE
    // =========================================================
    [
        'id' => 'dobele',
        'name' => 'Dobeles klubs',
        'address' => 'N/A',
        'church' => 'Dobeles draudze',
        'lat' => 56.6250,
        'lng' => 23.2780,
        'leader' => [
            'name'  => 'Astrīda Prelgauska',
            'email' => 'N/A',
            'phone' => 'N/A',
        ],
        'programs' => ['CM'],
    ],

    // =========================================================
    // JELGAVA
    // =========================================================
    [
        'id' => 'jelgava',
        'name' => 'Jelgavas klubs',
        'address' => 'N/A',
        'church' => 'Jelgavas draudze',
        'lat' => 56.6500,
        'lng' => 23.7120,
        'leader' => [
            'name'  => 'Liene Pomilovska',
            'email' => 'N/A',
            'phone' => 'N/A',
        ],
        'programs' => ['CM'],
    ],

    // =========================================================
    // KĀRSAVA
    // =========================================================
    [
        'id' => 'karsava',
        'name' => 'Kārsavas klubs',
        'address' => 'N/A',
        'church' => 'Kārsavas draudze',
        'lat' => 56.7840,
        'lng' => 27.6880,
        'leader' => [
            'name'  => 'Liene Ģipsle',
            'email' => 'N/A',
            'phone' => 'N/A',
        ],
        'programs' => ['CM'],
    ],

    // =========================================================
    // LIEPĀJA
    // =========================================================
    [
        'id' => 'liepaja',
        'name' => 'Liepājas klubs',
        'address' => 'Ūliha iela 70, Liepāja',
        'church' => 'Liepājas draudze',
        'lat' => 56.50173,
        'lng' => 21.0027,
        'leader' => [
            'name'  => 'N/A',
            'email' => 'N/A',
            'phone' => 'N/A',
        ],
        'programs' => ['CM','PM'],
    ],

    // =========================================================
    // ALŪKSNE
    // =========================================================
    [
        'id' => 'aluksne',
        'name' => 'Alūksnes klubs',
        'address' => 'Dārza iela 2, Alūksne',
        'church' => 'Alūksnes draudze',
        'lat' => 57.421945,
        'lng' => 27.052038,
        'leader' => [
            'name'  => 'Miks Možeiko',
            'email' => 'N/A',
            'phone' => 'N/A',
        ],
        'programs' => ['CM'],
    ],

    // =========================================================
    // RĒZEKNE
    // =========================================================
    [
        'id' => 'rezekne',
        'name' => 'Rēzeknes klubs',
        'address' => 'Latgales iela 19, Rēzekne',
        'church' => 'Rēzeknes draudze',
        'lat' => 56.500025,
        'lng' => 27.330507,
        'leader' => [
            'name'  => 'Jeļena Gubko',
            'email' => 'N/A',
            'phone' => 'N/A',
        ],
        'programs' => ['CM'],
    ],

    // =========================================================
    // SAULKRASTI
    // =========================================================
    [
        'id' => 'saulkrasti',
        'name' => 'Saulkrastu klubs',
        'address' => 'Palejas iela 6a, Saulkrasti',
        'church' => 'Saulkrastu draudze',
        'lat' => 57.256431,
        'lng' => 24.416975,
        'leader' => [
            'name'  => 'Maija Rivīte (Liene Vaga)',
            'email' => 'N/A',
            'phone' => 'N/A',
        ],
        'programs' => ['CM'],
    ],

    // =========================================================
    // SMILTENE
    // =========================================================
    [
        'id' => 'smiltene',
        'name' => 'Smiltenes klubs',
        'address' => 'Dārza 32, Smiltene',
        'church' => 'Smiltenes draudze',
        'lat' => 57.4212164,
        'lng' => 25.9151198,
        'leader' => [
            'name'  => 'Ansis Avišāns',
            'email' => 'N/A',
            'phone' => 'N/A',
        ],
        'programs' => ['CM'],
    ],

    // =========================================================
    // TALSI
    // =========================================================
    [
        'id' => 'talsi',
        'name' => 'Talsu klubs',
        'address' => 'Krievraga 3, Talsi',
        'church' => 'Talsu draudze',
        'lat' => 57.248673,
        'lng' => 22.582966,
        'leader' => [
            'name'  => 'Ieva Truce',
            'email' => 'N/A',
            'phone' => 'N/A',
        ],
        'programs' => ['CM'],
    ],

    // =========================================================
    // VALKA  
    // =========================================================
    [
        'id' => 'valka',
        'name' => 'Valkas klubs',
        'address' => 'Beverīnas iela 1, Valka',
        'church' => 'Valkas draudze',
        'lat' => 57.772676,   // ✅ FIX: was 557.772676
        'lng' => 26.016849,
        'leader' => [
            'name'  => 'Miks Mežītis',
            'email' => 'N/A',
            'phone' => 'N/A',
        ],
        'programs' => ['CM'],
    ],

    // =========================================================
    // VALMIERA
    // =========================================================
    [
        'id' => 'valmiera',
        'name' => 'Valmieras klubs',
        'address' => 'Georga Apiņa 4, Valmiera',
        'church' => 'Valmieras draudze',
        'lat' => 57.538887,
        'lng' => 25.417734,
        'leader' => [
            'name'  => 'Renāte Spirģe',
            'email' => 'N/A',
            'phone' => 'N/A',
        ],
        'programs' => ['CM'],
    ],

    // =========================================================
    // RĪGA (grouped together)
    // =========================================================
    [
        'id' => 'riga-1-cm',
        'name' => 'Rīga 1 klubs (CM)',
        'address' => 'Baznīcas iela 12A, Rīga',
        'church' => 'Rīgas 1. draudze',
        'lat' => 56.956421,
        'lng' => 24.119842,
        'leader' => [
            'name'  => 'Zane Alberiņa',
            'email' => 'N/A',
            'phone' => 'N/A',
        ],
        'programs' => ['CM'],
    ],
    [
        'id' => 'riga-1-pm',
        'name' => 'Rīga 1 klubs (PM)',
        'address' => 'Baznīcas iela 12A, Rīga',
        'church' => 'Rīgas 1. draudze',
        'lat' => 56.956421,
        'lng' => 24.119842,
        'leader' => [
            'name'  => 'Denīze Kūma',
            'email' => 'N/A',
            'phone' => 'N/A',
        ],
        'programs' => ['PM'],
    ],
    [
        'id' => 'riga-7',
        'name' => 'Rīga 7 klubs',
        'address' => 'Ģimnastikas iela 43, Rīga',
        'church' => 'Rīgas 7. draudze',
        'lat' => 56.919701,
        'lng' => 24.074512,
        'leader' => [
            'name'  => 'N/A',
            'email' => 'N/A',
            'phone' => 'N/A',
        ],
        'programs' => ['CM','PM','MG','V','J'],
    ],
    [
        'id' => 'riga-iskatel',
        'name' => 'Rīga klubs “Искатель”',
        'address' => 'Baznīcas iela 12A, Rīga',
        'church' => 'Rīga 4. draudze',
        'lat' => 56.956421,
        'lng' => 24.119842,
        'leader' => [
            'name'  => 'Inga Lokšinska',
            'email' => 'N/A',
            'phone' => 'N/A',
        ],
        'programs' => ['CM','PM','MG'],
    ],
];

/* ---------- Normalize + sanity checks ---------- */

$clubs = array_map($normalizeClub, $clubs);

// Prevent duplicate IDs (saves hours of “why is marker wrong?”)
$ids = array_map(static fn($c) => $c['id'], $clubs);
$dupes = array_diff_assoc($ids, array_unique($ids));
if (!empty($dupes)) {
    trigger_error('Duplicate club id(s): ' . implode(', ', array_unique($dupes)), E_USER_WARNING);
}

/**
 * Sorting:
 * Group by church (so Rīga together), then club name, then program rank.
 */
$sortKey = static function(string $s): string {
    $s = mb_strtolower($s, 'UTF-8');
    $s = str_replace(['draudze', 'draudze ', '.', '“', '”', '"'], '', $s);
    $s = preg_replace('~\s+~u', ' ', trim($s)) ?? trim($s);
    return $s;
};

$programRank = static function(array $programs): int {
    $order = ['CM' => 1, 'PM' => 2, 'MG' => 3, 'V' => 4, 'J' => 5];
    $min = 999;
    foreach ($programs as $p) {
        $min = min($min, $order[$p] ?? 999);
    }
    return $min;
};

usort($clubs, static function(array $a, array $b) use ($sortKey, $programRank): int {
    $ka = $sortKey($a['church'] ?? '') . '|' . $sortKey($a['name'] ?? '');
    $kb = $sortKey($b['church'] ?? '') . '|' . $sortKey($b['name'] ?? '');

    if ($ka !== $kb) return $ka <=> $kb;

    $ra = $programRank($a['programs'] ?? []);
    $rb = $programRank($b['programs'] ?? []);
    if ($ra !== $rb) return $ra <=> $rb;

    return ($a['id'] ?? '') <=> ($b['id'] ?? '');
});
