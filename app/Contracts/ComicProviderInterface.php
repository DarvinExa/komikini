<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTO\Comic\ChapterPayload;
use App\DTO\Comic\ComicCollection;
use App\DTO\Comic\ComicDetail;
use App\DTO\Comic\ComicPage;
use App\DTO\Comic\GenreCollection;
use App\Enums\ComicType;

interface ComicProviderInterface
{
    /**
     * Retrieve the latest released comics.
     */
    public function latest(int $page = 1): ComicPage;

    /**
     * Retrieve recommended comics.
     */
    public function recommended(): ComicCollection;

    /**
     * Retrieve popular comics, optionally filtered by comic type (manga, manhwa, manhua).
     */
    public function popular(?ComicType $type = null): ComicCollection;

    /**
     * Search comics by title/keyword.
     */
    public function search(string $query, int $page = 1): ComicPage;

    /**
     * Retrieve all available genres.
     */
    public function genres(): GenreCollection;

    /**
     * Retrieve comics categorized under a specific genre.
     */
    public function byGenre(string $slug, int $page = 1): ComicPage;

    /**
     * Retrieve full details of a single comic including chapter listing.
     */
    public function detail(string $slug): ComicDetail;

    /**
     * Retrieve chapter payload including image page URLs and prev/next keys.
     */
    public function chapter(string $slug, string $chapter): ChapterPayload;
}
