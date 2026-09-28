export interface User {
    id: number;
    public_id: string;
    name: string;
    username: string | null;
    email: string;
    email_verified_at: string | null;
    status: string;
}

export type ComicType = 'manga' | 'manhwa' | 'manhua' | 'unknown';

export interface ComicItem {
    slug: string;
    title: string;
    thumbnail_url: string | null;
    comic_type: ComicType;
    latest_chapter: string | null;
    rating: string | null;
    description: string | null;
}

export interface ComicPage {
    items: ComicItem[];
    current_page: number;
    has_next_page: boolean;
    has_prev_page: boolean;
    total_pages: number | null;
}

export interface Genre {
    slug: string;
    name: string;
}

export interface ChapterItem {
    chapter_key: string;
    chapter_number: string;
    title: string;
    slug?: string | null;
    release_date: string | null;
}

export interface ComicDetail {
    slug: string;
    title: string;
    alternative_title: string | null;
    thumbnail_url: string | null;
    comic_type: ComicType;
    publication_status: string | null;
    author: string | null;
    synopsis: string | null;
    genres: Genre[];
    chapters: ChapterItem[];
    first_chapter: ChapterItem | null;
    latest_chapter: ChapterItem | null;
}

export interface ChapterPayload {
    comic_slug: string;
    chapter_key: string;
    chapter_number: string;
    title: string;
    images: string[];
    prev_chapter_key: string | null;
    next_chapter_key: string | null;
}

export interface UserHistory {
    chapter_key: string;
    chapter_number: string;
    last_image_index: number | null;
    progress_percent: number;
    read_at: string;
}

export interface ReadingHistoryItem {
    id: number;
    comic_id: number;
    comic_slug: string;
    comic_title: string;
    comic_thumbnail: string | null;
    chapter_key: string;
    chapter_number: string;
    last_image_index: number | null;
    progress_percent: number;
    read_at: string;
    completed_at: string | null;
}

export interface BookmarkItem {
    id: number;
    comic_id: number;
    comic_slug: string;
    comic_title: string;
    comic_thumbnail: string | null;
    comic_type: ComicType;
    created_at: string;
}

export type PageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    appName: string;
    auth: {
        user: User | null;
    };
    flash: {
        success: string | null;
        error: string | null;
    };
    correlationId: string;
};
