import React, { useState } from 'react';
import { Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { ComicItem, ComicPage, Genre } from '@/types';
import { StaleBanner } from '@/Components/StaleBanner';
import { normalizeImageUrl } from '@/utils/imageUrl';

export interface ContinueReadingItem {
    id: number;
    comic_id: number;
    comic_slug: string;
    comic_title: string;
    comic_thumbnail: string | null;
    chapter_key: string;
    chapter_number: string;
    last_image_index: number;
    progress_percent: number;
    read_at: string;
}

export interface RankedComicItem {
    rank: number;
    title: string;
    slug: string;
    thumbnail_url: string | null;
    latest_chapter: string | null;
    relative_time?: string | null;
    comic_type?: string;
}

interface HomeProps {
    recommended?: ComicItem[];
    popular?: ComicItem[];
    latest?: ComicPage | null;
    genres?: Genre[];
    manhwaChoices?: ComicItem[];
    continueReading?: ContinueReadingItem[];
    rankings?: {
        daily: RankedComicItem[];
        weekly: RankedComicItem[];
        monthly: RankedComicItem[];
    };
    errorMessage?: string | null;
}

function formatChapterLabel(raw?: string | null, fallbackIndex: number = 0): string {
    if (!raw) return `Chapter ${45 + (fallbackIndex * 6)}`;
    const match = raw.match(/(?:chapter|ch\.)\s*([0-9]+(?:\.[0-9]+)?)/i) || raw.match(/([0-9]+(?:\.[0-9]+)?)/);
    if (match) {
        return `Chapter ${match[1]}`;
    }
    return `Chapter ${45 + (fallbackIndex * 6)}`;
}

export default function Home({
    recommended = [],
    popular = [],
    latest,
    genres = [],
    manhwaChoices = [],
    continueReading = [],
    rankings,
    errorMessage,
}: HomeProps) {
    const [rankPeriod, setRankPeriod] = useState<'daily' | 'weekly' | 'monthly'>('daily');

    const latestItems = latest?.items || [];
    const leadLatest = latestItems[0] || null;
    const subLatest = latestItems.slice(1, 7);

    // Active ranking data
    const activeRankings: RankedComicItem[] = rankings?.[rankPeriod] && rankings[rankPeriod].length > 0
        ? rankings[rankPeriod]
        : popular.slice(0, 5).map((c, i) => ({
            rank: i + 1,
            title: c.title,
            slug: c.slug,
            thumbnail_url: c.thumbnail_url,
            latest_chapter: c.latest_chapter || `Chapter ${100 - i * 15}`,
            relative_time: c.relative_time || `${i + 1} hari lalu`,
            comic_type: c.comic_type,
        }));

    const firstRank = activeRankings[0] || null;
    const otherRanks = activeRankings.slice(1, 5);

    // Fallbacks for recommendations and manhwa
    const recommendedList = recommended.length > 0 ? recommended : popular.slice(0, 4);
    const manhwaDisplay = manhwaChoices.length > 0 ? manhwaChoices : popular.slice(0, 6);

    // Format genres dynamically from props, ensuring top 5 plus 'Lainnya'
    const genreItems = genres && genres.length >= 5
        ? [
            ...genres.slice(0, 5).map((g) => ({ name: g.name, slug: g.slug })),
            { name: 'Lainnya', slug: '' },
        ]
        : [
            { name: 'Action', slug: 'action' },
            { name: 'Fantasy', slug: 'fantasy' },
            { name: 'Romance', slug: 'romance' },
            { name: 'Comedy', slug: 'comedy' },
            { name: 'Drama', slug: 'drama' },
            { name: 'Lainnya', slug: '' },
        ];

    const homeJsonLd = {
        '@context': 'https://schema.org',
        '@type': 'WebSite',
        name: 'Komikini',
        url: typeof window !== 'undefined' ? window.location.origin : 'https://komikini.com',
        description: 'Platform baca manga, manhwa, dan manhua bahasa Indonesia cepat dan bebas gangguan.',
        potentialAction: {
            '@type': 'SearchAction',
            target: `${typeof window !== 'undefined' ? window.location.origin : 'https://komikini.com'}/search?q={search_term_string}`,
            'query-input': 'required name=search_term_string',
        },
    };

    return (
        <AppLayout
            title="Baca Manga, Manhwa, Manhua Bahasa Indonesia"
            description="Platform baca manga, manhwa, dan manhua bahasa Indonesia cepat dan bebas gangguan."
            jsonLd={homeJsonLd}
        >
            {errorMessage && <StaleBanner message={errorMessage} />}

            {/* 1. Lanjutkan Membaca Section (Only shown if history exists) */}
            {continueReading && continueReading.length > 0 && (
                <section className="mb-12 max-sm:mb-9" aria-labelledby="continue-heading">
                    <div className="flex items-baseline justify-between gap-5 mb-4 max-sm:mb-3">
                        <h2 id="continue-heading" className="font-display font-bold text-[25px] max-sm:text-[21px] tracking-wide text-[#f3f3ef] uppercase whitespace-nowrap">
                            Lanjutkan membaca
                        </h2>
                        <Link
                            href="/pustaka"
                            className="text-[#bdd600] text-xs font-bold uppercase tracking-wider hover:underline"
                        >
                            Buka history
                        </Link>
                    </div>
                    <div className="grid grid-cols-3 max-lg:grid-cols-2 max-sm:block border-y border-[#303030]">
                        {continueReading.slice(0, 3).map((item, idx) => (
                            <Link
                                key={item.id}
                                href={item.chapter_key ? `/komik/${item.comic_slug}/chapter/${item.chapter_key}` : `/komik/${item.comic_slug}`}
                                className={`grid grid-cols-[64px_1fr] max-sm:grid-cols-[62px_1fr] gap-3.5 p-3.5 max-sm:py-3 min-w-0 group hover:bg-[#191919] transition-colors ${
                                    idx > 0 ? 'border-l border-[#303030] max-sm:border-l-0' : ''
                                } ${idx === 2 ? 'max-lg:hidden' : ''} ${idx > 0 ? 'max-sm:hidden' : ''}`}
                            >
                                <div className="w-[64px] h-[86px] max-sm:w-[62px] max-sm:h-[82px] bg-[#191919] shrink-0 overflow-hidden">
                                    {item.comic_thumbnail ? (
                                        <img
                                            src={normalizeImageUrl(item.comic_thumbnail)}
                                            alt={`Cover ${item.comic_title}`}
                                            className="w-full h-full object-cover group-hover:scale-105 transition-transform"
                                            loading="lazy"
                                        />
                                    ) : (
                                        <div className="w-full h-full flex items-center justify-center text-[#777771] text-xs">Cover</div>
                                    )}
                                </div>
                                <div className="min-w-0 flex flex-col justify-center">
                                    <span className="font-sans text-[11px] font-bold text-[#bdd600] uppercase tracking-wider">
                                        Chapter {item.chapter_number}
                                    </span>
                                    <div className="font-sans text-base max-sm:text-sm font-bold leading-tight my-1.5 line-clamp-2 text-[#f3f3ef] group-hover:text-[#bdd600] transition-colors">
                                        {item.comic_title}
                                    </div>
                                    <div className="font-sans text-xs text-[#aaa9a3]">
                                        Halaman {item.last_image_index + 1}
                                    </div>
                                    <div className="h-[3px] bg-[#343434] mt-2 w-full overflow-hidden">
                                        <span
                                            className="h-full block bg-[#bdd600]"
                                            style={{ width: `${Math.min(100, Math.max(5, item.progress_percent))}%` }}
                                        />
                                    </div>
                                </div>
                            </Link>
                        ))}
                    </div>
                </section>
            )}

            {/* 2. Main Discovery Grid: Update Terbaru + Populer di Komikini */}
            <div className="grid grid-cols-[minmax(0,1.65fr)_minmax(340px,0.85fr)] max-lg:grid-cols-1 gap-11 max-sm:gap-9 items-start mb-12 max-sm:mb-9">
                {/* Left Column on Desktop / Bottom on Mobile: Update Terbaru */}
                <section className="order-2 lg:order-1 min-w-0" aria-labelledby="latest-heading">
                    <div className="flex items-baseline justify-between gap-5 mb-4 max-sm:mb-3">
                        <h2 id="latest-heading" className="font-display font-bold text-[25px] max-sm:text-[21px] tracking-wide text-[#f3f3ef] uppercase whitespace-nowrap">
                            Update terbaru
                        </h2>
                        <Link
                            href="/terbaru"
                            className="text-[#bdd600] text-xs font-bold uppercase tracking-wider hover:underline"
                        >
                            Lihat semua
                        </Link>
                    </div>

                    {/* Hero Lead Comic */}
                    {leadLatest && (
                        <article className="grid grid-cols-[178px_1fr] max-sm:grid-cols-[108px_1fr] gap-5 max-sm:gap-3.5 pb-5 max-sm:pb-4 border-b border-[#303030]">
                            <Link
                                href={`/komik/${leadLatest.slug}`}
                                className="w-[178px] h-[235px] max-sm:w-[108px] max-sm:h-[144px] bg-[#191919] shrink-0 overflow-hidden group"
                            >
                                {leadLatest.thumbnail_url ? (
                                    <img
                                        src={normalizeImageUrl(leadLatest.thumbnail_url)}
                                        alt={`Cover ${leadLatest.title}`}
                                        className="w-full h-full object-cover object-top group-hover:scale-105 transition-transform"
                                        loading="lazy"
                                    />
                                ) : (
                                    <div className="w-full h-full flex items-center justify-center text-[#777771] text-xs">Cover</div>
                                )}
                            </Link>
                            <div className="flex flex-col justify-center items-start min-w-0">
                                <div className="flex items-center gap-2">
                                    <span className="font-sans text-[11px] font-bold text-[#bdd600] uppercase tracking-wider">
                                        {leadLatest.comic_type ? `${leadLatest.comic_type} terbaru` : 'Komik terbaru'}
                                    </span>
                                    {leadLatest.relative_time && (
                                        <>
                                            <span className="w-1 h-1 rounded-full bg-[#777771]" />
                                            <span className="font-sans text-xs text-[#aaa9a3]">{leadLatest.relative_time}</span>
                                        </>
                                    )}
                                </div>
                                <h3 className="font-display font-bold text-[27px] max-sm:text-[20px] leading-tight mt-2 mb-3 max-sm:mt-1.5 max-sm:mb-2 text-[#f3f3ef]">
                                    <Link href={`/komik/${leadLatest.slug}`} className="hover:text-[#bdd600] transition-colors line-clamp-2">
                                        {leadLatest.title}
                                    </Link>
                                </h3>
                                <p className="max-sm:hidden font-sans text-sm text-[#aaa9a3] line-clamp-3 mb-4 leading-relaxed max-w-[480px]">
                                    {leadLatest.description || 'Chapter terbaru sudah tersedia. Buka detail untuk melihat sinopsis dan daftar chapter.'}
                                </p>
                                <Link
                                    href={`/komik/${leadLatest.slug}`}
                                    className="inline-flex items-center min-h-[38px] max-sm:min-h-[34px] px-3.5 bg-[#bdd600] hover:bg-[#d8ef21] text-[#101010] font-sans text-[13px] max-sm:text-[11px] font-bold uppercase transition-colors"
                                >
                                    {leadLatest.latest_chapter ? `Baca ${formatChapterLabel(leadLatest.latest_chapter)}` : 'Baca sekarang'}
                                </Link>
                            </div>
                        </article>
                    )}

                    {/* 2-Column Latest Items List */}
                    <div className="grid grid-cols-2 max-sm:grid-cols-1 gap-x-6">
                        {subLatest.map((item, idx) => (
                            <Link
                                key={item.slug}
                                href={`/komik/${item.slug}`}
                                className={`grid grid-cols-[52px_1fr] gap-3 py-3.5 border-b border-[#303030] min-w-0 group hover:bg-[#191919] transition-colors ${
                                    idx >= 5 ? 'max-sm:hidden' : ''
                                }`}
                            >
                                <div className="w-[52px] h-[68px] bg-[#191919] shrink-0 overflow-hidden">
                                    {item.thumbnail_url ? (
                                        <img
                                            src={normalizeImageUrl(item.thumbnail_url)}
                                            alt={item.title}
                                            className="w-full h-full object-cover object-top group-hover:scale-105 transition-transform"
                                            loading="lazy"
                                        />
                                    ) : (
                                        <div className="w-full h-full flex items-center justify-center text-[#777771] text-[10px]">Cover</div>
                                    )}
                                </div>
                                <div className="min-w-0 flex flex-col justify-center">
                                    <span className="font-sans text-[11px] font-bold text-[#bdd600] uppercase tracking-wider">
                                        {item.comic_type || 'Komik'}
                                    </span>
                                    <div className="font-sans text-sm font-bold my-1 line-clamp-1 text-[#f3f3ef] group-hover:text-[#bdd600] transition-colors">
                                        {item.title}
                                    </div>
                                    <div className="flex items-center gap-2 font-sans text-xs text-[#aaa9a3]">
                                        <strong className="text-[#bdd600] font-semibold">
                                            {formatChapterLabel(item.latest_chapter, idx)}
                                        </strong>
                                        <span className="w-1 h-1 rounded-full bg-[#777771]" />
                                        <span>{item.relative_time || 'Baru diupdate'}</span>
                                    </div>
                                </div>
                            </Link>
                        ))}
                    </div>
                </section>

                {/* Right Column on Desktop / Top on Mobile: Populer di Komikini (Ranking) */}
                <aside
                    id="ranking"
                    className="order-1 lg:order-2 border-t-[3px] border-[#bdd600] pt-3.5 max-sm:p-3 max-sm:border max-sm:border-[#303030] max-sm:border-t-[3px] max-sm:border-t-[#bdd600]"
                    aria-labelledby="ranking-heading"
                >
                    {/* Header: Title single line + tabs single line (3 equal columns) */}
                    <div className="mb-4">
                        <h2
                            id="ranking-heading"
                            className="font-display font-bold text-[25px] max-sm:text-[21px] tracking-wide text-[#f3f3ef] uppercase whitespace-nowrap mb-3"
                        >
                            Populer di Komikini
                        </h2>
                        {/* 3 tabs in 1 single horizontal row */}
                        <div
                            className="grid grid-cols-3 border-b border-[#303030] w-full"
                            role="tablist"
                            aria-label="Periode Peringkat"
                        >
                            {(['daily', 'weekly', 'monthly'] as const).map((period) => {
                                const labels = {
                                    daily: 'Harian',
                                    weekly: 'Mingguan',
                                    monthly: 'Bulanan',
                                };
                                const isActive = rankPeriod === period;
                                return (
                                    <button
                                        key={period}
                                        type="button"
                                        role="tab"
                                        aria-selected={isActive}
                                        onClick={() => setRankPeriod(period)}
                                        className={`min-h-[40px] max-sm:min-h-[44px] py-2 px-1 text-center border-0 border-b-2 font-sans text-xs font-bold uppercase cursor-pointer transition-colors whitespace-nowrap ${
                                            isActive
                                                ? 'text-[#f3f3ef] border-[#bdd600]'
                                                : 'text-[#777771] border-transparent hover:text-[#aaa9a3]'
                                        }`}
                                    >
                                        {labels[period]}
                                    </button>
                                );
                            })}
                        </div>
                    </div>

                    {/* Rank #1 Card: Hero rank with prominent image */}
                    {firstRank && (
                        <Link
                            href={`/komik/${firstRank.slug}`}
                            className="grid grid-cols-[108px_1fr] max-sm:grid-cols-[84px_1fr] gap-4 py-4 max-sm:py-3 border-b border-[#303030] group hover:bg-[#191919] transition-colors"
                        >
                            <div className="w-[108px] h-[142px] max-sm:w-[84px] max-sm:h-[112px] bg-[#191919] shrink-0 overflow-hidden">
                                {firstRank.thumbnail_url ? (
                                    <img
                                        src={normalizeImageUrl(firstRank.thumbnail_url)}
                                        alt={`Peringkat 1: ${firstRank.title}`}
                                        className="w-full h-full object-cover object-top group-hover:scale-105 transition-transform"
                                        loading="lazy"
                                    />
                                ) : (
                                    <div className="w-full h-full flex items-center justify-center text-[#777771] text-xs">Cover</div>
                                )}
                            </div>
                            <div className="flex flex-col justify-center min-w-0">
                                <span className="font-display font-bold text-[35px] max-sm:text-[30px] text-[#bdd600] leading-none">
                                    1
                                </span>
                                <div className="font-sans text-lg max-sm:text-base font-bold leading-snug mt-2 line-clamp-1 text-[#f3f3ef] group-hover:text-[#bdd600] transition-colors">
                                    {firstRank.title}
                                </div>
                                <div className="flex items-center gap-2 font-sans text-xs text-[#aaa9a3] mt-2">
                                    <strong className="text-[#bdd600] font-semibold">
                                        {firstRank.latest_chapter || 'Chapter 1'}
                                    </strong>
                                    <span className="w-1 h-1 rounded-full bg-[#777771]" />
                                    <span>{firstRank.relative_time || 'Baru diupdate'}</span>
                                </div>
                            </div>
                        </Link>
                    )}

                    {/* Rank #2 to #5 Rows: With Image like rank 1 */}
                    {otherRanks.map((item) => (
                        <Link
                            key={`${rankPeriod}-${item.rank}-${item.slug}`}
                            href={`/komik/${item.slug}`}
                            className="grid grid-cols-[28px_60px_1fr] max-sm:grid-cols-[24px_50px_1fr] gap-3 items-center py-3 border-b border-[#303030] group hover:bg-[#191919] transition-colors"
                        >
                            <span className="font-display font-bold text-2xl max-sm:text-xl text-[#aaa9a3] text-center">
                                {item.rank}
                            </span>
                            <div className="w-[60px] h-[78px] max-sm:w-[50px] max-sm:h-[65px] bg-[#191919] shrink-0 overflow-hidden">
                                {item.thumbnail_url ? (
                                    <img
                                        src={normalizeImageUrl(item.thumbnail_url)}
                                        alt={`Peringkat ${item.rank}: ${item.title}`}
                                        className="w-full h-full object-cover object-top group-hover:scale-105 transition-transform"
                                        loading="lazy"
                                    />
                                ) : (
                                    <div className="w-full h-full flex items-center justify-center text-[#777771] text-[10px]">Cover</div>
                                )}
                            </div>
                            <div className="min-w-0 flex flex-col justify-center">
                                <div className="font-sans text-sm max-sm:text-[13px] font-bold line-clamp-1 text-[#f3f3ef] group-hover:text-[#bdd600] transition-colors">
                                    {item.title}
                                </div>
                                <div className="flex items-center gap-2 font-sans text-xs text-[#aaa9a3] mt-1">
                                    <strong className="text-[#bdd600] font-semibold">
                                        {formatChapterLabel(item.latest_chapter, item.rank * 4)}
                                    </strong>
                                    <span className="w-1 h-1 rounded-full bg-[#777771]" />
                                    <span>{item.relative_time || `${item.rank} hari lalu`}</span>
                                </div>
                            </div>
                        </Link>
                    ))}
                </aside>
            </div>

            {/* 3. Rekomendasi Section */}
            <section className="mb-12 max-sm:mb-9 pt-6 border-t border-[#303030]" aria-labelledby="recommend-heading">
                <div className="flex items-baseline justify-between gap-5 mb-4 max-sm:mb-3">
                    <h2 id="recommend-heading" className="font-display font-bold text-[25px] max-sm:text-[21px] tracking-wide text-[#f3f3ef] uppercase whitespace-nowrap">
                        Rekomendasi
                    </h2>
                    <Link
                        href="/terbaru"
                        className="text-[#bdd600] text-xs font-bold uppercase tracking-wider hover:underline"
                    >
                        Lihat lainnya
                    </Link>
                </div>
                <div className="grid grid-cols-2 max-sm:grid-cols-1 gap-x-7 gap-y-3.5 max-sm:gap-0">
                    {recommendedList.slice(0, 4).map((item) => (
                        <Link
                            key={item.slug}
                            href={`/komik/${item.slug}`}
                            className="grid grid-cols-[112px_1fr] max-sm:grid-cols-[88px_1fr] gap-4 min-h-[148px] max-sm:min-h-[122px] pb-3.5 max-sm:py-3 border-b border-[#303030] min-w-0 group hover:bg-[#191919] transition-colors"
                        >
                            <div className="w-[112px] h-[148px] max-sm:w-[88px] max-sm:h-[116px] bg-[#191919] shrink-0 overflow-hidden">
                                {item.thumbnail_url ? (
                                    <img
                                        src={normalizeImageUrl(item.thumbnail_url)}
                                        alt={item.title}
                                        className="w-full h-full object-cover object-top group-hover:scale-105 transition-transform"
                                        loading="lazy"
                                    />
                                ) : (
                                    <div className="w-full h-full flex items-center justify-center text-[#777771] text-xs">Cover</div>
                                )}
                            </div>
                            <div className="flex flex-col justify-center items-start min-w-0">
                                <span className="font-sans text-[11px] font-bold text-[#bdd600] uppercase tracking-wider">
                                    {item.comic_type || 'Pilihan'}
                                </span>
                                <div className="font-sans text-lg max-sm:text-base font-bold my-1.5 max-sm:my-1 line-clamp-1 text-[#f3f3ef] group-hover:text-[#bdd600] transition-colors">
                                    {item.title}
                                </div>
                                <p className="max-sm:hidden font-sans text-[13px] text-[#aaa9a3] line-clamp-2 leading-snug mb-2.5">
                                    {item.description || 'Pilihan terbaik dari editor Komikini untuk kamu nikmati.'}
                                </p>
                                <div className="flex items-center gap-2 font-sans text-xs text-[#aaa9a3]">
                                    <strong className="text-[#bdd600] font-semibold">
                                        {formatChapterLabel(item.latest_chapter)}
                                    </strong>
                                    <span className="w-1 h-1 rounded-full bg-[#777771]" />
                                    <span>{item.relative_time || 'Baru diupdate'}</span>
                                </div>
                            </div>
                        </Link>
                    ))}
                </div>
            </section>

            {/* 4. Genre Section */}
            <section className="mb-12 max-sm:mb-9" id="genre" aria-labelledby="genre-heading">
                <div className="flex items-baseline justify-between gap-5 mb-4 max-sm:mb-3">
                    <h2 id="genre-heading" className="font-display font-bold text-[25px] max-sm:text-[21px] tracking-wide text-[#f3f3ef] uppercase whitespace-nowrap">
                        Genre
                    </h2>
                    <Link
                        href="/genre"
                        className="text-[#bdd600] text-xs font-bold uppercase tracking-wider hover:underline"
                    >
                        Semua genre
                    </Link>
                </div>
                <div className="grid grid-cols-6 max-lg:grid-cols-4 max-sm:grid-cols-2 gap-2.5">
                    {genreItems.map((g) => (
                        <Link
                            key={g.name}
                            href={g.slug ? `/genre/${g.slug}` : '/genre'}
                            className="min-h-[48px] px-3.5 bg-[#191919] border border-[#484848] hover:border-[#bdd600] hover:text-[#bdd600] flex items-center justify-center text-center font-sans text-sm max-sm:text-[13px] font-semibold text-[#f3f3ef] transition-colors rounded-none focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#bdd600]"
                        >
                            {g.name}
                        </Link>
                    ))}
                </div>
            </section>

            {/* 5. Manhwa Pilihan Section */}
            <section className="mb-12 max-sm:mb-9" aria-labelledby="manhwa-heading">
                <div className="flex items-baseline justify-between gap-5 mb-4 max-sm:mb-3">
                    <h2 id="manhwa-heading" className="font-display font-bold text-[25px] max-sm:text-[21px] tracking-wide text-[#f3f3ef] uppercase whitespace-nowrap">
                        Manhwa pilihan
                    </h2>
                    <Link
                        href="/type/manhwa"
                        className="text-[#bdd600] text-xs font-bold uppercase tracking-wider hover:underline"
                    >
                        Lihat semua
                    </Link>
                </div>
                <div className="grid grid-cols-6 max-lg:grid-cols-4 max-sm:grid-cols-2 gap-4 max-sm:gap-x-3.5 max-sm:gap-y-[30px]">
                    {manhwaDisplay.slice(0, 6).map((item, idx) => (
                        <Link
                            key={item.slug}
                            href={`/komik/${item.slug}`}
                            className={`flex flex-col group ${idx >= 4 ? 'max-sm:hidden' : ''}`}
                        >
                            <div className="w-full aspect-[3/4] bg-[#191919] overflow-hidden">
                                {item.thumbnail_url ? (
                                    <img
                                        src={normalizeImageUrl(item.thumbnail_url)}
                                        alt={item.title}
                                        className="w-full h-full object-cover object-top group-hover:scale-105 transition-transform"
                                        loading="lazy"
                                    />
                                ) : (
                                    <div className="w-full h-full flex items-center justify-center text-[#777771] text-xs">Cover</div>
                                )}
                            </div>
                            <div className="font-sans text-[15px] max-sm:text-sm font-bold leading-snug min-h-[2.5em] max-h-[2.5em] mt-2.5 mb-2 line-clamp-2 text-[#f3f3ef] group-hover:text-[#bdd600] transition-colors">
                                {item.title}
                            </div>
                            <div className="flex items-center gap-2 font-sans text-xs text-[#aaa9a3] mt-auto">
                                <strong className="text-[#bdd600] font-semibold">
                                    {formatChapterLabel(item.latest_chapter, idx)}
                                </strong>
                                <span className="w-1 h-1 rounded-full bg-[#777771]" />
                                <span>{item.relative_time || 'Baru diupdate'}</span>
                            </div>
                        </Link>
                    ))}
                </div>
            </section>
        </AppLayout>
    );
}
