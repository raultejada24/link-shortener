<?php

declare(strict_types=1);

require_once __DIR__ . '/URLShortener.php';

function sendJson(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    exit;
}

function environmentValue(string $name, string $default): string
{
    $value = getenv($name);

    return $value === false || $value === '' ? $default : $value;
}

function detectBaseUrl(): string
{
    $isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $scheme = $isHttps ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = $_SERVER['SCRIPT_NAME'] ?? '/api.php';

    return $scheme . '://' . $host . $script;
}

function createDatabaseConnection(): PDO
{
    $host = environmentValue('DB_HOST', 'localhost');
    $port = environmentValue('DB_PORT', '3306');
    $name = environmentValue('DB_NAME', 'url_shortener');
    $user = environmentValue('DB_USER', 'root');
    $password = environmentValue('DB_PASSWORD', '');
    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

    return new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

try {
    $database = createDatabaseConnection();
    $baseUrl = environmentValue('APP_URL', detectBaseUrl());
    $shortener = new URLShortener($database, $baseUrl);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $body = file_get_contents('php://input');
        $input = json_decode($body === false ? '' : $body, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($input) || !isset($input['url']) || !is_string($input['url'])) {
            sendJson(['error' => 'A URL is required.'], 400);
        }

        sendJson($shortener->shorten($input['url']), 201);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['code']) && is_string($_GET['code'])) {
        $url = $shortener->resolve($_GET['code']);

        if ($url === null) {
            sendJson(['error' => 'Short URL not found.'], 404);
        }

        header('Location: ' . $url, true, 302);
        exit;
    }

    sendJson([
        'name' => 'URL Shortener',
        'message' => 'Send a POST request with a JSON body containing a URL.',
    ]);
} catch (JsonException) {
    sendJson(['error' => 'The request body must contain valid JSON.'], 400);
} catch (InvalidArgumentException $exception) {
    sendJson(['error' => $exception->getMessage()], 422);
} catch (Throwable) {
    sendJson(['error' => 'The server could not process the request.'], 500);
}
