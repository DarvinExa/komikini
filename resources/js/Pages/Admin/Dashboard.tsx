import React, { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import ConfirmPasswordModal from '@/Components/Admin/ConfirmPasswordModal';

interface DashboardStats {
    total_users: number;
    active_users: number;
    suspended_users: number;
    total_comments: number;
    open_reports: number;
    total_comics: number;
    total_qualified_views: number;
}

interface RecentActivity {
    id: number;
    log_name: string;
    description: string;
    causer: {
        name: string;
        username: string | null;
    } | null;
    created_at: string | null;
    human_time: string;
}

interface DashboardProps {
    stats: DashboardStats;
    recentActivities: RecentActivity[];
    canManageCache: boolean;
}

export default function Dashboard({ stats, recentActivities, canManageCache }: DashboardProps) {
    const [cacheModalOpen, setCacheModalOpen] = useState(false);
    const [cacheTarget, setCacheTarget] = useState<'ranking' | 'all'>('ranking');
    const [isFlushing, setIsFlushing] = useState(false);
    const [errorMessage, setErrorMessage] = useState<string | null>(null);

    const handleOpenCacheModal = (target: 'ranking' | 'all') => {
        setCacheTarget(target);
        setErrorMessage(null);
        setCacheModalOpen(true);
    };

    const handleConfirmCacheFlush = (password: string) => {
        setIsFlushing(true);
        setErrorMessage(null);

        router.post(
            '/admin/cache/flush',
            {
                target: cacheTarget,
                password: password,
            },
            {
                onSuccess: () => {
                    setCacheModalOpen(false);
                    setIsFlushing(false);
                },
                onError: (errors) => {
                    setIsFlushing(false);
                    setErrorMessage(errors.password || errors.message || 'Gagal membersihkan cache.');
                },
            }
        );
    };

    return (
        <AdminLayout
            title="Dashboard Operasional"
            description="Ringkasan metrik sistem, antrean moderasi, pembersihan cache, dan aktivitas audit terkini."
        >
            <div className="space-y-8">
                {/* Metric Summary Cards Grid */}
                <section>
                    <h2 className="text-xs font-mono uppercase tracking-widest text-[#888888] mb-4">
                        Metrik Kunci Platform
                    </h2>
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        {/* Users Card */}
                        <div className="bg-[#141414] border border-[#262626] p-5">
                            <div className="text-[11px] font-mono uppercase tracking-wider text-[#888888] flex items-center justify-between">
                                <span>Pengguna Terdaftar</span>
                                <span className="text-[#BAD306]">● AKTIF</span>
                            </div>
                            <div className="text-3xl font-mono font-bold text-[#F8F8F8] mt-2">
                                {stats.total_users.toLocaleString()}
                            </div>
                            <div className="mt-3 pt-3 border-t border-[#222222] flex items-center justify-between text-[11px] font-mono text-[#888888]">
                                <span>Aktif: {stats.active_users}</span>
                                <span className={stats.suspended_users > 0 ? 'text-[#E56458]' : ''}>
                                    Ditangguhkan: {stats.suspended_users}
                                </span>
                            </div>
                        </div>

                        {/* Open Reports Card */}
                        <div className={`bg-[#141414] border p-5 ${stats.open_reports > 0 ? 'border-[#E56458]/60' : 'border-[#262626]'}`}>
                            <div className="text-[11px] font-mono uppercase tracking-wider text-[#888888] flex items-center justify-between">
                                <span>Laporan Menunggu</span>
                                {stats.open_reports > 0 ? (
                                    <span className="text-[#E56458] font-bold">PERLU TINDAKAN</span>
                                ) : (
                                    <span className="text-[#888888]">BERSIH</span>
                                )}
                            </div>
                            <div className={`text-3xl font-mono font-bold mt-2 ${stats.open_reports > 0 ? 'text-[#E56458]' : 'text-[#F8F8F8]'}`}>
                                {stats.open_reports}
                            </div>
                            <div className="mt-3 pt-3 border-t border-[#222222] flex items-center justify-between text-[11px] font-mono text-[#888888]">
                                <Link href="/admin/moderation" className="text-[#BAD306] hover:underline">
                                    Buka antrean moderasi →
                                </Link>
                            </div>
                        </div>

                        {/* Comments Count Card */}
                        <div className="bg-[#141414] border border-[#262626] p-5">
                            <div className="text-[11px] font-mono uppercase tracking-wider text-[#888888] flex items-center justify-between">
                                <span>Total Komentar</span>
                                <span className="text-[#888888]">KOMUNITAS</span>
                            </div>
                            <div className="text-3xl font-mono font-bold text-[#F8F8F8] mt-2">
                                {stats.total_comments.toLocaleString()}
                            </div>
                            <div className="mt-3 pt-3 border-t border-[#222222] flex items-center justify-between text-[11px] font-mono text-[#888888]">
                                <Link href="/admin/moderation?tab=comments" className="text-[#AAAAAA] hover:text-[#BAD306]">
                                    Kelola komentar →
                                </Link>
                            </div>
                        </div>

                        {/* Catalog & Qualified Views */}
                        <div className="bg-[#141414] border border-[#262626] p-5">
                            <div className="text-[11px] font-mono uppercase tracking-wider text-[#888888] flex items-center justify-between">
                                <span>Katalog & Views</span>
                                <span className="text-[#BAD306]">METRIK</span>
                            </div>
                            <div className="text-3xl font-mono font-bold text-[#F8F8F8] mt-2">
                                {stats.total_comics.toLocaleString()} <span className="text-sm font-normal text-[#888888]">judul</span>
                            </div>
                            <div className="mt-3 pt-3 border-t border-[#222222] flex items-center justify-between text-[11px] font-mono text-[#888888]">
                                <span>Qualified Views:</span>
                                <span className="text-[#F8F8F8] font-bold">{stats.total_qualified_views.toLocaleString()}</span>
                            </div>
                        </div>
                    </div>
                </section>

                {/* Operations & Cache Management Panel */}
                {canManageCache && (
                    <section className="bg-[#141414] border border-[#262626] p-6">
                        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4">
                            <div>
                                <h3 className="font-display text-sm tracking-wider uppercase text-[#F8F8F8]">
                                    Manajemen Cache Aplikasi
                                </h3>
                                <p className="text-xs text-[#888888] mt-1 font-sans">
                                    Bersihkan cache Redis/file untuk menyegarkan data agregasi peringkat atau seluruh aplikasi.
                                    Tindakan ini memerlukan verifikasi kata sandi akun Anda.
                                </p>
                            </div>
                        </div>

                        <div className="flex flex-wrap gap-3 pt-2">
                            <button
                                type="button"
                                onClick={() => handleOpenCacheModal('ranking')}
                                className="px-4 py-2 text-xs font-display tracking-wider uppercase bg-[#1A1A1A] hover:bg-[#222222] text-[#F8F8F8] border border-[#333333] hover:border-[#BAD306] transition-colors rounded-none"
                            >
                                Bersihkan Cache Peringkat
                            </button>
                            <button
                                type="button"
                                onClick={() => handleOpenCacheModal('all')}
                                className="px-4 py-2 text-xs font-display tracking-wider uppercase bg-[#1A1A1A] hover:bg-[#222222] text-[#E56458] border border-[#E56458]/40 hover:border-[#E56458] transition-colors rounded-none"
                            >
                                Flush Semua Cache (Global)
                            </button>
                        </div>
                    </section>
                )}

                {/* Recent Activities Section */}
                <section>
                    <div className="flex items-center justify-between mb-4">
                        <h2 className="text-xs font-mono uppercase tracking-widest text-[#888888]">
                            Aktivitas Audit Terkini
                        </h2>
                        <Link
                            href="/admin/audit-logs"
                            className="text-xs font-mono text-[#BAD306] hover:underline"
                        >
                            Lihat Semua Log →
                        </Link>
                    </div>

                    <div className="bg-[#141414] border border-[#262626] overflow-x-auto">
                        <table className="w-full text-left text-xs font-sans">
                            <thead className="bg-[#111111] border-b border-[#262626] text-[10px] font-mono uppercase tracking-wider text-[#888888]">
                                <tr>
                                    <th className="px-4 py-3">Waktu</th>
                                    <th className="px-4 py-3">Kategori</th>
                                    <th className="px-4 py-3">Aktivitas</th>
                                    <th className="px-4 py-3">Pelaku</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-[#202020]">
                                {recentActivities.length === 0 ? (
                                    <tr>
                                        <td colSpan={4} className="px-4 py-8 text-center text-[#888888] font-mono">
                                            Belum ada log aktivitas tercatat.
                                        </td>
                                    </tr>
                                ) : (
                                    recentActivities.map((act) => (
                                        <tr key={act.id} className="hover:bg-[#181818] transition-colors">
                                            <td className="px-4 py-3 text-[#888888] font-mono whitespace-nowrap">
                                                {act.human_time}
                                            </td>
                                            <td className="px-4 py-3 whitespace-nowrap">
                                                <span className="px-1.5 py-0.5 bg-[#1C1C1C] border border-[#333333] text-[10px] font-mono uppercase text-[#AAAAAA]">
                                                    {act.log_name}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3 text-[#F8F8F8] font-mono text-xs">
                                                {act.description}
                                            </td>
                                            <td className="px-4 py-3 text-[#AAAAAA] whitespace-nowrap">
                                                {act.causer ? (
                                                    <span>
                                                        {act.causer.name} <span className="text-[#666666] font-mono">(@{act.causer.username})</span>
                                                    </span>
                                                ) : (
                                                    <span className="text-[#666666] font-mono">Sistem</span>
                                                )}
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            {/* Cache Flush Confirmation Modal */}
            <ConfirmPasswordModal
                isOpen={cacheModalOpen}
                title={`Bersihkan Cache [${cacheTarget.toUpperCase()}]`}
                description={`Apakah Anda yakin ingin membersihkan cache kategori [${cacheTarget}]? Tindakan ini akan memaksa sistem meng-generate ulang data dari basis data pada pemanggilan berikutnya.`}
                confirmText="Konfirmasi Pembersihan"
                confirmVariant={cacheTarget === 'all' ? 'danger' : 'primary'}
                errorMessage={errorMessage}
                isProcessing={isFlushing}
                onClose={() => setCacheModalOpen(false)}
                onConfirm={handleConfirmCacheFlush}
            />
        </AdminLayout>
    );
}
