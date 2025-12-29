<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\Exceptions\BlockedDomainException;
use App\Exceptions\InvalidUrlException;
use App\Exceptions\PrivateNetworkException;
use Stringable;

final readonly class SafeUrl implements Stringable
{
    private string $value;

    public function __construct(string $url)
    {
        $url = trim($url);

        $this->validateFormat($url);
        $this->validateScheme($url);

        if (app()->isProduction()) {
            $this->preventSSRF($url);
        }

        $this->value = $url;
    }

    public static function from(string $url): self
    {
        return new self($url);
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getHost(): string
    {
        return (string) parse_url($this->value, PHP_URL_HOST);
    }

    private function validateFormat(string $url): void
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new InvalidUrlException("Invalid URL format: {$url}");
        }
    }

    private function validateScheme(string $url): void
    {
        $scheme = parse_url($url, PHP_URL_SCHEME);

        if (! in_array($scheme, ['http', 'https'], true)) {
            throw new InvalidUrlException("Only http and https schemes are allowed, got: {$scheme}");
        }
    }

    private function preventSSRF(string $url): void
    {
        $host = parse_url($url, PHP_URL_HOST);

        if ($host === null || $host === '') {
            throw new InvalidUrlException('URL must have a valid host');
        }

        $this->validateNotLocalhost($host);
        $this->validateNotBlockedDomain($host);
        $this->validateNotPrivateIP($host);
    }

    private function validateNotLocalhost(string $host): void
    {
        $localhostPatterns = [
            'localhost',
            '127.0.0.1',
            '::1',
            '0.0.0.0',
        ];

        foreach ($localhostPatterns as $pattern) {
            if (strcasecmp($host, $pattern) === 0) {
                throw new PrivateNetworkException("Localhost URLs are not allowed: {$host}");
            }
        }

        if (str_starts_with($host, '127.')) {
            throw new PrivateNetworkException("Localhost URLs are not allowed: {$host}");
        }
    }

    private function validateNotBlockedDomain(string $host): void
    {
        $blockedDomains = config('audits.security.blocked_domains', []);

        foreach ($blockedDomains as $blocked) {
            $blocked = trim($blocked);
            if ($blocked === '' || $blocked === '0') {
                continue;
            }

            if (strcasecmp($host, $blocked) === 0 || str_ends_with($host, ".{$blocked}")) {
                throw new BlockedDomainException("Domain is blocked: {$host}");
            }
        }
    }

    private function validateNotPrivateIP(string $host): void
    {
        $ips = $this->resolveHostToIPs($host);

        foreach ($ips as $ip) {
            if ($this->isPrivateIP($ip)) {
                throw new PrivateNetworkException("Private network IP detected: {$ip} (resolved from {$host})");
            }
        }
    }

    /**
     * @return array<int, string>
     */
    private function resolveHostToIPs(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return [$host];
        }

        $ipv4s = @gethostbynamel($host);

        if ($ipv4s === false) {
            return [];
        }

        return $ipv4s;
    }

    private function isPrivateIP(string $ip): bool
    {
        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }

        $isNotPrivate = filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );

        return $isNotPrivate === false;
    }
}
