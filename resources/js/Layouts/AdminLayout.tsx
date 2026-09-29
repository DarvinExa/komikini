import React, { ReactNode } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import { PageProps } from '@/types';

interface AdminLayoutProps {
    title: string;
    description?: string;
    children: ReactNode;
}

export default function AdminLayout({ title, description, children }: AdminLayoutProps) {
    const { auth, flash, appName } = usePage<PageProps>().props;
    const user = auth.user;

    const navItems = [
        {
            group: 'Ikhtisar',
            items: [
                { href: '/admin', label: 'Dashboard', routeName: 'admin.dashboard' },
            ],
        },
        {
            group: 'Akses & Pengguna',
            items: [
                { href: '/admin/users', label: 'Manajemen Pengguna', routeName: 'admin.users.index' },
                { href: '/admin/roles', label: 'Peran & Permission', routeName: 'admin.roles.index' },
            ],
        },
        {
            group: 'Moderasi Komunitas',
            items: [
                { href: '/admin/moderation', label: 'Konsol Moderasi', routeName: 'admin.moderation.index' },
            ],
        },
        {
            group: 'Sistem & Kepatuhan',
            items: [
                { href: '/admin/audit-logs', label: 'Audit Logs', routeName: 'admin.audit-logs.index' },
            ],
        },
    ];

    const currentUrl = typeof window !== 'undefined' ? window.location.pathname : '';

    return (
        <div className="min-h-screen bg-[#0D0D0D] text-[#F8F8F8] font-sans antialiased flex flex-col md:flex-row">
            <Head>
                <title>{`${title} - Admin ${appName}`}</title>
                {description && <meta name="description" content={description} />}
            </Head>

            {/* Accessible Skip Link (WCAG 2.2 AA) */}
            <a
                href="#admin-main-content"
                className="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:px-4 focus:py-2.5 focus:bg-[#BAD306] focus:text-[#111111] focus:font-display focus:text-sm focus:font-bold focus:outline-none focus:ring-2 focus:ring-[#111111] rounded-none shadow-lg"
            >
                Lewati ke konten utama admin
            </a>

            {/* Admin Sidebar */}
            <aside className="w-full md:w-64 bg-[#141414] border-b md:border-b-0 md:border-r border-[#262626] shrink-0 flex flex-col justify-between">
                <div>
                    {/* Header / Brand */}
                    <div className="p-4 md:p-6 border-b border-[#262626] bg-[#111111]">
                        <div className="flex items-center gap-2">
                            <span className="font-display text-xl tracking-wider uppercase text-[#F8F8F8]">
                                {appName}
                            </span>
                            <span className="font-display text-[9px] tracking-widest text-[#111111] bg-[#BAD306] px-1.5 py-0.5 font-bold uppercase">
                                ADMIN
                            </span>
                        </div>
                        <p className="text-[11px] font-mono text-[#888888] mt-1">Konsol Operasional Sistem</p>
                    </div>

                    {/* Navigation Menu */}
                    <nav className="p-3 space-y-6">
                        {navItems.map((group) => (
                            <div key={group.group}>
                                <div className="px-3 mb-2 text-[10px] font-mono uppercase tracking-widest text-[#666666]">
                                    {group.group}
                                </div>
                                <div className="space-y-1">
                                    {group.items.map((item) => {
                                        const isActive = currentUrl === item.href || (item.href !== '/admin' && currentUrl.startsWith(item.href));
                                        return (
                                            <Link
                                                key={item.href}
                                                href={item.href}
                                                className={`flex items-center gap-3 px-3 py-2 text-xs font-display tracking-wider uppercase transition-colors rounded-none ${
                                                    isActive
                                                        ? 'bg-[#1F1F1F] text-[#BAD306] border-l-2 border-[#BAD306] font-bold'
                                                        : 'text-[#AAAAAA] hover:text-[#F8F8F8] hover:bg-[#1A1A1A] border-l-2 border-transparent'
                                                }`}
                                            >
                                                <span>{item.label}</span>
                                            </Link>
                                        );
                                    })}
                                </div>
                            </div>
                        ))}

                        {/* Back to Public Web */}
                        <div className="pt-2 border-t border-[#222222]">
                            <Link
                                href="/"
                                className="flex items-center gap-2 px-3 py-2 text-xs font-display tracking-wider uppercase text-[#888888] hover:text-[#BAD306] hover:bg-[#1A1A1A] transition-colors rounded-none"
                            >
                                <span>&larr;</span>
                                <span>Kembali ke Situs</span>
                            </Link>
                        </div>
                    </nav>
                </div>

                {/* Authenticated User Panel */}
                <div className="p-4 border-t border-[#262626] bg-[#111111]">
                    <div className="flex items-center justify-between">
                        <div className="truncate mr-2">
                            <div className="text-xs font-semibold text-[#F8F8F8] truncate">{user?.name}</div>
                            <div className="text-[10px] font-mono text-[#888888] truncate">
                                {user?.roles && user.roles.length > 0 ? (
                                    <span className="text-[#BAD306] uppercase">{user.roles[0]}</span>
                                ) : (
                                    user?.email
                                )}
                            </div>
                        </div>
                        <Link
                            href="/logout"
                            method="post"
                            as="button"
                            className="shrink-0 px-2 py-1 text-[10px] font-mono tracking-wider uppercase text-[#E56458] hover:text-white hover:bg-[#E56458] border border-[#E56458]/40 hover:border-[#E56458] transition-colors rounded-none"
                        >
                            Keluar
                        </Link>
                    </div>
                </div>
            </aside>

            {/* Main Content Area */}
            <main id="admin-main-content" className="flex-1 flex flex-col min-w-0">
                {/* Topbar */}
                <header className="h-14 border-b border-[#262626] bg-[#111111] px-6 flex items-center justify-between">
                    <div>
                        <h1 className="font-display text-base tracking-wider uppercase text-[#F8F8F8]">
                            {title}
                        </h1>
                        {description && (
                            <p className="text-[11px] text-[#888888] font-sans truncate">{description}</p>
                        )}
                    </div>
                    <div className="flex items-center gap-3">
                        <span className="inline-flex items-center gap-1.5 px-2 py-0.5 border border-[#333333] bg-[#161616] text-[10px] font-mono text-[#888888]">
                            <span className="w-1.5 h-1.5 bg-[#BAD306] inline-block"></span>
                            PROD OPS
                        </span>
                    </div>
                </header>

                {/* Flash Messages */}
                {flash.success && (
                    <div className="mx-6 mt-4 p-3 bg-[#BAD306]/10 border border-[#BAD306] text-[#BAD306] text-xs font-mono flex items-center justify-between" role="status" aria-live="polite">
                        <span>[OK] {flash.success}</span>
                    </div>
                )}
                {flash.error && (
                    <div className="mx-6 mt-4 p-3 bg-[#E56458]/10 border border-[#E56458] text-[#E56458] text-xs font-mono flex items-center justify-between" role="alert">
                        <span>[ERROR] {flash.error}</span>
                    </div>
                )}

                {/* Page Content Body */}
                <div className="p-6 flex-1 overflow-x-hidden">
                    {children}
                </div>
            </main>
        </div>
    );
}
