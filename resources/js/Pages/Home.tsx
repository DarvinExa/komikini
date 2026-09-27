import React from 'react';
import { Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { ComicItem, ComicPage, Genre } from '@/types';
import { ComicGrid } from '@/Components/ComicGrid';
import { StaleBanner } from '@/Components/StaleBanner';

interface HomeProps {
    recommended: ComicItem[];
    popular: ComicItem[];
    latest: ComicPage | null;
    genres: Genre[];
    errorMessage?: string | null;
}

export default function Home({
    recommended = [],
    popular = [],
    latest,
    genres = [],
    errorMessage,
}: HomeProps) {
    const latestItems = latest?.items || [];

    return (
        <AppLayout
            title="Baca Manga, Manhwa, Manhua Bahasa Indonesia"
            description="Platform baca manga, manhwa, dan manhua bahasa Indonesia cepat dan bebas gangguan."
        >
            {errorMessage && <StaleBanner message={errorMessage} />}

            {/* Quick Filter Bar */}
            <div className="w-full max-w-full overflow-hidden mb-8 pb-3 border-b-2 border-[#222222]">
                <nav aria-label="Filter Tipe Komik" className="overflow-x-auto w-full pb-1">
                    <div className="flex items-center gap-2 min-w-max">
                        <span className="font-display text-xs tracking-wider uppercase text-[#777777] mr-1 hidden sm:inline">
                            Format:
                        </span>
                        <span className="px-3 py-1 font-display text-xs sm:text-sm tracking-wider uppercase bg-[#BAD306] text-[#111111] border-2 border-[#BAD306] rounded-none">
                            Semua
                        </span>
                        <Link
                            href="/type/manga"
                            className="px-3 py-1 font-display text-xs sm:text-sm tracking-wider uppercase bg-[#161616] text-[#F8F8F8] border border-[#444444] hover:border-[#BAD306] hover:text-[#BAD306] transition-colors rounded-none"
                        >
                            Manga
                        </Link>
                        <Link
                            href="/type/manhwa"
                            className="px-3 py-1 font-display text-xs sm:text-sm tracking-wider uppercase bg-[#161616] text-[#F8F8F8] border border-[#444444] hover:border-[#BAD306] hover:text-[#BAD306] transition-colors rounded-none"
                        >
                            Manhwa
                        </Link>
                        <Link
                            href="/type/manhua"
                            className="px-3 py-1 font-display text-xs sm:text-sm tracking-wider uppercase bg-[#161616] text-[#F8F8F8] border border-[#444444] hover:border-[#BAD306] hover:text-[#BAD306] transition-colors rounded-none"
                        >
                            Manhua
                        </Link>
                        <Link
                            href="/genre"
                            className="px-3 py-1 font-display text-xs sm:text-sm tracking-wider uppercase bg-[#161616] text-[#AAAAAA] border border-[#333333] hover:border-[#BAD306] hover:text-[#F8F8F8] transition-colors rounded-none"
                        >
                            Direktori Genre
                        </Link>
                    </div>
                </nav>
            </div>

            {/* Rilis Terbaru Section */}
            <section aria-labelledby="latest-heading" className="mb-12">
                <div className="flex items-end justify-between mb-2">
                    <h2 id="latest-heading" className="font-display text-2xl sm:text-3xl text-[#F8F8F8] tracking-wider uppercase">
                        Update Terbaru
                    </h2>
                    <Link
                        href="/terbaru"
                        className="font-display text-xs sm:text-sm text-[#BAD306] hover:text-[#E0FF00] tracking-wider uppercase transition-colors"
                    >
                        Lihat Semua
                    </Link>
                </div>
                <div className="h-0.5 bg-[#222222] w-full mb-4" />
                <ComicGrid
                    comics={latestItems}
                    emptyMessage="Belum ada komik terbaru yang dapat dimuat."
                />
            </section>

            {/* Komik Populer Section */}
            {popular.length > 0 && (
                <section aria-labelledby="popular-heading" className="mb-12">
                    <div className="flex items-end justify-between mb-2">
                        <h2 id="popular-heading" className="font-display text-2xl sm:text-3xl text-[#F8F8F8] tracking-wider uppercase">
                            Populer di Komikini
                        </h2>
                        <div className="flex items-center gap-2">
                            <Link
                                href="/type/manhwa"
                                className="font-display text-xs text-[#AAAAAA] hover:text-[#F8F8F8] px-2 py-0.5 border border-[#333333] uppercase"
                            >
                                Manhwa
                            </Link>
                            <Link
                                href="/type/manga"
                                className="font-display text-xs text-[#AAAAAA] hover:text-[#F8F8F8] px-2 py-0.5 border border-[#333333] uppercase"
                            >
                                Manga
                            </Link>
                        </div>
                    </div>
                    <div className="h-0.5 bg-[#222222] w-full mb-4" />
                    <ComicGrid comics={popular.slice(0, 6)} />
                </section>
            )}

            {/* Rekomendasi Section */}
            {recommended.length > 0 && (
                <section aria-labelledby="recommended-heading" className="mb-12">
                    <div className="flex items-end justify-between mb-2">
                        <h2 id="recommended-heading" className="font-display text-2xl sm:text-3xl text-[#F8F8F8] tracking-wider uppercase">
                            Rekomendasi Editor
                        </h2>
                    </div>
                    <div className="h-0.5 bg-[#222222] w-full mb-4" />
                    <ComicGrid comics={recommended.slice(0, 6)} />
                </section>
            )}

            {/* Quick Genre Exploration */}
            {genres.length > 0 && (
                <section aria-labelledby="genres-explore-heading" className="mb-12">
                    <div className="flex items-end justify-between mb-2">
                        <h2 id="genres-explore-heading" className="font-display text-xl sm:text-2xl text-[#F8F8F8] tracking-wider uppercase">
                            Kategori Genre
                        </h2>
                        <Link
                            href="/genre"
                            className="font-display text-xs sm:text-sm text-[#BAD306] hover:text-[#E0FF00] tracking-wider uppercase transition-colors"
                        >
                            Semua Genre
                        </Link>
                    </div>
                    <div className="h-0.5 bg-[#222222] w-full mb-4" />
                    <div className="flex flex-wrap gap-2">
                        {genres.slice(0, 12).map((genre) => (
                            <Link
                                key={genre.slug}
                                href={`/genre/${genre.slug}`}
                                className="px-3 py-1.5 font-display text-xs tracking-wider uppercase bg-[#161616] text-[#AAAAAA] border border-[#222222] hover:border-[#BAD306] hover:text-[#F8F8F8] transition-colors rounded-none"
                            >
                                {genre.name}
                            </Link>
                        ))}
                    </div>
                </section>
            )}
        </AppLayout>
    );
}
