<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Services\Comic\ImageUrlValidator;
use Tests\TestCase;

class SsrfProtectionTest extends TestCase
{
    protected ImageUrlValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new ImageUrlValidator([
            'komiku.id',
            'komiku.org',
            'komiku.to',
            'img.komiku.id',
            'cdn.komiku.id',
            'i0.wp.com',
            'i1.wp.com',
        ]);
    }

    public function test_rejects_loopback_ipv4_and_ipv6(): void
    {
        $this->assertFalse($this->validator->isValid('https://127.0.0.1/evil.jpg'));
        $this->assertFalse($this->validator->isValid('https://127.0.0.2/evil.jpg'));
        $this->assertFalse($this->validator->isValid('https://127.1/evil.jpg'));
        $this->assertFalse($this->validator->isValid('https://[::1]/evil.jpg'));
        $this->assertFalse($this->validator->isValid('https://localhost/evil.jpg'));
        $this->assertFalse($this->validator->isValid('https://sub.localhost/evil.jpg'));
    }

    public function test_rejects_private_rfc1918_ip_addresses(): void
    {
        $this->assertFalse($this->validator->isValid('https://10.0.0.1/evil.jpg'));
        $this->assertFalse($this->validator->isValid('https://192.168.1.1/evil.jpg'));
        $this->assertFalse($this->validator->isValid('https://172.16.0.1/evil.jpg'));
        $this->assertFalse($this->validator->isValid('https://172.31.255.255/evil.jpg'));
    }

    public function test_rejects_cloud_metadata_service_endpoints(): void
    {
        $this->assertFalse($this->validator->isValid('https://169.254.169.254/latest/meta-data/'));
        $this->assertFalse($this->validator->isValid('https://metadata.google.internal/computeMetadata/v1/'));
    }

    public function test_rejects_non_https_schemes(): void
    {
        $this->assertFalse($this->validator->isValid('http://komiku.id/image.jpg'));
        $this->assertFalse($this->validator->isValid('file:///etc/passwd'));
        $this->assertFalse($this->validator->isValid('gopher://komiku.id/'));
        $this->assertFalse($this->validator->isValid('ftp://komiku.id/image.jpg'));
        $this->assertFalse($this->validator->isValid('javascript:alert(1)'));
        $this->assertFalse($this->validator->isValid('data:image/png;base64,iVBORw0KGgo='));
    }

    public function test_rejects_non_standard_ports(): void
    {
        $this->assertFalse($this->validator->isValid('https://komiku.id:8080/image.jpg'));
        $this->assertFalse($this->validator->isValid('https://komiku.id:22/image.jpg'));
        $this->assertFalse($this->validator->isValid('https://komiku.id:3306/image.jpg'));
        $this->assertFalse($this->validator->isValid('https://komiku.id:6379/image.jpg'));
    }

    public function test_rejects_userinfo_url_confusion(): void
    {
        $this->assertFalse($this->validator->isValid('https://attacker.com@komiku.id/image.jpg'));
        $this->assertFalse($this->validator->isValid('https://user:pass@komiku.id/image.jpg'));
    }

    public function test_rejects_unallowlisted_third_party_domains(): void
    {
        $this->assertFalse($this->validator->isValid('https://evil-attacker.com/malicious.jpg'));
        $this->assertFalse($this->validator->isValid('https://not-komiku.id.attacker.com/image.jpg'));
        $this->assertFalse($this->validator->isValid('https://komiku.id.attacker.com/image.jpg'));
    }

    public function test_accepts_valid_allowlisted_https_domains(): void
    {
        $this->assertTrue($this->validator->isValid('https://komiku.id/image.jpg'));
        $this->assertTrue($this->validator->isValid('https://cdn.komiku.id/chapter-1/page-1.jpg'));
        $this->assertTrue($this->validator->isValid('https://img.komiku.id/covers/one-piece.png'));
        $this->assertTrue($this->validator->isValid('https://i0.wp.com/img.komiku.org/test.webp'));
    }
}
