<?php
$file = __DIR__ . '/data-set.csv';

$result = [];

$handle = null;
try {
    $handle = fopen($file, 'r');
    if (!$handle) {
        throw new \Exception('Could not read file: ' . $file);
    }

    $i = 0;
    while (($row = fgetcsv($handle)) !== false) {
        // Skip headers
        if ($i > 0) {
            $result[] = $row;
            continue;
        }
        $i++;
    }
} finally {
    if ($handle && is_resource($handle)) {
        fclose($handle);
    }
}

return $result;