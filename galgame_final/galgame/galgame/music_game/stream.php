<?php
declare(strict_types=1);

// Get all local MP3 files from the current directory
function getAudioFiles(): array
{
    $audioFiles = glob(__DIR__ . DIRECTORY_SEPARATOR . '*.mp3') ?: [];
    sort($audioFiles, SORT_NATURAL | SORT_FLAG_CASE);
    return array_values($audioFiles);
}

// Send an error response and stop execution
function fail(int $statusCode, string $message): never
{
    http_response_code($statusCode);
    header('Content-Type: text/plain; charset=UTF-8');
    echo $message;
    exit;
}

// Read the requested audio index from the URL query string
$index = filter_input(INPUT_GET, 'index', FILTER_VALIDATE_INT);

// If the index is missing or invalid, go back to the music page
if ($index === null || $index === false) {
    header('Location: english_music.php');
    exit;
}

// Load the list of available audio files
$audioFiles = getAudioFiles();

// Check whether the requested index exists
if (!isset($audioFiles[$index])) {
    fail(404, 'Audio file not found.');
}

$filePath = $audioFiles[$index];

// Make sure the target file exists and can be read
if (!is_file($filePath) || !is_readable($filePath)) {
    fail(404, 'Audio file not available.');
}

// Get the file size for streaming and range calculation
$size = filesize($filePath);
if ($size === false) {
    fail(500, 'Unable to read file size.');
}

// Default byte range: send the whole file
$start = 0;
$end = $size - 1;
$length = $size;

// Send audio response headers
header('Content-Type: audio/mpeg');
header('Accept-Ranges: bytes');
header('Cache-Control: no-cache');
header('Content-Disposition: inline; filename="' . rawurlencode(basename($filePath)) . '"');

// Check whether the browser requested a partial byte range
$rangeHeader = $_SERVER['HTTP_RANGE'] ?? null;
if (is_string($rangeHeader) && preg_match('/bytes=(\d*)-(\d*)/', $rangeHeader, $matches) === 1) {
    // If a start byte is provided, use it
    if ($matches[1] !== '') {
        $start = (int) $matches[1];
    }

    // If an end byte is provided, use it
    if ($matches[2] !== '') {
        $end = (int) $matches[2];
    }

    // Reject invalid ranges
    if ($start > $end || $start >= $size) {
        header('Content-Range: bytes */' . $size);
        fail(416, 'Requested range not satisfiable.');
    }

    // Clamp the end position so it does not exceed file size
    $end = min($end, $size - 1);
    $length = $end - $start + 1;

    // Return partial content response
    http_response_code(206);
    header('Content-Range: bytes ' . $start . '-' . $end . '/' . $size);
}

// Tell the client how many bytes will be sent
header('Content-Length: ' . $length);

// Open the file in binary read mode
$handle = fopen($filePath, 'rb');
if ($handle === false) {
    fail(500, 'Unable to open audio file.');
}

// Move the file pointer to the requested start position
fseek($handle, $start);

// Stream the file in chunks instead of loading everything into memory at once
$bufferSize = 8192;
$bytesRemaining = $length;

while (!feof($handle) && $bytesRemaining > 0) {
    $readLength = min($bufferSize, $bytesRemaining);
    $buffer = fread($handle, $readLength);

    // Stop if reading fails
    if ($buffer === false) {
        break;
    }

    // Output the current chunk and flush it to the client
    echo $buffer;
    flush();
    $bytesRemaining -= strlen($buffer);
}

// Close the file handle after streaming is complete
fclose($handle);