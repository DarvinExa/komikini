<?php

declare(strict_types=1);

namespace App\Services\Comic;

use InvalidArgumentException;

class ImageUrlValidator
{
    /**
     * @param  list<string>  $allowedHosts
     */
    public function __construct(
        protected array $allowedHosts = []
    ) {
        if (empty($this->allowedHosts)) {
            $this->allowedHosts = (array) config('comic.image_allowed_hosts', []);
        }
    }

    /**
     * Validate an image URL and return the sanitized URL string, or null if invalid.
     */
    public function sanitize(?string $url): ?string
    {
        if ($url === null) {
            return null;
        }

        $url = trim($url);
        if ($url === '') {
            return null;
        }

        return $this->isValid($url) ? $url : null;
    }

    /**
     * Validate an image URL, throwing an exception if invalid.
     */
    public function validate(string $url): string
    {
        $sanitized = $this->sanitize($url);

        if ($sanitized === null) {
            throw new InvalidArgumentException("URL gambar tidak valid atau host tidak diizinkan: {$url}");
        }

        return $sanitized;
    }

    /**
     * Check if an image URL satisfies all security constraints (HTTPS, allowlisted host, anti-SSRF).
     */
    public function isValid(string $url): bool
    {
        $parts = parse_url($url);
        if (! $parts || empty($parts['scheme']) || empty($parts['host'])) {
            return false;
        }

        // 1. Enforce HTTPS only
        if (strtolower($parts['scheme']) !== 'https') {
            return false;
        }

        // 2. Reject URLs with embedded credentials (userinfo) to prevent URL confusion
        if (isset($parts['user']) || isset($parts['pass'])) {
            return false;
        }

        // 3. Reject non-standard ports
        if (isset($parts['port']) && $parts['port'] !== 443) {
            return false;
        }

        $host = strtolower($parts['host']);

        // 4. Reject suspicious characters in host
        if (str_contains($host, '@') || str_contains($host, '%') || str_contains($host, '\\')) {
            return false;
        }

        // 5. Block loopback, private, and reserved IP ranges (Anti-SSRF)
        if ($this->isBlockedIpOrHost($host)) {
            return false;
        }

        // 6. Validate host against configured allowlist
        return $this->isHostAllowed($host);
    }

    /**
     * Check if a host is an IP or matches blocked patterns.
     */
    protected function isBlockedIpOrHost(string $host): bool
    {
        $cleanHost = trim($host, '[]');

        // Block numeric / octal / hex encoded IP addresses
        if (is_numeric($cleanHost)) {
            return true;
        }

        // Check for direct IP address
        if (filter_var($cleanHost, FILTER_VALIDATE_IP)) {
            // FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            if (! filter_var($cleanHost, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return true;
            }

            if ($cleanHost === '127.0.0.1' || $cleanHost === '::1' || $cleanHost === '169.254.169.254') {
                return true;
            }
        }

        // Block localhost and standard internal hostnames
        if ($host === 'localhost' || str_ends_with($host, '.localhost') || str_ends_with($host, '.local') || str_ends_with($host, '.internal') || str_ends_with($host, '.lan')) {
            return true;
        }

        // Block cloud metadata hostnames
        if (str_contains($host, 'metadata.google.internal') || str_contains($host, '169.254.169.254')) {
            return true;
        }

        return false;
    }

    /**
     * Check if host matches allowlist (exact or subdomain).
     */
    protected function isHostAllowed(string $host): bool
    {
        foreach ($this->allowedHosts as $allowed) {
            $allowed = strtolower(trim($allowed));
            if ($host === $allowed) {
                return true;
            }

            // Subdomain matching, e.g. *.komiku.id
            if (str_ends_with($host, '.'.$allowed)) {
                return true;
            }
        }

        return false;
    }
}
