<?php
// Update these with your MySQL details.
const DB_HOST = 'localhost';
const DB_NAME = 'buildmyspace';
const DB_USER = 'root';
const DB_PASS = '';
const CURRENCY = '₹';

function db(): PDO {
    static $pdo = null;
    if (!$pdo) {
        $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}

function clean_inputs(array $in): array {
    $clamp = fn($v, $min, $max) => max($min, min($max, (float)$v));
    return [
        'plot'     => $clamp($in['plot'] ?? 1200, 300, 20000),
        'coverage' => $clamp($in['coverage'] ?? 60, 30, 90),
        'floors'   => (int)$clamp($in['floors'] ?? 1, 1, 4),
        'quality'  => preg_replace('/[^a-z_]/', '', (string)($in['quality'] ?? 'standard')),
        'beds'     => (int)$clamp($in['beds'] ?? 2, 1, 8),
        'baths'    => (int)$clamp($in['baths'] ?? 2, 1, 8),
        'dining'   => !empty($in['dining']),
        'parking'  => !empty($in['parking']),
        'extras'   => array_values(array_filter(array_map(fn($c) => preg_replace('/[^a-z_]/', '', (string)$c), (array)($in['extras'] ?? [])))),
    ];
}

// Single source of truth for pricing: the browser asks this, and saving re-runs it on the server.
function calculate(array $in): array {
    $pdo = db();
    $in = clean_inputs($in);
    $builtUp = round($in['plot'] * $in['coverage'] / 100 * $in['floors']);

    $q = $pdo->prepare('SELECT * FROM quality_rates WHERE code = ?');
    $q->execute([$in['quality']]);
    $quality = $q->fetch() ?: $pdo->query('SELECT * FROM quality_rates ORDER BY rate_per_sqft LIMIT 1')->fetch();
    $base = $builtUp * $quality['rate_per_sqft'];

    $breakdown = [];
    foreach ($pdo->query('SELECT label, percent FROM cost_breakdown ORDER BY id') as $row) {
        $breakdown[] = ['label' => $row['label'], 'amount' => round($base * $row['percent'] / 100)];
    }

    $extras = [];
    $st = $pdo->prepare('SELECT * FROM extras WHERE code = ?');
    foreach ($in['extras'] as $code) {
        $st->execute([$code]);
        if ($e = $st->fetch()) {
            $extras[] = ['label' => $e['label'], 'amount' => round($e['type'] === 'per_sqft' ? $e['cost'] * $builtUp : $e['cost'])];
        }
    }
    $total = round($base) + array_sum(array_column($extras, 'amount'));

    return ['inputs' => $in, 'built_up' => $builtUp, 'quality' => $quality['label'],
            'rate' => (float)$quality['rate_per_sqft'], 'base' => round($base),
            'breakdown' => $breakdown, 'extras' => $extras, 'total' => $total];
}
