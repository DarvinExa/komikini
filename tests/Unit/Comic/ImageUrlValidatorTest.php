<?php

declare(strict_types=1);

namespace Tests\Unit\Comic;

use App\Services\Comic\ImageUrlValidator;
use PHPUnit\Framework\TestCase;

class ImageUrlValidatorTest extends TestCase
{
    protected ImageUrlValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new ImageUrlValidator([
            'komiku.id',
            'img.komiku.id',
            'cdn.komiku.id',
            'i0.wp.com',
        ]);
    }

    public function test_allows_valid_https_urls_with_allowed_hosts(): void
    {
        $this->assertTrue($this->validator->isValid('https://komiku.id/cover.jpg'));
        $this->assertTrue($this->validator->isValid('https://img.komiku.id/uploads/ch1.png'));
        $this->assertTrue($this->validator->isValid('https://sub.img.komiku.id/pic.webp'));
        $this->assertTrue($this->validator->isValid('https://i0.wp.com/cache.jpg'));
    }

    public function test_rejects_non_https_schemes(): void
    {
        $this->assertFalse($this->validator->isValid('http://komiku.id/cover.jpg'));
        $this->assertFalse($this->validator->isValid('ftp://komiku.id/cover.jpg'));
        $this->assertFalse($this->validator->isValid('javascript:alert(1)'));
    }

    public function test_rejects_unallowed_hosts(): void
    {
        $this->assertFalse($this->validator->isValid('https://evil-attacker.com/malicious.jpg'));
        $this->assertFalse($this->validator->isValid('https://google.com/test.jpg'));
        $this->assertFalse($this->validator->isValid('https://komiku.id.attacker.com/fake.jpg'));
    }

    public function test_rejects_private_loopback_and_metadata_ips(): void
    {
        $this->assertFalse($this->validator->isValid('https://127.0.0.1/admin.jpg'));
        $this->assertFalse($this->validator->isValid('https://localhost/admin.jpg'));
        $this->assertFalse($this->validator->isValid('https://192.168.1.1/router.jpg'));
        $this->assertFalse($this->validator->isValid('https://10.0.0.1/internal.jpg'));
        $this->assertFalse($this->validator->isValid('https://169.254.169.254/latest/meta-data/'));
    }

    public function test_rejects_non_standard_ports(): void
    {
        $this->assertFalse($this->validator->isValid('https://img.komiku.id:8080/image.jpg'));
        $this->assertFalse($this->validator->isValid('https://img.komiku.id:8443/image.jpg'));
    }

    public function test_sanitize_returns_null_for_invalid_and_url_for_valid(): void
    {
        $this->assertSame('https://img.komiku.id/cover.jpg', $this->validator->sanitize('https://img.komiku.id/cover.jpg'));
        $this->assertNull($this->validator->sanitize('http://img.komiku.id/cover.jpg'));
        $this->assertNull($this->validator->sanitize('https://untrusted.com/cover.jpg'));
        $this->assertNull($this->validator->sanitize(''));
        $this->assertNull($this->validator->sanitize(null));
    }

    public function test_normalizes_komiku_domains_to_komiku_org(): void
    {
        $this->assertSame(
            'https://thumbnail.komiku.org/new/img/images/cover.webp',
            $this->validator->normalize('https://thumbnail.komiku.to/new/img/images/cover.webp')
        );
        $this->assertSame(
            'https://img.komiku.org/uploads2/page1.jpg',
            $this->validator->normalize('https://image2.komiku.to/uploads2/page1.jpg')
        );
        $this->assertSame(
            'https://img.komiku.org/uploads/page2.jpg',
            $this->validator->normalize('https://img.komiku.to/uploads/page2.jpg')
        );
    }
}
