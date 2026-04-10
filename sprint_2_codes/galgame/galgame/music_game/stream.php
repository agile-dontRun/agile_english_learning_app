<?php
declare(strict_types=1);

function getAudioFiles(): array
{
    $audioFiles = glob(__DIR__ . DIRECTORY_SEPARATOR . '*.mp3') ?: [];
    sort($audioFiles, SORT_NATURAL | SORT_FLAG_CASE);
    return array_values($audioFiles);
}

function fail(int $statusCode, string $message): never
{
    http_response_code($statusCode);
    header('Content-Type: text/plain; charset=UTF-8');
    echo $message;
    exit;
}

$index = filter_input(INPUT_GET, 'index', FILTER_VALIDATE_INT);
if ($index === null || $index === false) {
    header('Location: english_music.php');
    exit;
}

$audioFiles = getAudioFiles();
if (!isset($audioFiles[$index])) {
    fail(404, 'Audio file not found.');
}

$filePath = $audioFiles[$index];
if (!is_file($filePath) || !is_readable($filePath)) {
    fail(404, 'Audio file not available.');
}

$size = filesize($filePath);
if ($size === false) {
    fail(500, 'Unable to read file size.');
}

$start = 0;
$end = $size - 1;
$length = $size;

header('Content-Type: audio/mpeg');
header('Accept-Ranges: bytes');
header('Cache-Control: no-cache');
header('Content-Disposition: inline; filename="' . rawurlencode(basename($filePath)) . '"');

$rangeHeader = $_SERVER['HTTP_RANGE'] ?? null;
if (is_string($rangeHeader) && preg_match('/bytes=(\d*)-(\d*)/', $rangeHeader, $matches) === 1) {
    if ($matches[1] !== '') {
        $start = (int) $matches[1];
    }

    if ($matches[2] !== '') {
        $end = (int) $matches[2];
    }

    if ($start > $end || $start >= $size) {
        header('Content-Range: bytes */' . $size);
        fail(416, 'Requested range not satisfiable.');
    }

    $end = min($end, $size - 1);
    $length = $end - $start + 1;

    http_response_code(206);
    header('Content-Range: bytes ' . $start . '-' . $end . '/' . $size);
}

header('Content-Length: ' . $length);

$handle = fopen($filePath, 'rb');
if ($handle === false) {
    fail(500, 'Unable to open audio file.');
}

fseek($handle, $start);

$bufferSize = 8192;
$bytesRemaining = $length;

while (!feof($handle) && $bytesRemaining > 0) {
    $readLength = min($bufferSize, $bytesRemaining);
    $buffer = fread($handle, $readLength);

    if ($buffer === false) {
        break;
    }

    echo $buffer;
    flush();
    $bytesRemaining -= strlen($buffer);
}

fclose($handle);
