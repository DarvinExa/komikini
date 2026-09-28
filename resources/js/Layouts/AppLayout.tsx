import React, { ReactNode, useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import { PageProps } from '@/types';
import { SearchBox } from '@/Components/SearchBox';

interface AppLayoutProps {
    title?: string;
    description?: string;
    children: ReactNode;
}

export default function AppLayout({ title, description, children }: AppLayoutProps) {
    const { appName, auth, correlationId } = usePage<PageProps>().props;
    const user = auth.user;
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

    const navLinks = [
        { href: '/', label: 'Beranda' },
        { href: '/terbaru', label: 'Terbaru' },
        { href: '/type/manga', label: 'Manga' },
        { href: '/type/manhwa', label: 'Manhwa' },
        { href: '/type/manhua', label: 'Manhua' },
        { href: '/genre', label: 'Genre' },
        { href: '/pustaka', label: 'Pustaka' },
    ];

    return (
        <div className="min-h-screen flex flex-col bg-[#1A1A1A] text-[#F8F8F8] font-sans antialiased overflow-x-hidden w-full max-w-full selection:bg-[#BAD306] selection:text-[#111111]">
            <Head>
                <title>{title ? `${title} - ${appName}` : appName}</title>
                {description && <meta name="description" content={description} />}
            </Head>

            {/* Sticky Header with 2px bottom border */}
            <header className="sticky top-0 z-40 border-b-2 border-[#222222] bg-[#111111] w-full max-w-full">
                <div className="w-full max-w-[1440px] mx-auto px-4 sm:px-6 h-14 sm:h-16 flex items-center justify-between gap-3">
                    {/* Brand / Logo */}
                    <div className="flex items-center gap-6 shrink-0">
                        <Link
                            href="/"
                            className="flex items-center gap-1.5 focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#BAD306]"
                        >
                            <span className="font-display text-2xl sm:text-3xl tracking-wider text-[#F8F8F8] uppercase">
                                {appName}
                            </span>
                            <span className="font-display text-[10px] tracking-widest text-[#111111] bg-[#BAD306] px-1 py-0.5 uppercase">
                                ID
                            </span>
                        </Link>

                        {/* Desktop Navigation Links */}
                        <nav className="hidden lg:flex items-center space-x-1" aria-label="Navigasi Utama">
                            {navLinks.map((link) => (
                                <Link
                                    key={link.href}
                                    href={link.href}
                                    className="px-3 py-1 font-display text-sm tracking-wider uppercase text-[#AAAAAA] hover:text-[#F8F8F8] hover:bg-[#161616] border border-transparent hover:border-[#444444] transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#BAD306] rounded-none"
                                >
                                    {link.label}
                                </Link>
                            ))}
                        </nav>
                    </div>

                    {/* Desktop Search in Center */}
                    <div className="hidden sm:block flex-1 max-w-sm md:max-w-md mx-2">
                        <SearchBox />
                    </div>

                    {/* Right User Actions */}
                    <div className="flex items-center gap-2 sm:gap-3 shrink-0">
                        {user ? (
                            <div className="hidden sm:flex items-center gap-2 sm:gap-3">
                                <Link
                                    href="/pustaka"
                                    className="font-display text-xs tracking-wider uppercase text-[#F8F8F8] bg-[#161616] border border-[#333333] hover:border-[#BAD306] hover:text-[#BAD306] px-2.5 py-1.5 transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#BAD306] rounded-none"
                                >
                                    Pustaka
                                </Link>
                                <Link
                                    href="/profile"
                                    className="font-display text-xs tracking-wider uppercase text-[#F8F8F8] hover:text-[#BAD306] px-2 py-1.5 border border-transparent hover:border-[#444444] transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#BAD306] rounded-none"
                                >
                                    <span className="hidden xl:inline text-[#AAAAAA]">Akun: </span>
                                    <span>{user.name}</span>
                                </Link>
                                <Link
                                    href="/logout"
                                    method="post"
                                    as="button"
                                    className="font-display text-xs tracking-wider uppercase text-[#E56458] hover:text-white hover:bg-[#E56458] border border-[#E56458] px-2.5 py-1.5 transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#E56458] rounded-none"
                                >
                                    Keluar
                                </Link>
                            </div>
                        ) : (
                            <div className="hidden sm:flex items-center gap-2">
                                <Link
                                    href="/login"
                                    className="font-display text-xs tracking-wider uppercase text-[#F8F8F8] bg-[#161616] border border-[#444444] hover:border-[#BAD306] hover:text-[#BAD306] px-3 py-1.5 transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#BAD306] rounded-none"
                                >
                                    Masuk
                                </Link>
                                <Link
                                    href="/register"
                                    className="font-display text-xs tracking-wider uppercase text-[#111111] bg-[#BAD306] hover:bg-[#E0FF00] border-2 border-[#BAD306] px-3.5 py-1.5 transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#BAD306] rounded-none"
                                >
                                    Daftar
                                </Link>
                            </div>
                        )}

                        {/* Mobile Menu Toggle Button */}
                        <button
                            type="button"
                            onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
                            className="lg:hidden p-2 text-[#AAAAAA] hover:text-[#F8F8F8] border border-[#444444] bg-[#161616] focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#BAD306] rounded-none"
                            aria-expanded={mobileMenuOpen}
                            aria-label="Buka menu navigasi"
                        >
                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                {mobileMenuOpen ? (
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
                                ) : (
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 6h16M4 12h16M4 18h16" />
                                )}
                            </svg>
                        </button>
                    </div>
                </div>

                {/* Mobile Drawer */}
                {mobileMenuOpen && (
                    <div className="lg:hidden border-t-2 border-[#222222] bg-[#111111] px-4 pt-3 pb-5 space-y-3">
                        <div className="sm:hidden mb-3">
                            <SearchBox />
                        </div>
                        <nav className="grid grid-cols-2 gap-2" aria-label="Menu Mobile">
                            {navLinks.map((link) => (
                                <Link
                                    key={link.href}
                                    href={link.href}
                                    onClick={() => setMobileMenuOpen(false)}
                                    className="px-3 py-2 font-display text-sm tracking-wider uppercase text-[#F8F8F8] bg-[#161616] border border-[#333333] hover:border-[#BAD306] hover:text-[#BAD306] transition-colors rounded-none"
                                >
                                    {link.label}
                                </Link>
                            ))}
                        </nav>
                        {!user ? (
                            <div className="sm:hidden flex items-center gap-2 pt-2 border-t border-[#222222]">
                                <Link
                                    href="/login"
                                    onClick={() => setMobileMenuOpen(false)}
                                    className="flex-1 text-center py-2 font-display text-xs tracking-wider uppercase text-[#F8F8F8] bg-[#161616] border border-[#444444]"
                                >
                                    Masuk
                                </Link>
                                <Link
                                    href="/register"
                                    onClick={() => setMobileMenuOpen(false)}
                                    className="flex-1 text-center py-2 font-display text-xs tracking-wider uppercase text-[#111111] bg-[#BAD306] border-2 border-[#BAD306]"
                                >
                                    Daftar
                                </Link>
                            </div>
                        ) : (
                            <div className="sm:hidden flex items-center gap-2 pt-2 border-t border-[#222222]">
                                <Link
                                    href="/profile"
                                    onClick={() => setMobileMenuOpen(false)}
                                    className="flex-1 text-center py-2 font-display text-xs tracking-wider uppercase text-[#F8F8F8] bg-[#161616] border border-[#444444]"
                                >
                                    Akun: {user.name}
                                </Link>
                                <Link
                                    href="/logout"
                                    method="post"
                                    as="button"
                                    onClick={() => setMobileMenuOpen(false)}
                                    className="flex-1 text-center py-2 font-display text-xs tracking-wider uppercase text-[#E56458] border border-[#E56458]"
                                >
                                    Keluar
                                </Link>
                            </div>
                        )}
                    </div>
                )}
            </header>

            {/* Main Content Area */}
            <main id="main-content" className="flex-1 w-full max-w-[1440px] min-w-0 mx-auto px-4 sm:px-6 py-6 sm:py-8">
                {children}
            </main>

            {/* Footer */}
            <footer className="border-t-2 border-[#222222] bg-[#111111] py-8 text-xs text-[#AAAAAA]">
                <div className="max-w-[1440px] mx-auto px-4 sm:px-6 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div className="flex flex-col sm:flex-row items-center gap-2 sm:gap-6 text-center sm:text-left">
                        <span className="font-display text-lg tracking-wider text-[#F8F8F8] uppercase">{appName}</span>
                        <span className="text-xs text-[#AAAAAA]">Platform Baca Manga, Manhwa, dan Manhua Bahasa Indonesia.</span>
                    </div>
                    {correlationId && (
                        <p className="font-mono text-[10px] text-[#777777]">
                            ID: {correlationId}
                        </p>
                    )}
                </div>
            </footer>
        </div>
    );
}
