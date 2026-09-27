import React, { useState, useMemo } from 'react';
import { Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Genre } from '@/types';

interface GenresProps {
    genres: Genre[];
}

export default function Genres({ genres = [] }: GenresProps) {
    const [search, setSearch] = useState('');

    const filteredGenres = useMemo(() => {
        if (!search.trim()) {
            return genres;
        }
        const q = search.toLowerCase().trim();
        return genres.filter(
            (g) => g.name.toLowerCase().includes(q) || g.slug.toLowerCase().includes(q)
        );
    }, [genres, search]);

    return (
        <AppLayout
            title="Daftar Genre Komik Terlengkap"
            description="Jelajahi ribuan komik berdasarkan kategori dan tema cerita favorit di Komikini."
        >
            {/* Breadcrumb Navigation */}
            <div className="mb-4">
                <Link
                    href="/"
                    className="inline-flex items-center gap-1.5 font-display text-xs tracking-wider uppercase text-[#AAAAAA] hover:text-[#BAD306] transition-colors"
                >
                    <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span>Beranda</span>
                </Link>
            </div>

            {/* Header */}
            <header className="mb-8 pb-4 border-b-2 border-[#222222] flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 className="font-display text-3xl sm:text-4xl uppercase tracking-wider text-[#F8F8F8]">
                        Direktori Genre
                    </h1>
                    <p className="text-sm text-[#AAAAAA] mt-1">
                        Temukan komik sesuai dengan kategori dan tema cerita ({genres.length} genre tersedia).
                    </p>
                </div>

                {/* Filter input */}
                <div className="relative sm:w-64">
                    <input
                        type="text"
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Saring nama genre..."
                        className="w-full bg-[#161616] text-xs text-[#F8F8F8] placeholder-[#777777] pl-3 pr-8 py-2 rounded-none border border-[#444444] focus:border-[#BAD306] focus:outline-none"
                    />
                    {search && (
                        <button
                            type="button"
                            onClick={() => setSearch('')}
                            className="absolute right-2.5 top-2 text-[#AAAAAA] hover:text-[#F8F8F8]"
                            aria-label="Hapus filter genre"
                        >
                            <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    )}
                </div>
            </header>

            {/* Genre Grid */}
            {filteredGenres.length === 0 ? (
                <div className="py-12 text-center text-sm text-[#AAAAAA] bg-[#161616] border border-[#222222] rounded-none">
                    Tidak ada genre yang cocok dengan pencarian "{search}".
                </div>
            ) : (
                <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
                    {filteredGenres.map((genre) => (
                        <Link
                            key={genre.slug}
                            href={`/genre/${genre.slug}`}
                            className="flex flex-col items-center justify-center p-4 bg-[#161616] border border-[#222222] hover:border-[#BAD306] hover:bg-[#1A1A1A] transition-colors group text-center rounded-none"
                        >
                            <span className="font-display text-base tracking-wider uppercase text-[#F8F8F8] group-hover:text-[#BAD306] transition-colors">
                                {genre.name}
                            </span>
                            <span className="text-xs text-[#777777] mt-1 font-mono">
                                #{genre.slug}
                            </span>
                        </Link>
                    ))}
                </div>
            )}
        </AppLayout>
    );
}
