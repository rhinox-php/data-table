<?php
$url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (is_file(__DIR__ . '/../' . $url)) {
    if (preg_match('~^[a-z0-9/-]+\.php$~', $url)) {
        require __DIR__ . '/../' . $url;
        return;
    }
    return false;
}
if (is_dir(__DIR__ . '/../' . $url)) {
    if ($url !== '/' && !preg_match('~[^/]/$~', $url)) {
        header('Location: ' . rtrim($url, '/') . '/');
        return;
    }
    $files = [];
    foreach (new DirectoryIterator(__DIR__ . '/../' . $url) as $fileInfo) {
        if ($fileInfo->getFilename() === '.') {
            continue;
        }
        $files[] = $fileInfo->getFilename();
    }
    usort($files, function ($a, $b) {
        return strnatcasecmp($a, $b);
    });
    foreach ($files as $fileInfo) {
        echo '<a href="./' . htmlspecialchars($fileInfo, ENT_QUOTES) . '">' . $fileInfo . '</a><br />' . PHP_EOL;
    }
    return;
}
http_response_code(404);
