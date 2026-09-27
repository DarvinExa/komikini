<?php

declare(strict_types=1);

namespace Tests\Unit\Comic;

use App\DTO\Comic\ComicDetail;
use App\DTO\Comic\ComicItem;
use App\Enums\ComicType;
use App\Exceptions\ComicProvider\MalformedUpstreamResponseException;
use App\Services\Comic\ImageUrlValidator;
use App\Services\Comic\KomikuResponseMapper;
use PHPUnit\Framework\TestCase;

class KomikuResponseMapperTest extends TestCase
{
    protected KomikuResponseMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();
        $validator = new ImageUrlValidator(['img.komiku.id', 'cdn.komiku.id']);
        $this->mapper = new KomikuResponseMapper($validator);
    }

    public function test_maps_complete_comic_item(): void
    {
        $data = [
            'title' => ' <b>Bleach</b> ',
            'endpoint' => '/manga/bleach/',
            'thumbnail' => 'https://img.komiku.id/bleach.jpg',
            'type' => 'manga',
            'latest_chapter' => 'Chapter 686',
            'rating' => '8.5',
            'description' => 'Kurosaki Ichigo adalah seorang Shinigami pengganti.',
        ];

        $item = $this->mapper->mapComicItem($data);

        $this->assertInstanceOf(ComicItem::class, $item);
        $this->assertSame('bleach', $item->slug);
        $this->assertSame('Bleach', $item->title);
        $this->assertSame('https://img.komiku.id/bleach.jpg', $item->thumbnailUrl);
        $this->assertSame(ComicType::MANGA, $item->comicType);
        $this->assertSame('Chapter 686', $item->latestChapter);
        $this->assertSame('8.5', $item->rating);
        $this->assertSame('Kurosaki Ichigo adalah seorang Shinigami pengganti.', $item->description);
    }

    public function test_strips_html_tags_from_text_fields(): void
    {
        $data = [
            'title' => '<h1>Naruto</h1><script>alert(1)</script>',
            'slug' => 'naruto',
            'description' => '<p>Ninja dari desa Konoha.</p>',
        ];

        $item = $this->mapper->mapComicItem($data);
        $this->assertSame('Narutoalert(1)', $item->title);
        $this->assertSame('Ninja dari desa Konoha.', $item->description);
    }

    public function test_handles_missing_optional_fields_gracefully(): void
    {
        $data = [
            'title' => 'Minimal Comic',
            'slug' => 'minimal-comic',
        ];

        $item = $this->mapper->mapComicItem($data);

        $this->assertSame('minimal-comic', $item->slug);
        $this->assertSame('Minimal Comic', $item->title);
        $this->assertNull($item->thumbnailUrl);
        $this->assertSame(ComicType::UNKNOWN, $item->comicType);
        $this->assertNull($item->latestChapter);
        $this->assertNull($item->rating);
        $this->assertNull($item->description);
    }

    public function test_throws_malformed_exception_when_title_is_empty(): void
    {
        $this->expectException(MalformedUpstreamResponseException::class);
        $this->mapper->mapComicItem(['slug' => 'empty-title', 'title' => '   ']);
    }

    public function test_throws_malformed_exception_when_slug_is_empty(): void
    {
        $this->expectException(MalformedUpstreamResponseException::class);
        $this->mapper->mapComicItem(['title' => 'Valid Title', 'slug' => '']);
    }

    public function test_maps_comic_detail_with_chapters(): void
    {
        $data = [
            'title' => 'Solo Leveling',
            'alternative_title' => 'Only I Level Up',
            'thumbnail' => 'https://img.komiku.id/sl.jpg',
            'type' => 'manhwa',
            'status' => 'Completed',
            'author' => 'Chugong',
            'synopsis' => 'Dunia berubah ketika gate monster terbuka.',
            'genres' => ['Action', 'Fantasy'],
            'chapters' => [
                [
                    'title' => 'Chapter 179',
                    'slug' => 'solo-leveling-chapter-179',
                    'chapter_key' => 'chapter-179',
                    'chapter_number' => '179',
                    'release_date' => '2021-12-29',
                ],
                [
                    'title' => 'Chapter 1',
                    'slug' => 'solo-leveling-chapter-1',
                    'chapter_key' => 'chapter-1',
                    'chapter_number' => '1',
                    'release_date' => '2018-03-04',
                ],
            ],
        ];

        $detail = $this->mapper->mapComicDetail($data, 'solo-leveling');

        $this->assertInstanceOf(ComicDetail::class, $detail);
        $this->assertSame('solo-leveling', $detail->slug);
        $this->assertSame('Solo Leveling', $detail->title);
        $this->assertSame('Only I Level Up', $detail->alternativeTitle);
        $this->assertSame(ComicType::MANHWA, $detail->comicType);
        $this->assertCount(2, $detail->genres);
        $this->assertCount(2, $detail->chapters);
        $this->assertSame('chapter-179', $detail->latestChapter?->chapterKey);
        $this->assertSame('chapter-1', $detail->firstChapter?->chapterKey);
    }
}
