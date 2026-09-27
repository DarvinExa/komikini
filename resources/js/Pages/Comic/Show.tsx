import React, { useState } from 'react';
import { Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { ComicDetail } from '@/types';
import { ChapterList } from '@/Components/ChapterList';

interface ShowProps {
    comic: ComicDetail;
}

export default function Show({ comic }: ShowProps) {
    const [isSynopsisExpanded, setIsSynopsisExpanded] = useState(false);

    const cleanSynopsis = comic.synopsis?.trim() || 'Sinopsis belum tersedia untuk judul ini.';
    const isSynopsisLong = cleanSynopsis.length > 280;

    return (
        <AppLayout
            title={`Komik ${comic.title} Bahasa Indonesia`}
            description={cleanSynopsis.slice(0, 160)}
        >
            {/* Clear Back Button */}
            <div className="mb-6">
                <Link
                    href="/"
                    className="inline-flex items-center gap-2 font-display text-sm tracking-wider uppercase text-[#AAAAAA] hover:text-[#BAD306] transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#BAD306]"
                >
                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span>Kembali ke Beranda</span>
                </Link>
            </div>

            {/* Main Comic Detail Panel with 2px border */}
            <article className="bg-[#161616] border-2 border-[#444444] p-4 sm:p-6 mb-8 rounded-none">
                <div className="grid grid-cols-1 md:grid-cols-12 gap-6 lg:gap-8">
                    {/* Left Column: Cover & Quick Metadata */}
                    <div className="md:col-span-4 lg:col-span-3 flex flex-col">
                        <div className="relative aspect-[3/4] w-full bg-[#111111] border-2 border-[#444444] overflow-hidden">
                            {comic.thumbnail_url ? (
                                <img
                                    src={comic.thumbnail_url}
                                    alt={`Sampul komik ${comic.title}`}
                                    className="h-full w-full object-cover rounded-none"
                                    onError={(e) => {
                                        (e.target as HTMLImageElement).style.display = 'none';
                                    }}
                                />
                            ) : (
                                <div className="flex h-full w-full items-center justify-center font-display text-xs text-[#777777] uppercase tracking-wider">
                                    Tanpa Sampul
                                </div>
                            )}
                            <span className="absolute top-2 left-2 px-2 py-0.5 font-display text-xs tracking-wider uppercase bg-[#111111] text-[#F8F8F8] border border-[#444444] rounded-none">
                                {comic.comic_type}
                            </span>
                        </div>

                        {/* Quick Metadata Box */}
                        <div className="mt-4 p-3 bg-[#111111] border border-[#222222] space-y-2 text-xs">
                            {comic.author && (
                                <div className="flex justify-between border-b border-[#222222] pb-1.5">
                                    <span className="text-[#AAAAAA]">Pengarang:</span>
                                    <span className="text-[#F8F8F8] font-medium text-right">{comic.author}</span>
                                </div>
                            )}
                            {comic.publication_status && (
                                <div className="flex justify-between border-b border-[#222222] pb-1.5">
                                    <span className="text-[#AAAAAA]">Status:</span>
                                    <span className="text-[#BAD306] font-medium uppercase">{comic.publication_status}</span>
                                </div>
                            )}
                            <div className="flex justify-between border-b border-[#222222] pb-1.5">
                                <span className="text-[#AAAAAA]">Total Chapter:</span>
                                <span className="text-[#F8F8F8] font-medium">{comic.chapters.length}</span>
                            </div>
                            {comic.latest_chapter && (
                                <div className="flex justify-between">
                                    <span className="text-[#AAAAAA]">Terbaru:</span>
                                    <span className="text-[#BAD306] font-medium truncate max-w-[140px]">
                                        {comic.latest_chapter.title || `Ch. ${comic.latest_chapter.chapter_number}`}
                                    </span>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Right Column: Title, Metadata, Actions, and Synopsis */}
                    <div className="md:col-span-8 lg:col-span-9 flex flex-col justify-between">
                        <div>
                            <div className="flex items-center gap-2 mb-1">
                                <span className="font-display text-xs tracking-wider uppercase text-[#BAD306]">
                                    {comic.comic_type}
                                </span>
                                {comic.publication_status && (
                                    <>
                                        <span className="text-[#444444]">•</span>
                                        <span className="font-display text-xs tracking-wider uppercase text-[#AAAAAA]">
                                            {comic.publication_status}
                                        </span>
                                    </>
                                )}
                            </div>

                            <h1 className="font-display text-3xl sm:text-4xl lg:text-5xl uppercase tracking-wider text-[#F8F8F8] leading-tight mb-2">
                                {comic.title}
                            </h1>

                            {comic.alternative_title && (
                                <p className="text-sm text-[#AAAAAA] italic mb-4">
                                    {comic.alternative_title}
                                </p>
                            )}

                            {/* Genre Tags */}
                            {comic.genres.length > 0 && (
                                <div className="flex flex-wrap gap-1.5 mb-6" aria-label="Genre komik">
                                    {comic.genres.map((genre) => (
                                        <Link
                                            key={genre.slug}
                                            href={`/genre/${genre.slug}`}
                                            className="px-2.5 py-1 font-display text-xs tracking-wider uppercase bg-[#111111] text-[#AAAAAA] border border-[#333333] hover:border-[#BAD306] hover:text-[#F8F8F8] transition-colors rounded-none"
                                        >
                                            {genre.name}
                                        </Link>
                                    ))}
                                </div>
                            )}

                            {/* Action Buttons */}
                            <div className="flex flex-wrap items-center gap-3 mb-6">
                                <a
                                    href="#chapter-list"
                                    className="inline-flex items-center justify-center px-6 py-3 font-display text-base tracking-wider uppercase bg-[#BAD306] hover:bg-[#E0FF00] text-[#111111] border-2 border-[#BAD306] transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#BAD306] rounded-none"
                                >
                                    Mulai Baca
                                </a>
                                <button
                                    type="button"
                                    onClick={() => alert('Fitur bookmark akun akan tersedia pada modul berikutnya.')}
                                    className="inline-flex items-center justify-center px-6 py-3 font-display text-base tracking-wider uppercase bg-[#111111] hover:border-[#BAD306] hover:text-[#BAD306] text-[#F8F8F8] border border-[#444444] transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#BAD306] rounded-none"
                                >
                                    Bookmark
                                </button>
                            </div>
                        </div>

                        {/* Synopsis */}
                        <div className="pt-4 border-t border-[#222222]">
                            <h2 className="font-display text-lg uppercase tracking-wider text-[#AAAAAA] mb-2">
                                Sinopsis
                            </h2>
                            <p className={`text-sm sm:text-base text-[#F8F8F8] leading-relaxed ${!isSynopsisExpanded && isSynopsisLong ? 'line-clamp-4' : ''}`}>
                                {cleanSynopsis}
                            </p>
                            {isSynopsisLong && (
                                <button
                                    type="button"
                                    onClick={() => setIsSynopsisExpanded(!isSynopsisExpanded)}
                                    className="mt-2 font-display text-xs uppercase tracking-wider text-[#BAD306] hover:text-[#E0FF00] focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#BAD306]"
                                >
                                    {isSynopsisExpanded ? 'Sembunyikan' : 'Baca Selengkapnya'}
                                </button>
                            )}
                        </div>
                    </div>
                </div>
            </article>

            {/* Interactive Chapter List in Separate Panel */}
            <ChapterList
                comicSlug={comic.slug}
                chapters={comic.chapters}
            />
        </AppLayout>
    );
}
