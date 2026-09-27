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
