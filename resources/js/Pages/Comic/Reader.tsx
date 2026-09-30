import React, { useState, useEffect, useRef } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import { ChapterPayload, PageProps } from '@/types';
import CommentSection from '@/Components/CommentSection';
import { SeoHead } from '@/Components/SeoHead';

interface ReaderProps {
    comic: {
        slug: string;
        title: string;
    };
    chapter: ChapterPayload;
    initialIndex?: number;
}

export default function Reader({ comic, chapter, initialIndex = 0 }: ReaderProps) {
    const { auth } = usePage<PageProps>().props;
    const images = chapter.images || [];
    const totalPages = images.length;

    // Track failed image indices to show individual retry buttons
    const [failedImages, setFailedImages] = useState<Record<number, boolean>>({});
    // Retry cache-buster counter per image
    const [retryCounters, setRetryCounters] = useState<Record<number, number>>({});
    // Track active page currently in viewport
    const [activePage, setActivePage] = useState<number>(initialIndex > 0 ? initialIndex + 1 : 1);
    // Track scroll percentage
    const [scrollPercent, setScrollPercent] = useState<number>(0);
    // Floating controls visibility (auto-hide on scroll down, reveal on scroll up or tap)
    const [showControls, setShowControls] = useState<boolean>(true);
    const lastScrollY = useRef<number>(0);

    const toggleControls = () => {
        setShowControls((prev) => !prev);
    };

    const imageRefs = useRef<(HTMLDivElement | null)[]>([]);

    // Scroll to initial index on mount if resuming reading
    useEffect(() => {
        if (initialIndex > 0 && imageRefs.current[initialIndex]) {
            const timeout = setTimeout(() => {
                imageRefs.current[initialIndex]?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 400);
            return () => clearTimeout(timeout);
        }
    }, [initialIndex]);

    // Debounced reading progress synchronization
    useEffect(() => {
        if (!auth.user || totalPages === 0) return;

        const timer = setTimeout(() => {
            const lastImageIndex = activePage - 1;
            const progressPercent = Math.min(100, Math.round((activePage / totalPages) * 100));
            const isCompleted = progressPercent >= 95 || activePage === totalPages;

            fetch('/library/progress', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
                },
                body: JSON.stringify({
                    comic_slug: comic.slug,
                    chapter_key: chapter.chapter_key,
                    chapter_number: chapter.chapter_number,
                    last_image_index: lastImageIndex,
                    progress_percent: progressPercent,
                    completed: isCompleted,
                }),
            }).catch(() => {
                // Non-blocking fail-safe
            });
        }, 2000);

        return () => clearTimeout(timer);
    }, [activePage, auth.user, comic.slug, chapter.chapter_key, chapter.chapter_number, totalPages]);

    // Keyboard navigation (ArrowLeft = prev chapter, ArrowRight = next chapter)
    useEffect(() => {
        const handleKeyDown = (e: KeyboardEvent) => {
            // Ignore if focus is in an input or textarea
            if (['INPUT', 'TEXTAREA'].includes((e.target as HTMLElement)?.tagName)) {
                return;
            }

            if (e.key === 'ArrowLeft' && chapter.prev_chapter_key) {
                router.visit(`/komik/${comic.slug}/${chapter.prev_chapter_key}`);
            } else if (e.key === 'ArrowRight' && chapter.next_chapter_key) {
                router.visit(`/komik/${comic.slug}/${chapter.next_chapter_key}`);
            }
        };

        window.addEventListener('keydown', handleKeyDown);
        return () => window.removeEventListener('keydown', handleKeyDown);
    }, [chapter.prev_chapter_key, chapter.next_chapter_key, comic.slug]);

    // Scroll progress tracker & auto-hide controls on scroll down / show on scroll up
    useEffect(() => {
        const handleScroll = () => {
            const currentScrollY = window.scrollY || document.documentElement.scrollTop;
            const scrollHeight = document.documentElement.scrollHeight - window.innerHeight;
            if (scrollHeight > 0) {
                const percent = Math.min(100, Math.max(0, Math.round((currentScrollY / scrollHeight) * 100)));
                setScrollPercent(percent);
            }

            const diff = currentScrollY - lastScrollY.current;

            // When near the very top of page, always show controls
            if (currentScrollY < 60) {
                setShowControls(true);
            } else if (Math.abs(diff) > 12) {
                if (diff > 0) {
                    // Scrolling DOWN -> hide controls for distraction-free reading
                    setShowControls(false);
                } else {
                    // Scrolling UP -> reveal controls for easy navigation
                    setShowControls(true);
                }
            }

            lastScrollY.current = currentScrollY;
        };

        window.addEventListener('scroll', handleScroll, { passive: true });
        return () => window.removeEventListener('scroll', handleScroll);
    }, []);

    // Intersection observer to track current visible page
    useEffect(() => {
        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        const pageNum = Number(entry.target.getAttribute('data-page-index'));
                        if (pageNum) {
                            setActivePage(pageNum);
                        }
                    }
                });
            },
            {
                rootMargin: '-30% 0px -50% 0px',
                threshold: 0,
            }
        );

        imageRefs.current.forEach((el) => {
            if (el) observer.observe(el);
        });

        return () => observer.disconnect();
    }, [images]);

    const handleImageError = (index: number) => {
        setFailedImages((prev) => ({ ...prev, [index]: true }));
    };

    const handleRetryImage = (index: number) => {
        setFailedImages((prev) => ({ ...prev, [index]: false }));
        setRetryCounters((prev) => ({ ...prev, [index]: (prev[index] || 0) + 1 }));
    };

    const getImageSrc = (url: string, index: number) => {
        const count = retryCounters[index] || 0;
        if (count === 0) return url;
        const separator = url.includes('?') ? '&' : '?';
        return `${url}${separator}retry=${count}`;
    };

    const chapterJsonLd = {
        '@context': 'https://schema.org',
        '@type': 'Chapter',
        name: chapter.title || `Chapter ${chapter.chapter_number}`,
        isPartOf: {
            '@type': 'ComicSeries',
            name: comic.title,
        },
        inLanguage: 'id',
    };

    return (
        <div className="min-h-screen bg-[#0A0A0A] text-[#F8F8F8] font-sans antialiased selection:bg-[#BAD306] selection:text-[#111111] overflow-x-hidden w-full max-w-full">
            <SeoHead
                title={`${comic.title} Chapter ${chapter.chapter_number} Bahasa Indonesia`}
                description={`Baca komik ${comic.title} Chapter ${chapter.chapter_number} Bahasa Indonesia secara online di Komikini.`}
                canonical={`/komik/${comic.slug}/${chapter.chapter_key}`}
                ogType="article"
                jsonLd={chapterJsonLd}
            />

            {/* Reading Scroll Progress Bar at very top */}
            <div className="fixed top-0 left-0 right-0 z-50 h-1 bg-[#222222]">
                <div
                    className="h-full bg-[#BAD306] transition-all duration-150"
                    style={{ width: `${scrollPercent}%` }}
                    role="progressbar"
                    aria-valuenow={scrollPercent}
                    aria-valuemin={0}
                    aria-valuemax={100}
                    aria-label="Progres bacaan"
                />
            </div>

            {/* Floating Minimal Header with Auto-hide / Tap-to-toggle */}
            <header
                onClick={(e) => e.stopPropagation()}
                className={`fixed top-0 left-0 right-0 z-40 bg-[#111111]/95 backdrop-blur-md border-b-2 border-[#222222] w-full max-w-full transition-transform duration-300 ease-in-out ${
                    showControls ? 'translate-y-0' : '-translate-y-full pointer-events-none'
                }`}
            >
                <div className="w-full max-w-[1440px] mx-auto px-3 sm:px-6 h-14 flex items-center justify-between gap-3">
                    {/* Left: Back Link & Title */}
                    <div className="flex items-center gap-3 sm:gap-4 min-w-0">
                        <Link
                            href={`/komik/${comic.slug}`}
                            className="inline-flex items-center gap-1.5 px-2.5 py-1.5 font-display text-xs tracking-wider uppercase text-[#AAAAAA] hover:text-[#F8F8F8] bg-[#161616] border border-[#333333] hover:border-[#BAD306] transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#BAD306] shrink-0 rounded-none"
                            aria-label={`Kembali ke detail ${comic.title}`}
                        >
                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            <span className="hidden sm:inline">Detail</span>
                        </Link>

                        <div className="min-w-0">
                            <h1 className="font-display text-base sm:text-lg uppercase tracking-wider text-[#F8F8F8] truncate leading-tight">
                                {comic.title}
                            </h1>
                            <p className="text-[11px] sm:text-xs text-[#AAAAAA] truncate leading-none mt-0.5">
                                {chapter.title || `Chapter ${chapter.chapter_number}`}
                            </p>
                        </div>
                    </div>

                    {/* Right: Chapter Navigation & Page Indicator */}
                    <div className="flex items-center gap-2 sm:gap-3 shrink-0">
                        {/* Page indicator badge */}
                        <div className="hidden md:inline-flex items-center px-2.5 py-1 bg-[#161616] border border-[#333333] text-xs font-mono text-[#AAAAAA] rounded-none">
                            <span className="text-[#F8F8F8] font-bold mr-1">{activePage}</span> / {totalPages} Halaman
                        </div>

                        {/* Navigation buttons */}
                        <div className="flex items-center gap-1.5">
                            {chapter.prev_chapter_key ? (
                                <Link
                                    href={`/komik/${comic.slug}/${chapter.prev_chapter_key}`}
                                    className="px-2.5 sm:px-3 py-1.5 font-display text-xs tracking-wider uppercase text-[#F8F8F8] bg-[#161616] border border-[#444444] hover:border-[#BAD306] hover:text-[#BAD306] transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#BAD306] rounded-none"
                                    title="Chapter Sebelumnya (Arrow Left)"
                                >
                                    <span className="sm:inline hidden mr-1">Sebelumnya</span>
                                    <span className="sm:hidden inline">Prev</span>
                                </Link>
                            ) : (
                                <button
                                    type="button"
                                    disabled
                                    className="px-2.5 sm:px-3 py-1.5 font-display text-xs tracking-wider uppercase text-[#555555] bg-[#161616] border border-[#222222] cursor-not-allowed rounded-none"
                                >
                                    <span className="sm:inline hidden mr-1">Sebelumnya</span>
                                    <span className="sm:hidden inline">Prev</span>
                                </button>
                            )}

                            {chapter.next_chapter_key ? (
                                <Link
                                    href={`/komik/${comic.slug}/${chapter.next_chapter_key}`}
                                    className="px-2.5 sm:px-3 py-1.5 font-display text-xs tracking-wider uppercase text-[#111111] bg-[#BAD306] hover:bg-[#E0FF00] border-2 border-[#BAD306] transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#BAD306] rounded-none font-bold"
                                    title="Chapter Berikutnya (Arrow Right)"
                                >
                                    <span className="sm:inline hidden mr-1">Berikutnya</span>
                                    <span className="sm:hidden inline">Next</span>
                                </Link>
                            ) : (
                                <button
                                    type="button"
                                    disabled
                                    className="px-2.5 sm:px-3 py-1.5 font-display text-xs tracking-wider uppercase text-[#555555] bg-[#161616] border border-[#222222] cursor-not-allowed rounded-none"
                                >
                                    <span className="sm:inline hidden mr-1">Berikutnya</span>
                                    <span className="sm:hidden inline">Next</span>
                                </button>
                            )}
                        </div>
                    </div>
                </div>
            </header>

            {/* Main Long-Strip Reader Area */}
            <main id="reader-strip" className="w-full max-w-[768px] mx-auto min-w-0 px-0 sm:px-2 pt-14 pb-20 sm:pb-12">
                {images.length === 0 ? (
                    <div className="my-16 mx-4 p-8 text-center bg-[#161616] border-2 border-[#333333] rounded-none">
                        <p className="font-display text-xl uppercase tracking-wider text-[#F8F8F8] mb-2">
                            Gambar Chapter Belum Tersedia
                        </p>
                        <p className="text-sm text-[#AAAAAA] mb-6">
                            Halaman chapter ini sedang dalam proses pembaruan oleh penyedia komik.
                        </p>
                        <Link
                            href={`/komik/${comic.slug}`}
                            className="inline-block px-5 py-2 font-display text-xs tracking-wider uppercase bg-[#BAD306] text-[#111111] border-2 border-[#BAD306] font-bold rounded-none"
                        >
                            Kembali ke Detail Komik
                        </Link>
                    </div>
                ) : (
                    <div
                        onClick={toggleControls}
                        className="w-full block leading-none text-[0px] cursor-pointer"
                        title="Tap layar untuk menampilkan atau menyembunyikan navigasi"
                    >
                        {images.map((imgUrl, index) => {
                            const isFailed = failedImages[index];
                            const pageNumber = index + 1;

                            return (
                                <div
                                    key={`${index}-${retryCounters[index] || 0}`}
                                    ref={(el) => {
                                        imageRefs.current[index] = el;
                                    }}
                                    data-page-index={pageNumber}
                                    className={`w-full relative block leading-none m-0 p-0 text-[0px] ${
                                        index > 0 ? '-mt-[1px]' : ''
                                    }`}
                                >
                                    {isFailed ? (
                                        <div
                                            onClick={(e) => e.stopPropagation()}
                                            className="w-full py-16 px-4 bg-[#161616] border-2 border-[#444444] text-center my-2 rounded-none text-base"
                                        >
                                            <div className="inline-block p-2 bg-[#222222] text-[#E56458] mb-3">
                                                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                </svg>
                                            </div>
                                            <p className="text-sm font-medium text-[#F8F8F8] mb-1">
                                                Gagal memuat Halaman {pageNumber}
                                            </p>
                                            <p className="text-xs text-[#AAAAAA] mb-4">
                                                Koneksi ke server gambar terputus atau timeout.
                                            </p>
                                            <button
                                                type="button"
                                                onClick={(e) => {
                                                    e.stopPropagation();
                                                    handleRetryImage(index);
                                                }}
                                                className="px-4 py-2 font-display text-xs tracking-wider uppercase bg-[#111111] hover:bg-[#BAD306] text-[#F8F8F8] hover:text-[#111111] border border-[#444444] hover:border-[#BAD306] transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#BAD306] rounded-none font-bold"
                                            >
                                                Coba Lagi
                                            </button>
                                        </div>
                                    ) : (
                                        <img
                                            src={getImageSrc(imgUrl, index)}
                                            alt={`Halaman ${pageNumber}`}
                                            loading={index < 3 ? 'eager' : 'lazy'}
                                            decoding="async"
                                            // @ts-expect-error fetchpriority attribute supported in modern browsers
                                            fetchpriority={index === 0 ? 'high' : 'auto'}
                                            onError={() => handleImageError(index)}
                                            className="w-full h-auto block select-none m-0 p-0 border-0 outline-none align-top [transform:translateZ(0)]"
                                        />
                                    )}
                                </div>
                            );
                        })}
                    </div>
                )}

                {/* End of Chapter Section */}
                <section
                    onClick={(e) => e.stopPropagation()}
                    aria-label="Selesai Membaca Chapter"
                    className="mt-8 mb-16 mx-3 sm:mx-0 p-6 bg-[#161616] border-2 border-[#222222] text-center rounded-none"
                >
                    <p className="font-display text-xs tracking-widest uppercase text-[#BAD306] mb-1">
                        Selesai Membaca
                    </p>
                    <h2 className="font-display text-2xl sm:text-3xl uppercase tracking-wider text-[#F8F8F8] mb-2">
                        {comic.title} Chapter {chapter.chapter_number}
                    </h2>
                    <p className="text-xs sm:text-sm text-[#AAAAAA] mb-6">
                        Gunakan navigasi di bawah untuk melanjutkan chapter berikutnya atau kembali ke halaman komik.
                    </p>

                    <div className="flex flex-col sm:flex-row items-center justify-center gap-3">
                        {chapter.next_chapter_key ? (
                            <Link
                                href={`/komik/${comic.slug}/${chapter.next_chapter_key}`}
                                className="w-full sm:w-auto px-8 py-3 font-display text-sm tracking-wider uppercase bg-[#BAD306] hover:bg-[#E0FF00] text-[#111111] border-2 border-[#BAD306] font-bold transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#BAD306] rounded-none"
                            >
                                Lanjut ke Chapter Berikutnya
                            </Link>
                        ) : (
                            <span className="w-full sm:w-auto px-6 py-3 font-display text-xs tracking-wider uppercase bg-[#222222] text-[#777777] border border-[#333333] rounded-none">
                                Ini adalah Chapter Terakhir
                            </span>
                        )}

                        <Link
                            href={`/komik/${comic.slug}`}
                            className="w-full sm:w-auto px-6 py-3 font-display text-sm tracking-wider uppercase bg-[#111111] hover:bg-[#161616] text-[#F8F8F8] border border-[#444444] hover:border-[#BAD306] hover:text-[#BAD306] transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#BAD306] rounded-none"
                        >
                            Kembali ke Detail Komik
                        </Link>
                    </div>
                </section>

                {/* Community Comments & Discussion */}
                <div onClick={(e) => e.stopPropagation()}>
                    <CommentSection
                        comicSlug={comic.slug}
                        chapterKey={chapter.chapter_key}
                        currentUser={auth.user}
                    />
                </div>
            </main>

            {/* Mobile Bottom Ergonomic Bar with Auto-hide / Tap-to-toggle */}
            <div
                onClick={(e) => e.stopPropagation()}
                className={`sm:hidden fixed bottom-0 left-0 right-0 z-40 bg-[#111111] border-t-2 border-[#222222] px-3 pt-2 flex items-center justify-between gap-2 transition-transform duration-300 ease-in-out after:content-[''] after:absolute after:top-full after:left-0 after:right-0 after:h-24 after:bg-[#111111] after:pointer-events-none ${
                    showControls ? 'translate-y-0' : 'translate-y-full pointer-events-none'
                }`}
                style={{
                    bottom: 0,
                    paddingBottom: 'max(8px, env(safe-area-inset-bottom, 0px))',
                }}
            >
                <Link
                    href={`/komik/${comic.slug}`}
                    className="p-2 text-[#AAAAAA] hover:text-[#F8F8F8] border border-[#333333] bg-[#161616] rounded-none shrink-0"
                    aria-label="Kembali ke detail"
                >
                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </Link>

                <div className="flex items-center gap-1.5 text-center flex-1 justify-center min-w-0">
                    <span className="font-display text-xs tracking-wider uppercase text-[#F8F8F8] truncate">
                        Ch. {chapter.chapter_number}
                    </span>
                    <span className="text-[#444444]">•</span>
                    <span className="font-mono text-[11px] text-[#AAAAAA]">
                        {activePage}/{totalPages}
                    </span>
                </div>

                <div className="flex items-center gap-1.5 shrink-0">
                    {chapter.prev_chapter_key ? (
                        <Link
                            href={`/komik/${comic.slug}/${chapter.prev_chapter_key}`}
                            className="px-3 py-1.5 font-display text-xs tracking-wider uppercase text-[#F8F8F8] bg-[#161616] border border-[#444444] hover:border-[#BAD306] hover:text-[#BAD306] transition-colors rounded-none"
                        >
                            Prev
                        </Link>
                    ) : (
                        <button
                            type="button"
                            disabled
                            className="px-3 py-1.5 font-display text-xs tracking-wider uppercase text-[#444444] bg-[#161616] border border-[#222222] cursor-not-allowed rounded-none"
                        >
                            Prev
                        </button>
                    )}

                    {chapter.next_chapter_key ? (
                        <Link
                            href={`/komik/${comic.slug}/${chapter.next_chapter_key}`}
                            className="px-3 py-1.5 font-display text-xs tracking-wider uppercase text-[#111111] bg-[#BAD306] hover:bg-[#E0FF00] border-2 border-[#BAD306] font-bold transition-colors rounded-none"
                        >
                            Next
                        </Link>
                    ) : (
                        <button
                            type="button"
                            disabled
                            className="px-3 py-1.5 font-display text-xs tracking-wider uppercase text-[#444444] bg-[#161616] border border-[#222222] cursor-not-allowed rounded-none"
                        >
                            Next
                        </button>
                    )}
                </div>
            </div>
        </div>
    );
}
