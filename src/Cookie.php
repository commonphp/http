<?php

declare(strict_types=1);

namespace CommonPHP\HTTP;

use CommonPHP\HTTP\Exceptions\InvalidHeaderException;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

class Cookie
{
    public function __construct(
        private readonly string $name,
        private readonly string $value = '',
        private readonly ?DateTimeInterface $expires = null,
        private readonly string $path = '/',
        private readonly ?string $domain = null,
        private readonly bool $secure = false,
        private readonly bool $httpOnly = true,
        private readonly ?string $sameSite = 'Lax',
        private readonly bool $raw = false,
    ) {
        $this->assertValidName($name);
        $this->assertValidValue($value);
        $this->assertValidSameSite($sameSite);
    }

    public static function expired(string $name, string $path = '/', ?string $domain = null): self
    {
        return new self($name, '', new DateTimeImmutable('@1'), $path, $domain);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function expires(): ?DateTimeInterface
    {
        return $this->expires;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function domain(): ?string
    {
        return $this->domain;
    }

    public function isSecure(): bool
    {
        return $this->secure;
    }

    public function isHttpOnly(): bool
    {
        return $this->httpOnly;
    }

    public function sameSite(): ?string
    {
        return $this->sameSite;
    }

    public function isExpired(?DateTimeInterface $now = null): bool
    {
        if ($this->expires === null) {
            return false;
        }

        $now ??= new DateTimeImmutable();

        return $this->expires->getTimestamp() <= $now->getTimestamp();
    }

    public function withValue(string $value): self
    {
        return new self(
            $this->name,
            $value,
            $this->expires,
            $this->path,
            $this->domain,
            $this->secure,
            $this->httpOnly,
            $this->sameSite,
            $this->raw,
        );
    }

    public function expire(): self
    {
        return self::expired($this->name, $this->path, $this->domain);
    }

    public function toHeader(): string
    {
        $value = $this->raw ? $this->value : rawurlencode($this->value);
        $header = $this->name . '=' . $value;

        if ($this->expires !== null) {
            $expires = DateTimeImmutable::createFromInterface($this->expires)
                ->setTimezone(new DateTimeZone('GMT'));

            $maxAge = max(0, $expires->getTimestamp() - time());
            $header .= '; Expires=' . $expires->format('D, d M Y H:i:s \G\M\T');
            $header .= '; Max-Age=' . $maxAge;
        }

        if ($this->path !== '') {
            $header .= '; Path=' . $this->path;
        }

        if ($this->domain !== null && $this->domain !== '') {
            $header .= '; Domain=' . $this->domain;
        }

        if ($this->secure) {
            $header .= '; Secure';
        }

        if ($this->httpOnly) {
            $header .= '; HttpOnly';
        }

        if ($this->sameSite !== null) {
            $header .= '; SameSite=' . $this->sameSite;
        }

        return $header;
    }

    public function __toString(): string
    {
        return $this->toHeader();
    }

    private function assertValidName(string $name): void
    {
        if ($name === '' || preg_match('/^[A-Za-z0-9!#$%&\'*+.^_`|~-]+$/', $name) !== 1) {
            throw InvalidHeaderException::forName($name);
        }
    }

    private function assertValidValue(string $value): void
    {
        if (strpbrk($value, "\r\n;") !== false) {
            throw InvalidHeaderException::forValue($this->name);
        }
    }

    private function assertValidSameSite(?string $sameSite): void
    {
        if ($sameSite === null) {
            return;
        }

        if (!in_array($sameSite, ['Strict', 'Lax', 'None'], true)) {
            throw InvalidHeaderException::forValue('Set-Cookie');
        }
    }
}
