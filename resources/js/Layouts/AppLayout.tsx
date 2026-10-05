import React, { ReactNode, useState, useEffect } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { PageProps } from '@/types';
import { SearchBox } from '@/Components/SearchBox';
import { SeoHead, SeoHeadProps } from '@/Components/SeoHead';
import { BrandLogo } from '@/Components/BrandLogo';

interface AppLayoutProps extends SeoHeadProps {
    children: ReactNode;
}

export default function AppLayout({
    title,
    description,
    canonical,
    ogImage,
    ogType,
    noIndex,
    jsonLd,
    children,
}: AppLayoutProps) {
    const { appName, auth, correlationId } = usePage<PageProps>().props;
    const { url } = usePage();
    const user = auth.user;
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

    const navLinks = [
        { href: '/', label: 'Beranda' },
        { href: '/terbaru', label: 'Terbaru' },
        { href: '/ranking', label: 'Peringkat' },
        { href: '/type/manga', label: 'Manga' },
        { href: '/type/manhwa', label: 'Manhwa' },
        { href: '/type/manhua', label: 'Manhua' },
        { href: '/genre', label: 'Genre' },
        { href: '/pustaka', label: 'Pustaka' },
    ];

    // Lock body scroll and register Escape listener when drawer is open
    useEffect(() => {
        const handleKeyDown = (e: KeyboardEvent) => {
            if (e.key === 'Escape' && mobileMenuOpen) {
                setMobileMenuOpen(false);
            }
        };

        if (mobileMenuOpen) {
            document.body.classList.add('overflow-hidden');
            window.addEventListener('keydown', handleKeyDown);
        } else {
            document.body.classList.remove('overflow-hidden');
        }

        return () => {
            document.body.classList.remove('overflow-hidden');
            window.removeEventListener('keydown', handleKeyDown);
        };
    }, [mobileMenuOpen]);

    return (
        <div className="min-h-screen flex flex-col bg-[#121212] text-[#f3f3ef] font-sans antialiased overflow-x-hidden w-full max-w-full selection:bg-[#bdd600] selection:text-[#101010]">
            <SeoHead
                title={title}
                description={description}
                canonical={canonical}
                ogImage={ogImage}
                ogType={ogType}
                noIndex={noIndex}
                jsonLd={jsonLd}
            />

            {/* Accessible Skip to Content Link (WCAG 2.2 AA) */}
            <a
                href="#main-content"
                className="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:px-4 focus:py-2.5 focus:bg-[#bdd600] focus:text-[#101010] focus:font-display focus:text-sm focus:font-bold focus:outline-none focus:ring-2 focus:ring-[#101010] rounded-none shadow-lg"
            >
                Lewati ke konten utama
            </a>

            {/* Sticky Header */}
            <header className="sticky top-0 z-40 bg-[#0d0d0d] border-b border-[#303030] w-full max-w-full">
                <div className="w-[min(1280px,calc(100%-40px))] max-sm:w-[calc(100%-24px)] mx-auto">
                    <div className="h-[68px] max-sm:h-auto max-sm:py-3 grid grid-cols-[auto_minmax(240px,610px)_1fr_auto] max-lg:grid-cols-[auto_1fr_auto] max-sm:grid-cols-[1fr_auto] gap-5 max-sm:gap-2.5 items-center">
                        {/* Brand / Logo */}
                        <Link
                            href="/"
                            className="inline-flex items-center focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#bdd600] py-1"
                            aria-label="Komikini Beranda"
                        >
                            <BrandLogo variant="full" className="h-[28px] max-sm:h-[22px] w-auto" />
                        </Link>

                        {/* Search Desktop / Tablet */}
                        <div className="max-sm:hidden w-full">
                            <SearchBox id="search-desktop" placeholder="Cari judul, manga, manhwa, atau manhua" />
                        </div>

                        {/* Desktop Account Section */}
                        <nav className="account hidden lg:flex justify-end items-center gap-4 text-xs font-bold uppercase tracking-wider" aria-label="Akses Akun">
                            {user ? (
                                <div className="flex items-center gap-3">
                                    {user.can_access_admin && (
                                        <Link
                                            href="/admin"
                                            className="bg-[#bdd600] text-[#101010] px-3 py-2 font-bold hover:bg-[#d8ef21] transition-colors"
                                        >
                                            Admin
                                        </Link>
                                    )}
                                    <Link
                                        href="/pustaka"
                                        className="text-[#f3f3ef] hover:text-[#bdd600] transition-colors"
                                    >
                                        Pustaka
                                    </Link>
                                    <Link
                                        href="/profile"
                                        className="text-[#f3f3ef] hover:text-[#bdd600] transition-colors"
                                    >
                                        <span className="text-[#aaa9a3]">Akun: </span>
                                        <span>{user.name}</span>
                                    </Link>
                                    <Link
                                        href="/logout"
                                        method="post"
                                        as="button"
                                        className="text-[#aaa9a3] hover:text-[#e56458] transition-colors cursor-pointer"
                                    >
                                        Keluar
                                    </Link>
                                </div>
                            ) : (
                                <div className="flex items-center gap-4">
                                    <Link
                                        href="/login"
                                        className="text-[#f3f3ef] hover:text-[#bdd600] transition-colors py-2"
                                    >
                                        Masuk
                                    </Link>
                                    <Link
                                        href="/register"
                                        className="bg-[#bdd600] text-[#101010] px-4 py-2 font-bold hover:bg-[#d8ef21] transition-colors"
                                    >
                                        Daftar
                                    </Link>
                                </div>
                            )}
                        </nav>

                        {/* Hamburger Button */}
                        <button
                            type="button"
                            id="menuOpen"
                            onClick={() => setMobileMenuOpen(true)}
                            className="w-[42px] h-[40px] border border-[#484848] bg-[#151515] flex flex-col items-center justify-center gap-1 cursor-pointer hover:border-[#bdd600] focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#bdd600] max-sm:col-start-2 max-sm:row-start-1"
                            aria-label="Buka menu navigasi"
                            aria-expanded={mobileMenuOpen}
                            aria-controls="menuPanel"
                        >
                            <span className="block w-[18px] h-[2px] bg-[#f3f3ef]"></span>
                            <span className="block w-[18px] h-[2px] bg-[#f3f3ef]"></span>
                            <span className="block w-[18px] h-[2px] bg-[#f3f3ef]"></span>
                        </button>

                        {/* Mobile Search Row */}
                        <div className="sm:hidden col-span-2 row-start-2 w-full mt-1">
                            <SearchBox id="search-mobile" placeholder="Cari judul, manga, manhwa, atau manhua" />
                        </div>
                    </div>
                </div>
            </header>

            {/* Slide-over Hamburger Drawer Modal */}
            <div
                id="menuPanel"
                className={`fixed inset-0 z-50 transition-opacity ${mobileMenuOpen ? 'opacity-100 pointer-events-auto' : 'opacity-0 pointer-events-none'}`}
                aria-hidden={!mobileMenuOpen}
            >
                {/* Backdrop shade */}
                <div
                    id="menuShade"
                    onClick={() => setMobileMenuOpen(false)}
                    className="absolute inset-0 bg-black/75 transition-opacity"
                    aria-label="Tutup menu modal"
                />

                {/* Drawer Panel */}
                <aside
                    id="menuDrawer"
                    role="dialog"
                    aria-modal="true"
                    aria-label="Menu utama"
                    className={`absolute top-0 right-0 w-[min(390px,100%)] h-full bg-[#0d0d0d] border-l border-[#484848] p-6 flex flex-col justify-between overflow-y-auto transform transition-transform duration-200 ease-in-out ${
                        mobileMenuOpen ? 'translate-x-0' : 'translate-x-full'
                    }`}
                >
                    <div>
                        {/* Top bar with close button */}
                        <div className="flex items-center justify-between pb-5 border-b border-[#303030]">
                            <strong className="font-display text-xl font-bold text-[#f3f3ef] uppercase tracking-wider">
                                Menu
                            </strong>
                            <button
                                type="button"
                                id="menuClose"
                                onClick={() => setMobileMenuOpen(false)}
                                className="w-10 h-10 flex items-center justify-center border border-[#484848] text-[#f3f3ef] hover:border-[#bdd600] hover:text-[#bdd600] text-2xl transition-colors cursor-pointer"
                                aria-label="Tutup menu"
                            >
                                &times;
                            </button>
                        </div>

                        {/* Navigation Links */}
                        <nav className="flex flex-col mt-4" aria-label="Navigasi Menu">
                            {navLinks.map((link) => (
                                <Link
                                    key={link.href}
                                    href={link.href}
                                    onClick={() => setMobileMenuOpen(false)}
                                    className="py-3.5 border-b border-[#303030] font-sans text-base font-semibold text-[#f3f3ef] hover:text-[#bdd600] transition-colors"
                                >
                                    {link.label}
                                </Link>
                            ))}
                        </nav>
                    </div>

                    {/* Footer Actions in Drawer */}
                    <div className="pt-6 border-t border-[#303030] mt-6">
                        {user ? (
                            <div className="flex flex-col gap-3">
                                {user.can_access_admin && (
                                    <Link
                                        href="/admin"
                                        onClick={() => setMobileMenuOpen(false)}
                                        className="w-full text-center py-2.5 font-display text-xs tracking-wider uppercase text-[#101010] bg-[#bdd600] font-bold"
                                    >
                                        Konsol Admin
                                    </Link>
                                )}
                                <Link
                                    href="/profile"
                                    onClick={() => setMobileMenuOpen(false)}
                                    className="w-full text-center py-2.5 font-display text-xs tracking-wider uppercase text-[#f3f3ef] bg-[#191919] border border-[#484848]"
                                >
                                    Akun: {user.name}
                                </Link>
                                <Link
                                    href="/logout"
                                    method="post"
                                    as="button"
                                    onClick={() => setMobileMenuOpen(false)}
                                    className="w-full text-center py-2.5 font-display text-xs tracking-wider uppercase text-[#e56458] border border-[#e56458] hover:bg-[#e56458] hover:text-white transition-colors"
                                >
                                    Keluar
                                </Link>
                            </div>
                        ) : (
                            <div className="flex items-center gap-3">
                                <Link
                                    href="/login"
                                    onClick={() => setMobileMenuOpen(false)}
                                    className="flex-1 text-center py-2.5 font-display text-xs tracking-wider uppercase text-[#f3f3ef] bg-[#191919] border border-[#484848] hover:border-[#bdd600]"
                                >
                                    Masuk
                                </Link>
                                <Link
                                    href="/register"
                                    onClick={() => setMobileMenuOpen(false)}
                                    className="flex-1 text-center py-2.5 font-display text-xs tracking-wider uppercase text-[#101010] bg-[#bdd600] font-bold hover:bg-[#d8ef21]"
                                >
                                    Daftar
                                </Link>
                            </div>
                        )}
                    </div>
                </aside>
            </div>

            {/* Main Content Area */}
            <main id="main-content" className="flex-1 w-[min(1280px,calc(100%-40px))] max-sm:w-[calc(100%-24px)] mx-auto pt-[31px] max-sm:pt-[22px] pb-[110px] max-sm:pb-[92px]">
                {children}
            </main>

            {/* Mobile Bottom Navigation */}
            <nav
                className="sm:hidden fixed bottom-0 left-0 right-0 z-30 h-[66px] bg-[#0d0d0d] border-t border-[#484848] grid grid-cols-4 items-center"
                aria-label="Navigasi cepat mobile"
            >
                <Link
                    href="/"
                    className={`flex flex-col items-center justify-center gap-1 font-sans text-[11px] font-bold uppercase ${
                        url === '/' ? 'text-[#bdd600]' : 'text-[#aaa9a3] hover:text-[#f3f3ef]'
                    }`}
                >
                    <span>Home</span>
                </Link>
                <Link
                    href="/pustaka?tab=bookmark"
                    className={`flex flex-col items-center justify-center gap-1 font-sans text-[11px] font-bold uppercase ${
                        url.includes('bookmark') ? 'text-[#bdd600]' : 'text-[#aaa9a3] hover:text-[#f3f3ef]'
                    }`}
                >
                    <span>Bookmark</span>
                </Link>
                <Link
                    href="/pustaka"
                    className={`flex flex-col items-center justify-center gap-1 font-sans text-[11px] font-bold uppercase ${
                        url === '/pustaka' ? 'text-[#bdd600]' : 'text-[#aaa9a3] hover:text-[#f3f3ef]'
                    }`}
                >
                    <span>History</span>
                </Link>
                <Link
                    href={user ? '/profile' : '/login'}
                    className={`flex flex-col items-center justify-center gap-1 font-sans text-[11px] font-bold uppercase ${
                        url.includes('/profile') || url.includes('/login') ? 'text-[#bdd600]' : 'text-[#aaa9a3] hover:text-[#f3f3ef]'
                    }`}
                >
                    <span>Profil</span>
                </Link>
            </nav>

            {/* Footer */}
            <footer className="border-t border-[#303030] bg-[#0d0d0d] py-8 text-xs text-[#aaa9a3] max-sm:pb-24">
                <div className="w-[min(1280px,calc(100%-40px))] max-sm:w-[calc(100%-24px)] mx-auto flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div className="flex flex-col sm:flex-row items-center gap-3 sm:gap-6 text-center sm:text-left">
                        <Link href="/" className="inline-flex items-center" aria-label="Komikini Beranda">
                            <BrandLogo variant="full" className="h-6 w-auto" />
                        </Link>
                        <span className="text-xs text-[#aaa9a3]">Platform Baca Manga, Manhwa, dan Manhua Bahasa Indonesia.</span>
                    </div>
                    {correlationId && (
                        <p className="font-mono text-[10px] text-[#777771]">
                            ID: {correlationId}
                        </p>
                    )}
                </div>
            </footer>
        </div>
    );
}
