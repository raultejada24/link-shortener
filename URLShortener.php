<?php

declare(strict_types=1);

final class URLShortener
{
    private const ALPHABET = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    private const CODE_LENGTH = 7;
    private const MAX_URL_LENGTH = 2048;
    private const MAX_CODE_ATTEMPTS = 10;

    private PDO $database;
    private string $baseUrl;

    public function __construct(PDO $database, string $baseUrl)
    {
        $this->database = $database;
        $this->baseUrl = $baseUrl;
    }

    public function shorten(string $url): array
    {
        $url = trim($url);
        $this->validateUrl($url);

        $existingLink = $this->findByUrl($url);
        if ($existingLink !== null) {
            return $this->formatLink($existingLink);
        }

        for ($attempt = 0; $attempt < self::MAX_CODE_ATTEMPTS; $attempt++) {
            $code = $this->generateCode();

            try {
                $statement = $this->database->prepare(
                    'INSERT INTO shortened_urls (original_url, short_code) VALUES (:url, :code)'
                );
                $statement->execute([
                    'url' => $url,
                    'code' => $code,
                ]);

                return $this->formatLink([
                    'original_url' => $url,
                    'short_code' => $code,
                    'click_count' => 0,
                ]);
            } catch (PDOException $exception) {
                if ($exception->getCode() !== '23000') {
                    throw $exception;
                }
            }
        }

        throw new RuntimeException('Could not generate a unique short code.');
    }

    public function resolve(string $code): ?string
    {
        if (!preg_match('/^[a-zA-Z0-9]{' . self::CODE_LENGTH . '}$/', $code)) {
            return null;
        }

        $statement = $this->database->prepare(
            'SELECT original_url FROM shortened_urls WHERE short_code = :code LIMIT 1'
        );
        $statement->execute(['code' => $code]);
        $url = $statement->fetchColumn();

        if ($url === false) {
            return null;
        }

        $update = $this->database->prepare(
            'UPDATE shortened_urls SET click_count = click_count + 1 WHERE short_code = :code'
        );
        $update->execute(['code' => $code]);

        return (string) $url;
    }

    private function validateUrl(string $url): void
    {
        if ($url === '' || strlen($url) > self::MAX_URL_LENGTH) {
            throw new InvalidArgumentException('The URL must contain between 1 and 2048 characters.');
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('The URL is not valid.');
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if (!in_array($scheme, ['http', 'https'], true)) {
            throw new InvalidArgumentException('Only HTTP and HTTPS URLs are supported.');
        }
    }

    private function findByUrl(string $url): ?array
    {
        $statement = $this->database->prepare(
            'SELECT original_url, short_code, click_count
             FROM shortened_urls
             WHERE original_url = :url
             ORDER BY id ASC
             LIMIT 1'
        );
        $statement->execute(['url' => $url]);
        $link = $statement->fetch(PDO::FETCH_ASSOC);

        return $link === false ? null : $link;
    }

    private function generateCode(): string
    {
        $code = '';
        $lastIndex = strlen(self::ALPHABET) - 1;

        for ($index = 0; $index < self::CODE_LENGTH; $index++) {
            $code .= self::ALPHABET[random_int(0, $lastIndex)];
        }

        return $code;
    }

    private function formatLink(array $link): array
    {
        $code = (string) $link['short_code'];
        $separator = strpos($this->baseUrl, '?') !== false ? '&' : '?';

        return [
            'url' => (string) $link['original_url'],
            'code' => $code,
            'short_url' => rtrim($this->baseUrl, '?&') . $separator . 'code=' . rawurlencode($code),
            'clicks' => (int) $link['click_count'],
        ];
    }
}
