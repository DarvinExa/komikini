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
            noIndex={true}
        >
            {/* Screen Reader Live Region for Search Results (WCAG 2.2 AA) */}
            <div className="sr-only" role="status" aria-live="polite">
                {hasSearched ? `Ditemukan ${items.length} komik untuk kata kunci "${query}".` : ''}
            </div>

            {/* Search Header */}
            <div className="max-w-2xl mx-auto mb-10 text-center">
                <h1 className="font-display font-bold text-3xl sm:text-4xl uppercase tracking-wider text-[#f3f3ef] mb-2">
                    Cari Judul Komik
                </h1>
                <p className="font-sans text-sm text-[#aaa9a3] mb-6">
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
                        className="flex-1 bg-[#151515] text-sm text-[#f3f3ef] placeholder-[#777771] px-4 py-3 rounded-none border border-[#484848] focus:border-[#bdd600] focus:outline-none transition-colors"
                        aria-label="Kata kunci pencarian"
                    />
                    <button
                        type="submit"
                        className="px-6 py-3 font-sans text-xs font-bold tracking-wider uppercase text-[#101010] bg-[#bdd600] hover:bg-[#d8ef21] border border-[#bdd600] transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#bdd600] rounded-none shrink-0 cursor-pointer"
                    >
                        Cari
                    </button>
                </form>
            </div>

            {/* Results or States */}
            {!hasSearched ? (
                <div className="text-center py-12 bg-[#191919] border border-[#303030] max-w-lg mx-auto p-6 rounded-none">
                    <p className="font-sans text-sm text-[#aaa9a3]">
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
                    <div className="flex items-center justify-between mb-6 pb-3 border-b border-[#303030]">
                        <h2 id="search-results-heading" className="font-display font-bold text-2xl uppercase tracking-wider text-[#f3f3ef]">
                            Hasil Pencarian: <span className="text-[#bdd600]">"{query}"</span>
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
