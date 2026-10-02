import React from 'react';
import { Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { BookmarkItem, ReadingHistoryItem } from '@/types';
import { Pagination } from '@/Components/Pagination';
import { normalizeImageUrl } from '@/utils/imageUrl';

interface PaginatedData<T> {
    data: T[];
    current_page: number;
    last_page?: number;
    prev_page_url: string | null;
    next_page_url: string | null;
    total: number;
}

interface LibraryProps {
    tab: 'riwayat' | 'bookmark';
    histories: PaginatedData<ReadingHistoryItem>;
    bookmarks: PaginatedData<BookmarkItem>;
    counts: {
        histories: number;
        bookmarks: number;
    };
}

export default function Index({ tab = 'riwayat', histories, bookmarks, counts }: LibraryProps) {
    const activeTab = tab;

    const handleDeleteHistory = (id: number, comicTitle: string) => {
        if (confirm(`Hapus "${comicTitle}" dari riwayat baca?`)) {
            router.delete(`/pustaka/riwayat/${id}`, {
                preserveScroll: true,
            });
        }
    };

    const handleClearAllHistory = () => {
        if (confirm('Yakin ingin menghapus seluruh riwayat baca? Tindakan ini tidak dapat dibatalkan.')) {
            router.delete('/pustaka/riwayat', {
                preserveScroll: true,
            });
        }
    };

    const handleDeleteBookmark = (id: number, comicTitle: string) => {
        if (confirm(`Hapus "${comicTitle}" dari bookmark?`)) {
            router.delete(`/pustaka/bookmark/${id}`, {
                preserveScroll: true,
            });
        }
    };

    const formatRelativeTime = (isoString: string): string => {
        try {
            const date = new Date(isoString);
            const now = new Date();
            const diffInSeconds = Math.floor((now.getTime() - date.getTime()) / 1000);

            if (diffInSeconds < 60) return 'Baru saja';
            if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)} menit lalu`;
            if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)} jam lalu`;
            if (diffInSeconds < 604800) return `${Math.floor(diffInSeconds / 86400)} hari lalu`;
            return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
        } catch {
            return isoString;
        }
    };

    return (
        <AppLayout
            title="Pustaka Saya - Riwayat & Bookmark"
            description="Kelola riwayat baca komik dan daftar bookmark tersimpan Anda di Komikini."
            noIndex={true}
        >
            <div className="max-w-[1440px] mx-auto px-4 sm:px-6 py-6 sm:py-8">
                {/* Header Section */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 pb-4 border-b-2 border-[#222222]">
                    <div>
                        <h1 className="font-display text-3xl sm:text-4xl uppercase tracking-wider text-[#F8F8F8]">
                            Pustaka Saya
                        </h1>
                        <p className="text-sm text-[#AAAAAA] mt-1">
                            Lanjutkan membaca chapter terakhir dan kelola komik favorit Anda.
                        </p>
                    </div>

                    {/* Tab Navigation */}
                    <div className="flex items-center gap-1 bg-[#111111] p-1 border border-[#333333]">
                        <Link
                            href="/pustaka?tab=riwayat"
                            className={`px-4 py-2 font-display text-sm tracking-wider uppercase transition-colors rounded-none focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#BAD306] ${
                                activeTab === 'riwayat'
                                    ? 'bg-[#BAD306] text-[#111111] font-bold'
                                    : 'text-[#AAAAAA] hover:text-[#F8F8F8] hover:bg-[#1A1A1A]'
                            }`}
                        >
                            Riwayat ({counts.histories})
                        </Link>
                        <Link
                            href="/pustaka?tab=bookmark"
                            className={`px-4 py-2 font-display text-sm tracking-wider uppercase transition-colors rounded-none focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#BAD306] ${
                                activeTab === 'bookmark'
                                    ? 'bg-[#BAD306] text-[#111111] font-bold'
                                    : 'text-[#AAAAAA] hover:text-[#F8F8F8] hover:bg-[#1A1A1A]'
                            }`}
                        >
                            Bookmark ({counts.bookmarks})
                        </Link>
                    </div>
                </div>

                {/* TAB 1: RIWAYAT BACA */}
                {activeTab === 'riwayat' && (
                    <div>
                        {histories.data.length > 0 && (
                            <div className="flex items-center justify-between mb-4">
                                <span className="font-display text-xs tracking-wider uppercase text-[#AAAAAA]">
                                    Menampilkan {histories.data.length} dari {histories.total} komik dibaca
                                </span>
                                <button
                                    type="button"
                                    onClick={handleClearAllHistory}
                                    className="px-3 py-1 font-display text-xs tracking-wider uppercase text-[#E56458] hover:text-white hover:bg-[#E56458] border border-[#E56458] transition-colors rounded-none"
                                >
                                    Hapus Semua Riwayat
                                </button>
                            </div>
                        )}

                        {histories.data.length === 0 ? (
                            /* Privacy-safe Empty State */
                            <div className="py-16 px-4 text-center bg-[#161616] border-2 border-[#333333] my-4">
                                <div className="inline-flex p-4 border-2 border-[#444444] bg-[#111111] text-[#BAD306] mb-4">
                                    <svg className="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <h3 className="font-display text-2xl uppercase tracking-wider text-[#F8F8F8] mb-2">
                                    Belum Ada Riwayat Baca
                                </h3>
                                <p className="text-sm text-[#AAAAAA] max-w-md mx-auto mb-6">
                                    Komik yang Anda baca saat login akan otomatis tersimpan di sini sehingga Anda bisa melanjutkan membaca kapan saja.
                                </p>
                                <Link
                                    href="/"
                                    className="inline-flex items-center gap-2 px-6 py-3 font-display text-sm tracking-wider uppercase bg-[#BAD306] hover:bg-[#E0FF00] text-[#111111] font-bold border-2 border-[#BAD306] transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#BAD306] rounded-none"
                                >
                                    Jelajahi Komik Sekarang
                                </Link>
                            </div>
                        ) : (
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                {histories.data.map((item) => {
                                    const percent = Math.round(item.progress_percent);
                                    const isDone = Boolean(item.completed_at) || percent >= 95;

                                    return (
                                        <div
                                            key={item.id}
                                            className="flex flex-col bg-[#161616] border-2 border-[#333333] hover:border-[#BAD306] transition-colors p-4 relative group"
                                        >
                                            <div className="flex gap-4">
                                                {/* Thumbnail */}
                                                <Link
                                                    href={`/komik/${item.comic_slug}`}
                                                    className="w-20 aspect-[3/4] bg-[#111111] border border-[#444444] shrink-0 overflow-hidden"
                                                >
                                                    {item.comic_thumbnail ? (
                                                        <img
                                                            src={normalizeImageUrl(item.comic_thumbnail)}
                                                            alt={item.comic_title}
                                                            className="w-full h-full object-cover"
                                                            onError={(e) => {
                                                                (e.target as HTMLImageElement).style.display = 'none';
                                                            }}
                                                        />
                                                    ) : (
                                                        <div className="w-full h-full flex items-center justify-center font-display text-[10px] text-[#777777] uppercase text-center p-1">
                                                            No Cover
                                                        </div>
                                                    )}
                                                </Link>

                                                {/* Info */}
                                                <div className="flex-1 min-w-0 flex flex-col justify-between">
                                                    <div>
                                                        <Link
                                                            href={`/komik/${item.comic_slug}`}
                                                            className="font-display text-lg tracking-wide uppercase text-[#F8F8F8] hover:text-[#BAD306] transition-colors line-clamp-1"
                                                        >
                                                            {item.comic_title}
                                                        </Link>

                                                        <div className="mt-1 flex items-center gap-2">
                                                            <span className="font-display text-xs tracking-wider uppercase px-1.5 py-0.5 bg-[#222222] text-[#BAD306] border border-[#333333]">
                                                                Ch. {item.chapter_number}
                                                            </span>
                                                            <span className="text-[11px] text-[#777777]">
                                                                {formatRelativeTime(item.read_at)}
                                                            </span>
                                                        </div>
                                                    </div>

                                                    {/* Progress bar */}
                                                    <div className="mt-3">
                                                        <div className="flex items-center justify-between text-[11px] text-[#AAAAAA] mb-1 font-mono">
                                                            <span>{isDone ? 'Selesai Dibaca' : `Halaman ${item.last_image_index !== null ? item.last_image_index + 1 : 1}`}</span>
                                                            <span className={isDone ? 'text-[#72BC8F] font-bold' : 'text-[#BAD306]'}>
                                                                {percent}%
                                                            </span>
                                                        </div>
                                                        <div className="w-full h-1.5 bg-[#111111] border border-[#333333]">
                                                            <div
                                                                className={`h-full ${isDone ? 'bg-[#72BC8F]' : 'bg-[#BAD306]'}`}
                                                                style={{ width: `${Math.max(5, percent)}%` }}
                                                            />
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            {/* Action bar */}
                                            <div className="mt-4 pt-3 border-t border-[#262626] flex items-center justify-between gap-2">
                                                <Link
                                                    href={`/komik/${item.comic_slug}/${item.chapter_key}`}
                                                    className="inline-flex items-center gap-1.5 px-3 py-1.5 font-display text-xs tracking-wider uppercase bg-[#BAD306] hover:bg-[#E0FF00] text-[#111111] font-bold border border-[#BAD306] transition-colors rounded-none"
                                                >
                                                    <span>Lanjutkan Baca</span>
                                                    <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                                    </svg>
                                                </Link>

                                                <button
                                                    type="button"
                                                    onClick={() => handleDeleteHistory(item.id, item.comic_title)}
                                                    className="px-2.5 py-1.5 text-xs text-[#777777] hover:text-[#E56458] transition-colors"
                                                    title="Hapus dari riwayat"
                                                    aria-label={`Hapus ${item.comic_title} dari riwayat`}
                                                >
                                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        )}

                        <Pagination
                            currentPage={histories.current_page}
                            hasNextPage={Boolean(histories.next_page_url)}
                            hasPrevPage={Boolean(histories.prev_page_url)}
                            totalPages={histories.last_page}
                            queryParams={{ tab: 'riwayat' }}
                        />
                    </div>
                )}

                {/* TAB 2: BOOKMARK */}
                {activeTab === 'bookmark' && (
                    <div>
                        {bookmarks.data.length === 0 ? (
                            /* Privacy-safe Empty State */
                            <div className="py-16 px-4 text-center bg-[#161616] border-2 border-[#333333] my-4">
                                <div className="inline-flex p-4 border-2 border-[#444444] bg-[#111111] text-[#BAD306] mb-4">
                                    <svg className="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                                    </svg>
                                </div>
                                <h3 className="font-display text-2xl uppercase tracking-wider text-[#F8F8F8] mb-2">
                                    Belum Ada Komik Tersimpan
                                </h3>
                                <p className="text-sm text-[#AAAAAA] max-w-md mx-auto mb-6">
                                    Simpan komik favorit Anda untuk memantau update chapter dan membacanya kapan saja dengan mudah.
                                </p>
                                <Link
                                    href="/"
                                    className="inline-flex items-center gap-2 px-6 py-3 font-display text-sm tracking-wider uppercase bg-[#BAD306] hover:bg-[#E0FF00] text-[#111111] font-bold border-2 border-[#BAD306] transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#BAD306] rounded-none"
                                >
                                    Jelajahi Katalog Komik
                                </Link>
                            </div>
                        ) : (
                            <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 sm:gap-4">
                                {bookmarks.data.map((item) => (
                                    <div
                                        key={item.id}
                                        className="bg-[#161616] border-2 border-[#333333] hover:border-[#BAD306] transition-colors flex flex-col justify-between group"
                                    >
                                        <Link href={`/komik/${item.comic_slug}`} className="block relative aspect-[3/4] bg-[#111111] overflow-hidden">
                                            {item.comic_thumbnail ? (
                                                <img
                                                    src={normalizeImageUrl(item.comic_thumbnail)}
                                                    alt={item.comic_title}
                                                    className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200"
                                                    onError={(e) => {
                                                        (e.target as HTMLImageElement).style.display = 'none';
                                                    }}
                                                />
                                            ) : (
                                                <div className="w-full h-full flex items-center justify-center font-display text-xs text-[#777777] uppercase text-center p-2">
                                                    No Cover
                                                </div>
                                            )}
                                            <span className="absolute top-1.5 left-1.5 px-1.5 py-0.5 font-display text-[10px] tracking-wider uppercase bg-[#111111] text-[#F8F8F8] border border-[#444444]">
                                                {item.comic_type}
                                            </span>
                                        </Link>

                                        <div className="p-3 flex flex-col justify-between flex-1">
                                            <Link
                                                href={`/komik/${item.comic_slug}`}
                                                className="font-display text-sm tracking-wide uppercase text-[#F8F8F8] group-hover:text-[#BAD306] transition-colors line-clamp-2 leading-tight"
                                            >
                                                {item.comic_title}
                                            </Link>

                                            <div className="mt-3 pt-2 border-t border-[#262626] flex items-center justify-between">
                                                <Link
                                                    href={`/komik/${item.comic_slug}`}
                                                    className="font-display text-xs tracking-wider uppercase text-[#BAD306] hover:underline"
                                                >
                                                    Detail
                                                </Link>
                                                <button
                                                    type="button"
                                                    onClick={() => handleDeleteBookmark(item.id, item.comic_title)}
                                                    className="text-xs text-[#777777] hover:text-[#E56458] transition-colors p-1"
                                                    title="Hapus bookmark"
                                                    aria-label={`Hapus ${item.comic_title} dari bookmark`}
                                                >
                                                    <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}

                        <Pagination
                            currentPage={bookmarks.current_page}
                            hasNextPage={Boolean(bookmarks.next_page_url)}
                            hasPrevPage={Boolean(bookmarks.prev_page_url)}
                            totalPages={bookmarks.last_page}
                            queryParams={{ tab: 'bookmark' }}
                        />
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
