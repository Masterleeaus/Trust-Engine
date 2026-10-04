<?php

declare(strict_types=1);

if (PHP_VERSION_ID < 80000) {
    fwrite(STDERR, "Trust signal evaluation requires PHP 8.0 or newer." . PHP_EOL);
    exit(2);
}

$root = dirname(__DIR__);
$scenarioPath = $root . '/evaluations/trust-signal/scenarios.json';
$scenarioDocument = json_decode((string) file_get_contents($scenarioPath), true, 512, JSON_THROW_ON_ERROR);
require_once $root . '/System/Services/TrustEvaluator.php';

mt_srand((int) $scenarioDocument['seed']);
$results = [];
$levelMismatches = 0;
$flagMismatches = 0;
$baselineMismatches = 0;
$noGpsCases = 0;
$noGpsHigh = 0;
$veryLowAccuracyCases = 0;
$veryLowAccuracyHigh = 0;
$mediumThresholdCases = 0;
$mediumThresholdCorrect = 0;
$missingAccuracyWithGps = 0;
$missingAccuracyHigh = 0;
$missingAccuracyFlagged = 0;

foreach ($scenarioDocument['cases'] as $case) {
    $latitude = array_key_exists('lat', $case) && $case['lat'] !== null ? (float) $case['lat'] : null;
    $longitude = array_key_exists('lng', $case) && $case['lng'] !== null ? (float) $case['lng'] : null;
    $accuracy = array_key_exists('accuracy_m', $case) && $case['accuracy_m'] !== null ? (float) $case['accuracy_m'] : null;
    $source = array_key_exists('source', $case) && $case['source'] !== null ? (string) $case['source'] : null;

    try {
        [$actualLevel, $actualFlags] = \App\Extensions\TitanTrust\System\Services\TrustEvaluator::evaluate(
            $latitude,
            $longitude,
            $accuracy,
            $source,
        );
        $error = null;
    } catch (Throwable $exception) {
        $actualLevel = null;
        $actualFlags = [];
        $error = $exception->getMessage();
    }

    $expectedLevel = (string) $case['expected_level'];
    $expectedFlags = (array) $case['expected_flags'];
    $levelMatch = $actualLevel === $expectedLevel;
    $flagsMatch = $actualFlags === $expectedFlags;
    if (!$levelMatch) {
        $levelMismatches++;
    }
    if (!$flagsMatch) {
        $flagMismatches++;
    }

    $tags = (array) ($case['tags'] ?? []);
    if (in_array('no-gps', $tags, true)) {
        $noGpsCases++;
        if ($actualLevel === 'high') {
            $noGpsHigh++;
        }
    }
    if (in_array('very-low-accuracy', $tags, true)) {
        $veryLowAccuracyCases++;
        if ($actualLevel === 'high') {
            $veryLowAccuracyHigh++;
        }
    }
    if (in_array('medium-threshold', $tags, true)) {
        $mediumThresholdCases++;
        if ($actualLevel === 'medium') {
            $mediumThresholdCorrect++;
        }
    }
    if (in_array('missing-accuracy-with-gps', $tags, true)) {
        $missingAccuracyWithGps++;
        if ($actualLevel === 'high') {
            $missingAccuracyHigh++;
        }
        if (in_array('no_accuracy', $actualFlags, true)) {
            $missingAccuracyFlagged++;
        }
    }

    // Illustrative bypass baseline: every observation is reported as high with no flags.
    if ($actualLevel !== 'high' || $actualFlags !== []) {
        $baselineMismatches++;
    }

    $results[] = [
        'id' => (string) $case['id'],
        'category' => (string) $case['category'],
        'expected_level' => $expectedLevel,
        'actual_level' => $actualLevel,
        'expected_flags' => $expectedFlags,
        'actual_flags' => $actualFlags,
        'level_match' => $levelMatch,
        'flags_match' => $flagsMatch,
        'error' => $error,
    ];
}

$scenarioCount = count($results);
$scenarioFailures = $levelMismatches + $flagMismatches;
$scenarioHash = hash_file('sha256', $scenarioPath);
$evaluatedAt = gmdate('Y-m-d\TH:i:s\Z');
$commit = getenv('GITHUB_SHA') ?: 'local-uncommitted';

