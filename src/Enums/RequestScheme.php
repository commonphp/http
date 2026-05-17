<?php

declare(strict_types=1);

namespace CommonPHP\HTTP\Enums;

use CommonPHP\HTTP\Exceptions\UnsupportedSchemeException;

enum RequestScheme
{
    case HTTP;
    case HTTPS;

    /**
     * @param array<string, mixed> $server
     */
    public static function fromServer(array $server): self
    {
        $scheme = $server['REQUEST_SCHEME'] ?? null;

        if (is_string($scheme) && $scheme !== '') {
            return self::fromString($scheme);
        }

        $forwarded = $server['HTTP_X_FORWARDED_PROTO'] ?? null;

        if (is_string($forwarded) && $forwarded !== '') {
            $parts = explode(',', $forwarded);

            return self::fromString(trim($parts[0]));
        }

        $https = $server['HTTPS'] ?? null;

        if ($https !== null && $https !== '' && strtolower((string) $https) !== 'off') {
            return self::HTTPS;
        }

        $port = $server['SERVER_PORT'] ?? null;

        return (string) $port === '443' ? self::HTTPS : self::HTTP;
    }

    public static function fromString(string $scheme): self
    {
        return match (strtolower(trim($scheme))) {
            'http' => self::HTTP,
            'https' => self::HTTPS,
            default => throw UnsupportedSchemeException::forScheme($scheme),
        };
    }

    public function value(): string
    {
        return strtolower($this->name);
    }

    public function isSecure(): bool
    {
        return $this === self::HTTPS;
    }
}
