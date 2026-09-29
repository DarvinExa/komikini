import React, { useState, useMemo } from 'react';
import { Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Genre } from '@/types';

interface GenresProps {
    genres: Genre[];
}

function parseGenreName(rawName: string): { cleanName: string; countLabel: string | null } {
    const match = rawName.match(/^(.*?)\s*\(([\d.,]+)\)$/);
    if (match) {
        return {
            cleanName: match[1].trim(),
            countLabel: match[2],
        };
    }
    return {
        cleanName: rawName.trim(),
        countLabel: null,
    };
}

const ALPHABET = ['#', ...'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.split('')];

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

    const { groupedGenres, activeLetters } = useMemo(() => {
        const groups: Record<string, Genre[]> = {};

        // Sort genres alphabetically by clean name
        const sorted = [...filteredGenres].sort((a, b) => {
            const { cleanName: nameA } = parseGenreName(a.name);
            const { cleanName: nameB } = parseGenreName(b.name);
            return nameA.localeCompare(nameB, 'id', { sensitivity: 'base' });
        });

        sorted.forEach((genre) => {
            const { cleanName } = parseGenreName(genre.name);
            const firstChar = cleanName.charAt(0).toUpperCase();
            const key = /^[A-Z]$/.test(firstChar) ? firstChar : '#';
            if (!groups[key]) {
                groups[key] = [];
            }
            groups[key].push(genre);
        });

        const active = new Set(Object.keys(groups));

        // Sort groups with '#' first, then A-Z
        const sortedKeys = Object.keys(groups).sort((a, b) => {
            if (a === '#') return -1;
            if (b === '#') return 1;
            return a.localeCompare(b);
        });

        const grouped = sortedKeys.map((letter) => ({
            letter,
            items: groups[letter],
        }));

        return { groupedGenres: grouped, activeLetters: active };
    }, [filteredGenres]);

    return (
        <AppLayout
            title="Daftar Genre Komik Terlengkap"
            description="Jelajahi ribuan komik berdasarkan kategori dan tema cerita favorit di Komikini."
            canonical="/genre"
        >
            <div id="top" className="scroll-mt-16">
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

                {/* Header */}
                <header className="mb-6 pb-4 border-b border-[#303030] flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h1 className="font-display font-bold text-3xl sm:text-4xl uppercase tracking-wider text-[#f3f3ef]">
                            Direktori Genre
                        </h1>
                        <p className="font-sans text-sm text-[#aaa9a3] mt-1">
                            Temukan komik sesuai kategori dan tema cerita favorit ({genres.length} genre terdaftar).
                        </p>
                    </div>

                    {/* Filter input */}
                    <div className="relative sm:w-72">
                        <input
                            type="text"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Saring nama genre..."
                            aria-label="Saring nama genre"
                            className="w-full bg-[#151515] text-xs text-[#f3f3ef] placeholder-[#777771] pl-3 pr-8 py-2.5 rounded-none border border-[#484848] focus:border-[#bdd600] focus:outline-none"
                        />
                        {search && (
                            <button
                                type="button"
                                onClick={() => setSearch('')}
                                className="absolute right-2.5 top-2.5 text-[#aaa9a3] hover:text-[#f3f3ef]"
                                aria-label="Hapus filter genre"
                            >
                                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        )}
                    </div>
                </header>

                {/* Alphabet Quick Bar */}
                <div className="mb-8 p-3 bg-[#151515] border border-[#303030]">
                    <div className="text-[11px] font-mono uppercase tracking-wider text-[#777771] mb-2">
                        Lompat Ke Karakter:
                    </div>
                    <div className="flex flex-wrap gap-1.5">
                        {ALPHABET.map((char) => {
                            const isAvailable = activeLetters.has(char);
                            if (isAvailable) {
                                return (
                                    <a
                                        key={char}
                                        href={`#letter-${char}`}
                                        className="w-8 h-8 flex items-center justify-center font-display font-bold text-sm bg-[#191919] border border-[#484848] text-[#f3f3ef] hover:border-[#bdd600] hover:text-[#bdd600] transition-colors focus:outline-none focus:border-[#bdd600]"
                                        title={`Lompat ke huruf ${char}`}
                                    >
                                        {char}
                                    </a>
                                );
                            }
                            return (
                                <span
                                    key={char}
                                    className="w-8 h-8 flex items-center justify-center font-display font-bold text-sm bg-[#151515] border border-[#262626] text-[#444440] select-none cursor-not-allowed"
                                >
                                    {char}
                                </span>
                            );
                        })}
                    </div>
                </div>

                {/* Grouped Genre List or Empty State */}
                {groupedGenres.length === 0 ? (
                    <div className="py-16 text-center bg-[#191919] border border-[#303030]">
                        <p className="font-sans text-sm text-[#aaa9a3]">
                            Tidak ada genre yang cocok dengan pencarian &quot;<span className="text-[#bdd600]">{search}</span>&quot;.
                        </p>
                        <button
                            type="button"
                            onClick={() => setSearch('')}
                            className="mt-4 px-4 py-2 bg-[#252525] border border-[#484848] text-xs font-semibold text-[#f3f3ef] hover:border-[#bdd600] hover:text-[#bdd600] uppercase tracking-wider transition-colors"
                        >
                            Reset Pencarian
                        </button>
                    </div>
                ) : (
                    <div className="space-y-10">
                        {groupedGenres.map((group) => (
                            <section
                                key={group.letter}
                                id={`letter-${group.letter}`}
                                className="scroll-mt-20 space-y-4"
                            >
                                {/* Section Header */}
                                <div className="flex items-center gap-3 pb-2 border-b border-[#303030]">
                                    <span className="w-9 h-9 flex items-center justify-center bg-[#191919] border border-[#484848] text-[#bdd600] font-display font-black text-xl select-none">
                                        {group.letter}
                                    </span>
                                    <div className="flex items-baseline gap-2">
                                        <h2 className="font-display font-bold text-xl uppercase tracking-wider text-[#f3f3ef]">
                                            {group.letter === '#' ? 'Angka & Simbol' : `Huruf ${group.letter}`}
                                        </h2>
                                        <span className="font-mono text-xs text-[#777771]">
                                            ({group.items.length} genre)
                                        </span>
                                    </div>
                                    <div className="flex-1 border-t border-[#262626] hidden sm:block"></div>
                                    <a
                                        href="#top"
                                        className="text-[11px] font-sans text-[#777771] hover:text-[#bdd600] transition-colors hidden sm:inline-block"
                                        title="Kembali ke atas"
                                    >
                                        ↑ Ke Atas
                                    </a>
                                </div>

                                {/* Items Grid */}
                                <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
                                    {group.items.map((genre) => {
                                        const { cleanName, countLabel } = parseGenreName(genre.name);
                                        return (
                                            <Link
                                                key={genre.slug}
                                                href={`/genre/${genre.slug}`}
                                                className="min-h-[58px] p-3 bg-[#191919] border border-[#484848] hover:border-[#bdd600] hover:bg-[#202020] transition-colors group flex flex-col items-center justify-center text-center rounded-none focus:outline-none focus:border-[#bdd600]"
                                            >
                                                <span className="font-sans text-sm font-semibold text-[#f3f3ef] group-hover:text-[#bdd600] transition-colors line-clamp-1">
                                                    {cleanName}
                                                </span>
                                                <span className="text-[11px] text-[#777771] font-mono mt-0.5 group-hover:text-[#aaa9a3]">
                                                    {countLabel ? `${countLabel} komik` : `#${genre.slug}`}
                                                </span>
                                            </Link>
                                        );
                                    })}
                                </div>
                            </section>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
