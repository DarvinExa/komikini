import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

interface AuditLogItem {
    id: number;
    log_name: string;
    description: string;
    properties: Record<string, unknown> | null;
    causer: {
        id: number;
        name: string;
        username: string | null;
    } | null;
    subject_type: string | null;
    subject_id: string | number | null;
    created_at: string | null;
    human_time: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedLogs {
    data: AuditLogItem[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

interface AuditLogsIndexProps {
    logs: PaginatedLogs;
    filters: {
        log_name: string;
        search: string;
    };
    availableLogNames: string[];
}

export default function AuditLogsIndex({ logs, filters, availableLogNames }: AuditLogsIndexProps) {
    const [logName, setLogName] = useState(filters.log_name || '');
    const [search, setSearch] = useState(filters.search || '');
    const [inspectedLog, setInspectedLog] = useState<AuditLogItem | null>(null);

    const handleFilterSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/admin/audit-logs', { log_name: logName, search }, { preserveState: true });
    };

    const handleReset = () => {
        setLogName('');
        setSearch('');
        router.get('/admin/audit-logs', {}, { preserveState: true });
    };

    return (
        <AdminLayout
            title="Log Audit & Kepatuhan Sistem"
            description="Pencatatan aktivitas administratif, perubahan peran, moderasi konten, pembersihan cache, dan penegakan keamanan."
        >
            <div className="space-y-6">
                {/* Search & Filter Bar */}
                <form
                    onSubmit={handleFilterSubmit}
                    className="bg-[#141414] border border-[#262626] p-4 flex flex-col md:flex-row items-stretch md:items-center gap-3"
                >
                    <div className="flex-1">
                        <input
                            type="text"
                            placeholder="Cari deskripsi atau muatan log..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            className="w-full bg-[#0D0D0D] border border-[#333333] focus:border-[#BAD306] focus:ring-0 text-xs px-3 py-2 text-[#F8F8F8] font-sans rounded-none placeholder:text-[#666666]"
                        />
                    </div>
                    <div className="w-full md:w-56">
                        <select
                            value={logName}
                            onChange={(e) => setLogName(e.target.value)}
                            className="w-full bg-[#0D0D0D] border border-[#333333] focus:border-[#BAD306] focus:ring-0 text-xs px-3 py-2 text-[#F8F8F8] font-sans rounded-none"
                        >
                            <option value="">Semua Kategori Log</option>
                            {availableLogNames.map((name) => (
                                <option key={name} value={name}>
                                    {name.toUpperCase()}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="flex items-center gap-2">
                        <button
                            type="submit"
                            className="px-4 py-2 text-xs font-display tracking-wider uppercase bg-[#BAD306] text-[#111111] hover:bg-[#a6bd05] font-bold rounded-none"
                        >
                            Filter
                        </button>
                        {(logName || search) && (
                            <button
                                type="button"
                                onClick={handleReset}
                                className="px-3 py-2 text-xs font-display tracking-wider uppercase bg-[#222222] hover:bg-[#2A2A2A] text-[#AAAAAA] hover:text-white border border-[#333333] rounded-none"
                            >
                                Reset
                            </button>
                        )}
                    </div>
                </form>

                {/* Audit Logs Table */}
                <div className="bg-[#141414] border border-[#262626] overflow-x-auto">
                    <table className="w-full text-left text-xs font-sans">
                        <thead className="bg-[#111111] border-b border-[#262626] text-[10px] font-mono uppercase tracking-wider text-[#888888]">
                            <tr>
                                <th className="px-4 py-3">ID & Waktu</th>
                                <th className="px-4 py-3">Kategori</th>
                                <th className="px-4 py-3">Aktivitas / Event</th>
                                <th className="px-4 py-3">Pelaku (Causer)</th>
                                <th className="px-4 py-3">Subjek Target</th>
                                <th className="px-4 py-3 text-right">Detail Muatan</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-[#202020]">
                            {logs.data.length === 0 ? (
                                <tr>
                                    <td colSpan={6} className="px-4 py-10 text-center text-[#888888] font-mono">
                                        Tidak ditemukan log aktivitas sesuai filter yang diberikan.
                                    </td>
                                </tr>
                            ) : (
                                logs.data.map((log) => (
                                    <tr key={log.id} className="hover:bg-[#181818] transition-colors">
                                        <td className="px-4 py-3 whitespace-nowrap">
                                            <div className="font-mono font-bold text-[#F8F8F8]">#{log.id}</div>
                                            <div className="text-[10px] font-mono text-[#888888]">{log.human_time}</div>
                                            <div className="text-[9px] font-mono text-[#555555]">
                                                {log.created_at ? new Date(log.created_at).toLocaleString('id-ID') : '-'}
                                            </div>
                                        </td>
                                        <td className="px-4 py-3 whitespace-nowrap">
                                            <span className="px-2 py-0.5 bg-[#1A1A1A] border border-[#333333] text-[10px] font-mono uppercase text-[#BAD306]">
                                                {log.log_name}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3 max-w-sm">
                                            <div className="font-mono text-[#F8F8F8] font-semibold text-xs">
                                                {log.description}
                                            </div>
                                        </td>
                                        <td className="px-4 py-3 whitespace-nowrap">
                                            {log.causer ? (
                                                <div>
                                                    <div className="text-[#F8F8F8] font-semibold">{log.causer.name}</div>
                                                    <div className="text-[10px] font-mono text-[#888888]">
                                                        {log.causer.username ? `@${log.causer.username}` : `ID: ${log.causer.id}`}
                                                    </div>
                                                </div>
                                            ) : (
                                                <span className="text-[#666666] font-mono text-[11px]">Sistem Otomatis</span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 whitespace-nowrap font-mono text-[11px] text-[#AAAAAA]">
                                            {log.subject_type ? (
                                                <span>
                                                    {log.subject_type} <span className="text-[#666666]">#{log.subject_id}</span>
                                                </span>
                                            ) : (
                                                <span className="text-[#555555]">-</span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-right whitespace-nowrap">
                                            {log.properties && Object.keys(log.properties).length > 0 ? (
                                                <button
                                                    type="button"
                                                    onClick={() => setInspectedLog(log)}
                                                    className="px-2.5 py-1 text-[11px] font-mono uppercase tracking-wider text-[#BAD306] bg-[#182012] hover:bg-[#223018] border border-[#BAD306]/40 hover:border-[#BAD306] rounded-none transition-colors"
                                                >
                                                    JSON [{Object.keys(log.properties).length}]
                                                </button>
                                            ) : (
                                                <span className="text-[#555555] font-mono text-[11px]">Kosong</span>
                                            )}
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

                {/* Pagination Controls */}
                {logs.total > 0 && (
                    <div className="flex flex-col sm:flex-row items-center justify-between gap-4 pt-2 text-xs font-mono text-[#888888]">
                        <div>
                            Menampilkan {logs.from || 0} - {logs.to || 0} dari {logs.total} log audit
                        </div>
                        <div className="flex items-center gap-1">
                            {logs.links.map((link, idx) => (
                                <button
                                    key={idx}
                                    type="button"
                                    disabled={!link.url}
                                    onClick={() => link.url && router.get(link.url, {}, { preserveState: true })}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                    className={`px-3 py-1 text-xs border rounded-none ${
                                        link.active
                                            ? 'bg-[#BAD306] text-[#111111] font-bold border-[#BAD306]'
                                            : link.url
                                            ? 'bg-[#141414] text-[#AAAAAA] hover:text-[#F8F8F8] border-[#333333]'
                                            : 'bg-[#111111] text-[#555555] border-[#222222] cursor-not-allowed'
                                    }`}
                                />
                            ))}
                        </div>
                    </div>
                )}
            </div>

            {/* Properties JSON Inspector Modal */}
            {inspectedLog && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80">
                    <div className="w-full max-w-2xl bg-[#161616] border-2 border-[#333333] shadow-2xl p-6 text-[#F8F8F8] rounded-none flex flex-col max-h-[85vh]">
                        <div className="flex items-center justify-between border-b border-[#262626] pb-3 mb-4 shrink-0">
                            <div>
                                <h3 className="font-display text-base tracking-wider uppercase text-[#F8F8F8]">
                                    Muatan Log #{inspectedLog.id} &bull; {inspectedLog.log_name}
                                </h3>
                                <p className="text-xs font-mono text-[#888888] mt-0.5">
                                    {inspectedLog.description}
                                </p>
                            </div>
                            <button
                                type="button"
                                onClick={() => setInspectedLog(null)}
                                className="text-[#888888] hover:text-white px-2 py-1 text-sm font-mono border border-transparent hover:border-[#444444] rounded-none"
                            >
                                [X]
                            </button>
                        </div>

                        <div className="flex-1 min-h-0 overflow-y-auto bg-[#0A0A0A] border border-[#222222] p-4">
                            <pre className="text-xs font-mono text-[#BAD306] whitespace-pre-wrap break-words">
                                {JSON.stringify(inspectedLog.properties, null, 2)}
                            </pre>
                        </div>

                        <div className="flex items-center justify-end pt-4 border-t border-[#262626] shrink-0 mt-4">
                            <button
                                type="button"
                                onClick={() => setInspectedLog(null)}
                                className="px-4 py-2 text-xs font-display tracking-wider uppercase bg-[#222222] hover:bg-[#2A2A2A] text-white border border-[#333333] rounded-none"
                            >
                                Tutup
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}
