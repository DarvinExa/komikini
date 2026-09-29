import React from 'react';
import { Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { ComicPage } from '@/types';
import { ComicGrid } from '@/Components/ComicGrid';
import { Pagination } from '@/Components/Pagination';

interface BrowseProps {
    title: string;
    description?: string;
    comics: ComicPage;
    type?: string | null;
    genre?: string | null;
}

export default function Browse({
    title,
    description,
    comics,
    type,
    genre,
}: BrowseProps) {
    const items = comics?.items || [];
    const canonicalPath = type ? `/type/${type}` : genre ? `/genre/${genre}` : '/terbaru';

    return (
        <AppLayout
            title={title}
            description={description || `Koleksi komik ${title} bahasa Indonesia di Komikini.`}
            canonical={canonicalPath}
        >
            {/* Breadcrumb Navigation */}
            <div className="mb-4">
                <Link
                    href="/"
                    className="inline-flex items-center gap-1.5 font-sans text-xs font-semibold uppercase tracking-wider text-[#aaa9a3] hover:text-[#bdd600] transition-colors"
                >
                    <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span>Beranda</span>
                </Link>
            </div>

            {/* Header Section */}
            <header className="mb-8 pb-4 border-b border-[#303030]">
                <h1 className="font-display font-bold text-3xl sm:text-4xl uppercase tracking-wider text-[#f3f3ef]">
                    {title}
                </h1>
                {description && (
                    <p className="font-sans text-sm text-[#aaa9a3] mt-1">
                        {description}
                    </p>
                )}
            </header>

            {/* Comic Grid */}
            <main>
                <ComicGrid
                    comics={items}
                    emptyMessage="Tidak ada komik yang ditemukan dalam kategori ini."
                />

                {/* Pagination Controls */}
                <div className="mt-8">
                    <Pagination
                        currentPage={comics.current_page}
                        hasNextPage={comics.has_next_page}
                        hasPrevPage={comics.has_prev_page}
                        totalPages={comics.total_pages}
                    />
                </div>
            </main>
        </AppLayout>
    );
}
