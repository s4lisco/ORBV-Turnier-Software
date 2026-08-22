<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$testFiles = glob(__DIR__ . '/*Test.php');
sort($testFiles);

foreach ($testFiles as $file) {
    echo basename($file) . "\n";
    require $file;
}

$passed = $GLOBALS['__tests_passed'];
$failed = $GLOBALS['__tests_failed'];

echo "\n" . ($passed + $failed) . " Tests, $passed bestanden, $failed fehlgeschlagen.\n";

exit($failed > 0 ? 1 : 0);
