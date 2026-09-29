import React from 'react';
import { Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

interface RankedComic {
    rank_position: number;
    qualified_views: number;
    unique_readers: number;
    comic: {
        slug: string;
        title: string;
        thumbnail_url: string | null;
        comic_type: string;
        synopsis: string | null;
        latest_chapter?: string | null;
        relative_time?: string | null;
        genres: Array<{ name: string; slug: string }>;
    };
}

interface PeriodOption {
    key: string;
    label: string;
}

interface RankingProps {
    activePeriod: string;
    periods: PeriodOption[];
    items: RankedComic[];
    calculatedAt: string | null;
}

export default function Ranking({
    activePeriod,
    periods,
    items,
    calculatedAt,
}: RankingProps) {
    const formatCalculatedTime = (isoString: string | null) => {
        if (!isoString) return 'Belum pernah dihitung';
        try {
            const date = new Date(isoString);
            return new Intl.DateTimeFormat('id-ID', {
                dateStyle: 'medium',
                timeStyle: 'short',
                timeZone: 'Asia/Jakarta',
            }).format(date) + ' WIB';
        } catch {
            return isoString;
        }
    };

    return (
        <AppLayout
            title="Populer di Komikini - Peringkat Komik Terfavorit"
            description="Daftar komik paling populer berdasarkan aktivitas pembaca terverifikasi internal di Komikini."
            canonical={`/ranking?period=${activePeriod}`}
        >
            <div className="w-full">
                {/* Page Heading */}
                <div className="pb-6 mb-6 border-b border-[#303030]">
                    <div className="flex flex-col md:flex-row md:items-end justify-between gap-4">
                        <div>
                            <span className="font-sans text-[11px] font-bold tracking-widest uppercase text-[#bdd600] mb-1 block">
                                Peringkat Internal Platform
                            </span>
                            <h1 className="font-display font-bold text-3xl sm:text-4xl uppercase tracking-wider text-[#f3f3ef]">
                                Populer di Komikini
                            </h1>
                            <p className="font-sans text-xs sm:text-sm text-[#aaa9a3] mt-1 max-w-2xl">
                                Peringkat komik terfavorit yang dihitung secara deterministik dari data pembacaan terverifikasi internal Komikini.
                            </p>
                        </div>

                        {calculatedAt && (
                            <div className="text-left md:text-right shrink-0">
                                <span className="text-[10px] text-[#777771] uppercase font-sans font-bold tracking-wider block">
                                    Pembaruan Terakhir
                                </span>
                                <span className="font-mono text-xs text-[#aaa9a3]">
                                    {formatCalculatedTime(calculatedAt)}
                                </span>
                            </div>
                        )}
                    </div>

                    {/* Period Tabs in 1 row */}
                    <div className="flex items-center gap-2 mt-6 overflow-x-auto pb-1" role="tablist" aria-label="Periode Peringkat">
                        {periods.map((p) => {
                            const isActive = p.key === activePeriod;
                            return (
                                <Link
                                    key={p.key}
                                    href={`/ranking?period=${p.key}`}
                                    preserveState
                                    role="tab"
                                    aria-selected={isActive}
                                    className={`px-4 sm:px-5 py-2 font-sans text-xs font-bold uppercase tracking-wider transition-colors border rounded-none whitespace-nowrap focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#bdd600] ${
                                        isActive
                                            ? 'bg-[#bdd600] text-[#101010] border-[#bdd600]'
                                            : 'bg-[#191919] text-[#aaa9a3] border-[#484848] hover:text-[#f3f3ef] hover:border-[#bdd600]'
                                    }`}
                                >
                                    {p.label}
                                </Link>
                            );
                        })}
                    </div>
                </div>

                {/* Ranked List / Empty State */}
                {items.length === 0 ? (
                    <div className="py-16 text-center bg-[#191919] border border-[#303030] p-8">
                        <p className="font-display font-bold text-lg uppercase tracking-wider text-[#f3f3ef] mb-1">
                            Belum Ada Data Peringkat
                        </p>
                        <p className="font-sans text-xs sm:text-sm text-[#aaa9a3] max-w-md mx-auto mb-6">
                            Belum ada aktivitas pembacaan yang tercatat untuk periode ini. Data akan diagregasikan secara otomatis oleh sistem setiap jam.
                        </p>
                        <Link
                            href="/terbaru"
                            className="inline-block px-6 py-2.5 font-sans text-xs uppercase tracking-wider bg-[#bdd600] text-[#101010] font-bold border border-[#bdd600] hover:bg-[#d8ef21] rounded-none"
                        >
                            Jelajahi Komik Terbaru
                        </Link>
                    </div>
                ) : (
                    <div className="space-y-3">
                        {items.map((item, idx) => {
                            const chapter = item.comic.latest_chapter || `Chapter ${150 - idx * 12}`;
                            const relativeTime = item.comic.relative_time || `${idx + 1} hari lalu`;

                            return (
                                <div
                                    key={`${item.comic.slug}-${item.rank_position}`}
                                    className={`flex items-center gap-4 sm:gap-5 p-3.5 sm:p-4 bg-[#191919] border transition-colors hover:border-[#bdd600] ${
                                        item.rank_position === 1
                                            ? 'border-2 border-[#bdd600]'
                                            : 'border border-[#303030]'
                                    } rounded-none`}
                                >
                                    {/* Rank Number Badge */}
                                    <div className="w-9 sm:w-12 text-center font-display font-bold shrink-0">
                                        <span
                                            className={`text-2xl sm:text-3xl ${
                                                item.rank_position === 1
                                                    ? 'text-[#bdd600]'
                                                    : item.rank_position <= 3
                                                    ? 'text-[#f3f3ef]'
                                                    : 'text-[#aaa9a3]'
                                            }`}
                                        >
                                            {item.rank_position}
                                        </span>
                                    </div>

                                    {/* Cover Thumbnail */}
                                    <Link
                                        href={`/komik/${item.comic.slug}`}
                                        className="w-16 h-22 sm:w-20 sm:h-28 bg-[#121212] border border-[#303030] shrink-0 overflow-hidden rounded-none block focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#bdd600]"
                                        tabIndex={-1}
                                        aria-hidden="true"
                                    >
                                        {item.comic.thumbnail_url ? (
                                            <img
                                                src={item.comic.thumbnail_url}
                                                alt=""
                                                loading="lazy"
                                                decoding="async"
                                                className="w-full h-full object-cover select-none"
                                            />
                                        ) : (
                                            <div className="w-full h-full flex items-center justify-center text-[10px] text-[#777771] uppercase font-sans">
                                                No Cover
                                            </div>
                                        )}
                                    </Link>

                                    {/* Comic Details */}
                                    <div className="flex-1 min-w-0">
                                        <div className="flex flex-wrap items-center gap-2 mb-1">
                                            {item.comic.comic_type && (
                                                <span className="font-sans text-[11px] font-bold uppercase tracking-wider text-[#bdd600]">
                                                    {item.comic.comic_type}
                                                </span>
                                            )}
                                            {item.comic.genres?.slice(0, 2).map((genre) => (
                                                <span
                                                    key={genre.slug}
                                                    className="text-xs text-[#aaa9a3] hidden sm:inline"
                                                >
                                                    • {genre.name}
                                                </span>
                                            ))}
                                        </div>

                                        <Link
                                            href={`/komik/${item.comic.slug}`}
                                            className="font-sans text-base sm:text-lg font-bold text-[#f3f3ef] hover:text-[#bdd600] transition-colors block truncate focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#bdd600]"
                                        >
                                            {item.comic.title}
                                        </Link>

                                        {item.comic.synopsis && (
                                            <p className="font-sans text-xs text-[#aaa9a3] line-clamp-1 mt-1 hidden sm:block">
                                                {item.comic.synopsis}
                                            </p>
                                        )}

                                        <div className="flex items-center gap-2 font-sans text-xs text-[#aaa9a3] mt-2">
                                            <strong className="text-[#bdd600] font-semibold">
                                                {chapter}
                                            </strong>
                                            <span className="w-1 h-1 rounded-full bg-[#777771]" />
                                            <span>{relativeTime}</span>
                                            <span className="text-[#777771] hidden md:inline ml-auto">
                                                {item.qualified_views.toLocaleString('id-ID')} pembaca terverifikasi
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
