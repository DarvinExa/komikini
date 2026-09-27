import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { ComicPage } from '@/types';
import { ComicGrid } from '@/Components/ComicGrid';
import { Pagination } from '@/Components/Pagination';
import { EmptyState } from '@/Components/EmptyState';

interface SearchProps {
    query: string;
    comics: ComicPage;
}

export default function Search({ query = '', comics }: SearchProps) {
    const [searchTerm, setSearchTerm] = useState(query);
    const items = comics?.items || [];
    const hasSearched = Boolean(query && query.trim().length > 0);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        const trimmed = searchTerm.trim();
        if (trimmed) {
            router.get('/search', { q: trimmed });
        }
    };

    return (
        <AppLayout
            title={hasSearched ? `Hasil Pencarian: ${query}` : 'Pencarian Komik'}
            description="Cari judul manga, manhwa, dan manhua favoritmu dengan mudah dan cepat di Komikini."
        >
            {/* Search Header */}
            <div className="max-w-2xl mx-auto mb-10 text-center">
                <h1 className="font-display text-3xl sm:text-4xl uppercase tracking-wider text-[#F8F8F8] mb-2">
                    Cari Judul Komik
                </h1>
                <p className="text-sm text-[#AAAAAA] mb-6">
                    Ketik judul komik, manga, manhwa, atau manhua yang ingin kamu temukan.
                </p>

                {/* Form Input */}
                <form onSubmit={handleSubmit} className="flex flex-col sm:flex-row items-stretch gap-2">
                    <input
                        type="text"
                        value={searchTerm}
                        onChange={(e) => setSearchTerm(e.target.value)}
                        placeholder="Contoh: Solo Leveling, One Piece, Tower of God..."
                        maxLength={100}
                        className="flex-1 bg-[#161616] text-sm text-[#F8F8F8] placeholder-[#777777] px-4 py-3 rounded-none border-2 border-[#444444] focus:border-[#BAD306] focus:outline-none transition-colors"
                        aria-label="Kata kunci pencarian"
                    />
                    <button
                        type="submit"
                        className="px-6 py-3 font-display text-sm tracking-wider uppercase text-[#111111] bg-[#BAD306] hover:bg-[#E0FF00] border-2 border-[#BAD306] transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#BAD306] rounded-none shrink-0"
                    >
                        Cari
                    </button>
                </form>
            </div>

            {/* Results or States */}
            {!hasSearched ? (
                <div className="text-center py-12 bg-[#161616] border border-[#222222] max-w-lg mx-auto p-6 rounded-none">
                    <p className="text-sm text-[#AAAAAA]">
                        Mulai ketik di kolom pencarian di atas untuk menemukan ribuan judul komik menarik.
                    </p>
                </div>
            ) : items.length === 0 ? (
                <EmptyState
                    title={`Tidak ada komik untuk "${query}"`}
                    description="Periksa kembali ejaan kata kunci kamu atau coba judul lain yang serupa."
                    actionHref="/terbaru"
                    actionLabel="Jelajahi Komik Terbaru"
                />
            ) : (
                <section aria-labelledby="search-results-heading">
                    <div className="flex items-center justify-between mb-4 pb-2 border-b-2 border-[#222222]">
                        <h2 id="search-results-heading" className="font-display text-xl uppercase tracking-wider text-[#F8F8F8]">
                            Hasil Pencarian: <span className="text-[#BAD306]">"{query}"</span>
                        </h2>
                    </div>

                    <ComicGrid comics={items} />

                    <div className="mt-8">
                        <Pagination
                            currentPage={comics.current_page}
                            hasNextPage={comics.has_next_page}
                            hasPrevPage={comics.has_prev_page}
                            totalPages={comics.total_pages}
                            baseUrl="/search"
                            queryParams={{ q: query }}
                        />
                    </div>
                </section>
            )}
        </AppLayout>
    );
}