$report = [
    'title' => (string) $scenarioDocument['title'],
    'evaluated_at_utc' => $evaluatedAt,
    'evaluated_commit' => $commit,
    'scenario_seed' => (int) $scenarioDocument['seed'],
    'random_sampling' => (bool) ($scenarioDocument['random_sampling'] ?? false),
    'scenario_count' => $scenarioCount,
    'scenario_sha256' => $scenarioHash,
    'php_version' => PHP_VERSION,
    'baseline' => [
        'name' => (string) $scenarioDocument['baseline']['name'],
        'description' => (string) $scenarioDocument['baseline']['behavior'],
        'output_mismatches' => $baselineMismatches,
        'scenarios_total' => $scenarioCount,
    ],
    'metrics' => [
        'trust_level_mismatches' => $levelMismatches,
        'quality_flag_mismatches' => $flagMismatches,
        'scenario_failures' => $scenarioFailures,
        'no_gps_high_signals' => $noGpsHigh,
        'no_gps_cases_total' => $noGpsCases,
        'very_low_accuracy_high_signals' => $veryLowAccuracyHigh,
        'very_low_accuracy_cases_total' => $veryLowAccuracyCases,
        'medium_threshold_correct' => $mediumThresholdCorrect,
        'medium_threshold_cases_total' => $mediumThresholdCases,
        'missing_accuracy_high_signals' => $missingAccuracyHigh,
        'missing_accuracy_flagged' => $missingAccuracyFlagged,
        'missing_accuracy_with_gps_total' => $missingAccuracyWithGps,
    ],
    'diagnostics' => [
        'missing_accuracy_behavior' => 'The current implementation flags missing accuracy but may still return a high level when coordinates are present. A high level is a coarse signal, not proof of attendance.',
    ],
    'rubric' => (string) $scenarioDocument['rubric'],
    'cases' => $results,
];

$outputDir = $root . '/eval-results';
if (!is_dir($outputDir) && !mkdir($outputDir, 0775, true) && !is_dir($outputDir)) {
    throw new RuntimeException('Could not create evaluation output directory.');
}

$jsonPath = $outputDir . '/trust-signal-latest.json';
$markdownPath = $outputDir . '/trust-signal-latest.md';
file_put_contents($jsonPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL);

$lines = [
    '# Trust Signal Evaluation',
    '',
    '- Evaluated: ' . $evaluatedAt,
    '- Evaluator commit: ' . $commit,
    '- Scenarios: ' . $scenarioCount . ' fixed cases; seed ' . (int) $scenarioDocument['seed'] . '; random sampling: no',
    '- Scenario SHA-256: ' . $scenarioHash,
    '- PHP: ' . PHP_VERSION,
    '- Allow-all baseline exact-output mismatches: ' . $baselineMismatches . ' / ' . $scenarioCount,
    '',
    '| Metric | Result |',
    '| --- | ---: |',
    '| Trust-level mismatches | ' . $levelMismatches . ' / ' . $scenarioCount . ' |',
    '| Quality-flag mismatches | ' . $flagMismatches . ' / ' . $scenarioCount . ' |',
    '| High signals with no GPS | ' . $noGpsHigh . ' / ' . $noGpsCases . ' |',
    '| High signals with accuracy over 500 m | ' . $veryLowAccuracyHigh . ' / ' . $veryLowAccuracyCases . ' |',
    '| Correct medium signals for 100 m < accuracy <= 500 m | ' . $mediumThresholdCorrect . ' / ' . $mediumThresholdCases . ' |',
    '| Missing-accuracy cases with GPS still marked high | ' . $missingAccuracyHigh . ' / ' . $missingAccuracyWithGps . ' |',
    '| Missing-accuracy cases explicitly flagged | ' . $missingAccuracyFlagged . ' / ' . $missingAccuracyWithGps . ' |',
    '| Scenario failures | ' . $scenarioFailures . ' / ' . $scenarioCount . ' |',
    '',
    'This is a deterministic behavior-conformance evaluation of the GPS signal heuristic. GPS does not prove attendance or truth. The baseline is an illustrative always-high/no-flags bypass, not a competing product.',
    '',
    '## Diagnostic finding',
    '',
    'The implementation flags missing accuracy as no_accuracy but still returns high when coordinates are present. This behavior is included in the recorded contract results and is visible as a separate caution; high remains a coarse signal only.',
    '',
    '## Scenario outcomes',
    '',
    '| Scenario | Category | Expected | Actual | Flags | Result |',
    '| --- | --- | --- | --- | --- | --- |',
];

foreach ($results as $result) {
    $expectedFlags = implode(', ', $result['expected_flags']);
    $actualFlags = implode(', ', $result['actual_flags']);
    $pass = $result['level_match'] && $result['flags_match'] && $result['error'] === null;
    $lines[] = '| ' . $result['id'] . ' | ' . $result['category'] . ' | '
        . $result['expected_level'] . ' | ' . ($result['actual_level'] ?? 'error')
        . ' | expected: ' . $expectedFlags . '; actual: ' . $actualFlags
        . ' | ' . ($pass ? 'PASS' : 'FAIL') . ' |';
}
$lines[] = '';
file_put_contents($markdownPath, implode(PHP_EOL, $lines));

echo 'Trust signal evaluation: ' . $scenarioCount . ' cases, ' . $scenarioFailures . ' scenario failures.' . PHP_EOL;
echo 'No-GPS high: ' . $noGpsHigh . '/' . $noGpsCases
    . '; very-low-accuracy high: ' . $veryLowAccuracyHigh . '/' . $veryLowAccuracyCases
    . '; missing-accuracy high with GPS: ' . $missingAccuracyHigh . '/' . $missingAccuracyWithGps
    . '; baseline output mismatches: ' . $baselineMismatches . '/' . $scenarioCount . '.' . PHP_EOL;
echo 'Wrote ' . $jsonPath . PHP_EOL;
echo 'Wrote ' . $markdownPath . PHP_EOL;

exit($scenarioFailures === 0 ? 0 : 1);
