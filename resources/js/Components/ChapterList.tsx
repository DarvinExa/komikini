import React, { useState, useMemo } from 'react';
import { Link } from '@inertiajs/react';
import { ChapterItem } from '@/types';

interface ChapterListProps {
    comicSlug?: string;
    chapters: ChapterItem[];
    activeChapterKey?: string | null;
}

export const ChapterList: React.FC<ChapterListProps> = ({ comicSlug, chapters, activeChapterKey }) => {
    const [search, setSearch] = useState('');
    const [isAscending, setIsAscending] = useState(false);

    const filteredChapters = useMemo(() => {
        let result = [...chapters];

        if (search.trim()) {
            const q = search.toLowerCase().trim();
            result = result.filter(
                (ch) =>
                    ch.title.toLowerCase().includes(q) ||
                    ch.chapter_number.toLowerCase().includes(q) ||
                    ch.chapter_key.toLowerCase().includes(q)
            );
        }

        if (isAscending) {
            result.reverse();
        }

        return result;
    }, [chapters, search, isAscending]);

    return (
        <section
            id="chapter-list"
            aria-labelledby="chapter-list-heading"
            data-comic-slug={comicSlug}
            className="bg-[#161616] border-2 border-[#444444] p-4 sm:p-6 rounded-none"
        >
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 pb-4 border-b border-[#222222]">
                <div>
                    <h2 id="chapter-list-heading" className="font-display text-2xl uppercase tracking-wider text-[#F8F8F8]">
                        Daftar Chapter
                    </h2>
                    <p className="text-xs text-[#AAAAAA] mt-0.5">
                        Tersedia {chapters.length} chapter
                    </p>
                </div>

                <div className="flex items-center gap-2">
                    {/* Chapter filter input */}
                    <div className="relative flex-1 sm:w-48">
                        <input
                            type="text"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Cari chapter..."
                            className="w-full bg-[#111111] text-xs text-[#F8F8F8] placeholder-[#777777] pl-3 pr-8 py-2 rounded-none border border-[#444444] focus:border-[#BAD306] focus:outline-none"
                        />
                        {search && (
                            <button
                                type="button"
                                onClick={() => setSearch('')}
                                className="absolute right-2.5 top-2 text-[#AAAAAA] hover:text-[#F8F8F8]"
                                aria-label="Hapus filter chapter"
                            >
                                <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        )}
                    </div>

                    {/* Sort order toggle without unicode arrows */}
                    <button
                        type="button"
                        onClick={() => setIsAscending(!isAscending)}
                        className="px-3 py-2 font-display text-xs tracking-wider uppercase text-[#F8F8F8] bg-[#111111] border border-[#444444] hover:border-[#BAD306] hover:text-[#BAD306] transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#BAD306] shrink-0 rounded-none"
                    >
                        {isAscending ? 'Urutan: Pertama' : 'Urutan: Terbaru'}
                    </button>
                </div>
            </div>

            {filteredChapters.length === 0 ? (
                <div className="py-8 text-center text-sm text-[#AAAAAA] bg-[#111111] border border-[#222222]">
                    Tidak ada chapter yang cocok dengan pencarian "{search}".
                </div>
            ) : (
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5 max-h-[500px] overflow-y-auto pr-1">
                    {filteredChapters.map((ch) => {
                        const chapterHref = comicSlug ? `/komik/${comicSlug}/${ch.chapter_key}` : '#';
                        const isLastRead = ch.chapter_key === activeChapterKey;
                        return (
                            <Link
                                key={ch.chapter_key}
                                href={chapterHref}
                                className={`flex items-center justify-between p-3 bg-[#111111] border transition-colors group rounded-none focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#BAD306] ${
                                    isLastRead
                                        ? 'border-[#BAD306] bg-[#1a1f0a]'
                                        : 'border-[#222222] hover:border-[#BAD306]'
                                }`}
                            >
                                <div className="truncate mr-2">
                                    <div className="flex items-center gap-1.5">
                                        <span className={`text-sm font-medium truncate block ${isLastRead ? 'text-[#BAD306]' : 'text-[#F8F8F8] group-hover:text-[#BAD306]'}`}>
                                            {ch.title || `Chapter ${ch.chapter_number}`}
                                        </span>
                                        {isLastRead && (
                                            <span className="font-display text-[10px] tracking-wider uppercase px-1.5 py-0.5 bg-[#BAD306] text-[#111111] font-bold shrink-0">
                                                Dibaca
                                            </span>
                                        )}
                                    </div>
                                    {ch.release_date && (
                                        <span className="text-xs text-[#777777]">
                                            {ch.release_date}
                                        </span>
                                    )}
                                </div>
                                <span className={`font-display text-xs tracking-wider uppercase px-2.5 py-1 transition-colors shrink-0 rounded-none ${
                                    isLastRead
                                        ? 'bg-[#BAD306] text-[#111111] font-bold'
                                        : 'bg-[#222222] group-hover:bg-[#BAD306] group-hover:text-[#111111] text-[#AAAAAA]'
                                }`}>
                                    Ch. {ch.chapter_number}
                                </span>
                            </Link>
                        );
                    })}
                </div>
            )}
        </section>
    );
};
