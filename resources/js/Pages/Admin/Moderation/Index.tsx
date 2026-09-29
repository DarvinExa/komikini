import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

interface ReportItem {
    id: number;
    reason_code: string;
    details: string | null;
    status: 'open' | 'resolved' | 'dismissed';
    created_at: string | null;
    human_time: string;
    reporter: {
        name: string;
        username: string | null;
    } | null;
    comment: {
        id: number;
        body: string;
        status: string;
        chapter_key: string | null;
        author: {
            name: string;
            username: string | null;
        } | null;
        comic: {
            slug: string;
            title: string;
        } | null;
    } | null;
    resolver: {
        name: string;
    } | null;
    resolved_at: string | null;
}

interface CommentItem {
    id: number;
    public_id: string;
    body: string;
    status: 'published' | 'hidden' | 'deleted';
    chapter_key: string | null;
    reports_count: number;
    moderation_reason: string | null;
    created_at: string | null;
    human_time: string;
    user: {
        id: number;
        name: string;
        username: string | null;
    } | null;
    comic: {
        slug: string;
        title: string;
    } | null;
    moderator: {
        name: string;
    } | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedData<T> {
    data: T[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

interface ModerationIndexProps {
    activeTab: 'reports' | 'comments';
    reports: PaginatedData<ReportItem> | null;
    comments: PaginatedData<CommentItem> | null;
    canResolveReports: boolean;
    canModerateComments: boolean;
    filters: {
        report_status: string;
        comment_status: string;
        comment_search: string;
    };
}

export default function ModerationIndex({
    activeTab,
    reports,
    comments,
    canResolveReports,
    canModerateComments,
    filters,
}: ModerationIndexProps) {
    const [currentTab, setCurrentTab] = useState<'reports' | 'comments'>(activeTab);

    // Filters states
    const [reportStatus, setReportStatus] = useState(filters.report_status || 'open');
    const [commentStatus, setCommentStatus] = useState(filters.comment_status || 'all');
    const [commentSearch, setCommentSearch] = useState(filters.comment_search || '');

    // Report resolve modal
    const [selectedReport, setSelectedReport] = useState<ReportItem | null>(null);
    const [resolveStatus, setResolveStatus] = useState<'resolved' | 'dismissed'>('resolved');
    const [resolveAction, setResolveAction] = useState<'none' | 'hide' | 'delete'>('hide');
    const [resolveReason, setResolveReason] = useState('');
    const [isResolving, setIsResolving] = useState(false);
    const [resolveError, setResolveError] = useState<string | null>(null);

    // Bulk comments moderation
    const [selectedCommentIds, setSelectedCommentIds] = useState<number[]>([]);
    const [bulkModalOpen, setBulkModalOpen] = useState(false);
    const [bulkAction, setBulkAction] = useState<'hide' | 'unhide' | 'delete'>('hide');
    const [bulkReason, setBulkReason] = useState('');
    const [isBulking, setIsBulking] = useState(false);
    const [bulkError, setBulkError] = useState<string | null>(null);

    const switchTab = (tab: 'reports' | 'comments') => {
        setCurrentTab(tab);
        router.get(
            '/admin/moderation',
            {
                tab,
                report_status: reportStatus,
                comment_status: commentStatus,
                comment_search: commentSearch,
            },
            { preserveState: true }
        );
    };

    const handleReportFilterChange = (status: string) => {
        setReportStatus(status);
        router.get(
            '/admin/moderation',
            { tab: 'reports', report_status: status },
            { preserveState: true }
        );
    };

    const handleCommentFilterSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(
            '/admin/moderation',
            {
                tab: 'comments',
                comment_status: commentStatus,
                comment_search: commentSearch,
            },
            { preserveState: true }
        );
    };

    // Report resolve submit
    const openResolveModal = (report: ReportItem) => {
        setSelectedReport(report);
        setResolveStatus('resolved');
        setResolveAction('hide');
        setResolveReason(`Ditindak berdasarkan laporan #${report.id}: ${report.reason_code}`);
        setResolveError(null);
    };

    const handleResolveSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedReport || isResolving) return;

        setIsResolving(true);
        setResolveError(null);

        router.post(
            `/admin/reports/${selectedReport.id}/resolve`,
            {
                status: resolveStatus,
                action: resolveStatus === 'resolved' ? resolveAction : 'none',
                reason: resolveReason,
            },
            {
                onSuccess: () => {
                    setIsResolving(false);
                    setSelectedReport(null);
                },
                onError: (errors) => {
                    setIsResolving(false);
                    setResolveError(errors.message || errors.reason || 'Gagal memproses laporan.');
                },
            }
        );
    };

    // Bulk comment selection
    const toggleSelectComment = (id: number) => {
        if (selectedCommentIds.includes(id)) {
            setSelectedCommentIds(selectedCommentIds.filter((cid) => cid !== id));
        } else {
            setSelectedCommentIds([...selectedCommentIds, id]);
        }
    };

    const toggleSelectAllComments = () => {
        if (!comments) return;
        if (selectedCommentIds.length === comments.data.length) {
            setSelectedCommentIds([]);
        } else {
            setSelectedCommentIds(comments.data.map((c) => c.id));
        }
    };

    const openBulkModal = (action: 'hide' | 'unhide' | 'delete', singleId?: number) => {
        if (singleId) {
            setSelectedCommentIds([singleId]);
        }
        setBulkAction(action);
        setBulkReason(
            action === 'hide'
                ? 'Komentar disembunyikan karena melanggar aturan komunitas.'
                : action === 'unhide'
                ? 'Komentar dipulihkan setelah peninjauan.'
                : 'Komentar dihapus permanen oleh moderator.'
        );
        setBulkError(null);
        setBulkModalOpen(true);
    };

    const handleBulkSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (selectedCommentIds.length === 0 || isBulking) return;

        setIsBulking(true);
        setBulkError(null);

        router.post(
            '/admin/comments/bulk-moderate',
            {
                comment_ids: selectedCommentIds,
                action: bulkAction,
                reason: bulkReason,
            },
            {
                onSuccess: () => {
                    setIsBulking(false);
                    setBulkModalOpen(false);
                    setSelectedCommentIds([]);
                },
                onError: (errors) => {
                    setIsBulking(false);
                    setBulkError(errors.reason || errors.comment_ids || errors.message || 'Gagal memoderasi komentar.');
                },
            }
        );
    };

    return (
        <AdminLayout
            title="Konsol Moderasi Komunitas"
            description="Antrean penanganan laporan komentar pengguna, sensor konten, penangguhan komentar, dan tindakan massal."
        >
            <div className="space-y-6">
                {/* Tab Switcher */}
                <div className="flex border-b border-[#262626] bg-[#111111]">
                    {reports !== null && (
                        <button
                            type="button"
                            onClick={() => switchTab('reports')}
                            className={`px-5 py-3 text-xs font-display tracking-wider uppercase transition-colors rounded-none border-b-2 flex items-center gap-2 ${
                                currentTab === 'reports'
                                    ? 'border-[#BAD306] text-[#BAD306] font-bold bg-[#1A1A1A]'
                                    : 'border-transparent text-[#AAAAAA] hover:text-[#F8F8F8] hover:bg-[#161616]'
                            }`}
                        >
                            <span>Antrean Laporan Komentar</span>
                            {reports.total > 0 && (
                                <span className="px-1.5 py-0.2 bg-[#E56458] text-white text-[10px] font-mono">
                                    {reports.total}
                                </span>
                            )}
                        </button>
                    )}
                    {comments !== null && (
                        <button
                            type="button"
                            onClick={() => switchTab('comments')}
                            className={`px-5 py-3 text-xs font-display tracking-wider uppercase transition-colors rounded-none border-b-2 ${
                                currentTab === 'comments'
                                    ? 'border-[#BAD306] text-[#BAD306] font-bold bg-[#1A1A1A]'
                                    : 'border-transparent text-[#AAAAAA] hover:text-[#F8F8F8] hover:bg-[#161616]'
                            }`}
                        >
                            Semua Komentar Komunitas
                        </button>
                    )}
                </div>

                {/* REPORTS TAB CONTENT */}
                {currentTab === 'reports' && reports && (
                    <div className="space-y-4">
                        {/* Reports Status Filter */}
                        <div className="flex items-center gap-2 bg-[#141414] border border-[#262626] p-3 text-xs font-sans">
                            <span className="text-[#888888] font-mono text-[11px] uppercase mr-2">Status Laporan:</span>
                            {['open', 'resolved', 'dismissed', 'all'].map((st) => (
                                <button
                                    key={st}
                                    type="button"
                                    onClick={() => handleReportFilterChange(st)}
                                    className={`px-3 py-1 text-xs font-mono uppercase rounded-none border ${
                                        reportStatus === st
                                            ? 'bg-[#BAD306] text-[#111111] font-bold border-[#BAD306]'
                                            : 'bg-[#1C1C1C] text-[#AAAAAA] hover:text-white border-[#333333]'
                                    }`}
                                >
                                    {st === 'open' ? 'Menunggu (Open)' : st === 'resolved' ? 'Selesai (Resolved)' : st === 'dismissed' ? 'Ditolak (Dismissed)' : 'Semua'}
                                </button>
                            ))}
                        </div>

                        {/* Reports Table */}
                        <div className="bg-[#141414] border border-[#262626] overflow-x-auto">
                            <table className="w-full text-left text-xs font-sans">
                                <thead className="bg-[#111111] border-b border-[#262626] text-[10px] font-mono uppercase tracking-wider text-[#888888]">
                                    <tr>
                                        <th className="px-4 py-3">ID & Waktu</th>
                                        <th className="px-4 py-3">Pelapor & Alasan</th>
                                        <th className="px-4 py-3">Isi Komentar Dilaporkan</th>
                                        <th className="px-4 py-3">Status</th>
                                        <th className="px-4 py-3 text-right">Tindakan</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-[#202020]">
                                    {reports.data.length === 0 ? (
                                        <tr>
                                            <td colSpan={5} className="px-4 py-10 text-center text-[#888888] font-mono">
                                                Tidak ada laporan dengan status [{reportStatus}].
                                            </td>
                                        </tr>
                                    ) : (
                                        reports.data.map((rep) => (
                                            <tr key={rep.id} className="hover:bg-[#181818] transition-colors">
                                                <td className="px-4 py-3 whitespace-nowrap">
                                                    <div className="font-mono font-bold text-[#F8F8F8]">#{rep.id}</div>
                                                    <div className="text-[10px] font-mono text-[#888888]">{rep.human_time}</div>
                                                </td>
                                                <td className="px-4 py-3">
                                                    <div className="font-mono text-[#BAD306] font-bold uppercase text-[11px]">
                                                        {rep.reason_code}
                                                    </div>
                                                    {rep.details && (
                                                        <div className="text-[#CCCCCC] text-[11px] mt-0.5 italic">
                                                            &ldquo;{rep.details}&rdquo;
                                                        </div>
                                                    )}
                                                    <div className="text-[10px] font-mono text-[#888888] mt-1">
                                                        Oleh: {rep.reporter ? `@${rep.reporter.username || rep.reporter.name}` : 'Anonim'}
                                                    </div>
                                                </td>
                                                <td className="px-4 py-3 max-w-md">
                                                    {rep.comment ? (
                                                        <div>
                                                            <div className="text-[#F8F8F8] line-clamp-2 bg-[#0D0D0D] border border-[#222222] p-2 text-xs">
                                                                {rep.comment.body}
                                                            </div>
                                                            <div className="flex items-center gap-2 text-[10px] font-mono text-[#888888] mt-1">
                                                                <span>Pengarang: @{rep.comment.author?.username || rep.comment.author?.name || 'User'}</span>
                                                                <span>&bull;</span>
                                                                <span>Komik: {rep.comment.comic?.title || '-'}</span>
                                                                <span>&bull;</span>
                                                                <span className="uppercase text-[#AAAAAA]">Status: {rep.comment.status}</span>
                                                            </div>
                                                        </div>
                                                    ) : (
                                                        <span className="text-[#888888] font-mono">[Komentar sudah tidak tersedia]</span>
                                                    )}
                                                </td>
                                                <td className="px-4 py-3 whitespace-nowrap">
                                                    {rep.status === 'open' && (
                                                        <span className="px-2 py-0.5 bg-[#E56458]/10 border border-[#E56458] text-[#E56458] text-[10px] font-mono uppercase font-bold">
                                                            Menunggu
                                                        </span>
                                                    )}
                                                    {rep.status === 'resolved' && (
                                                        <span className="px-2 py-0.5 bg-[#1F2F1C] border border-[#48733E] text-[#86EFAC] text-[10px] font-mono uppercase">
                                                            Selesai
                                                        </span>
                                                    )}
                                                    {rep.status === 'dismissed' && (
                                                        <span className="px-2 py-0.5 bg-[#1E1E1E] border border-[#333333] text-[#888888] text-[10px] font-mono uppercase">
                                                            Ditolak
                                                        </span>
                                                    )}
                                                    {rep.resolver && (
                                                        <div className="text-[10px] font-mono text-[#666666] mt-1">
                                                            Oleh: {rep.resolver.name}
                                                        </div>
                                                    )}
                                                </td>
                                                <td className="px-4 py-3 text-right whitespace-nowrap">
                                                    {canResolveReports && rep.status === 'open' && (
                                                        <button
                                                            type="button"
                                                            onClick={() => openResolveModal(rep)}
                                                            className="px-3 py-1.5 text-xs font-display tracking-wider uppercase bg-[#BAD306] text-[#111111] hover:bg-[#a6bd05] font-bold rounded-none"
                                                        >
                                                            Tindak Laporan
                                                        </button>
                                                    )}
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>

                        {/* Pagination for Reports */}
                        {reports.total > 0 && (
                            <div className="flex flex-col sm:flex-row items-center justify-between gap-4 pt-2 text-xs font-mono text-[#888888]">
                                <div>
                                    Menampilkan {reports.from || 0} - {reports.to || 0} dari {reports.total} laporan
                                </div>
                                <div className="flex items-center gap-1">
                                    {reports.links.map((link, idx) => (
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
                )}

                {/* COMMENTS TAB CONTENT */}
                {currentTab === 'comments' && comments && (
                    <div className="space-y-4">
                        {/* Comments Search & Filter Form */}
                        <form
                            onSubmit={handleCommentFilterSubmit}
                            className="bg-[#141414] border border-[#262626] p-4 flex flex-col md:flex-row items-stretch md:items-center gap-3"
                        >
                            <div className="flex-1">
                                <input
                                    type="text"
                                    placeholder="Cari konten teks komentar..."
                                    value={commentSearch}
                                    onChange={(e) => setCommentSearch(e.target.value)}
                                    className="w-full bg-[#0D0D0D] border border-[#333333] focus:border-[#BAD306] focus:ring-0 text-xs px-3 py-2 text-[#F8F8F8] font-sans rounded-none placeholder:text-[#666666]"
                                />
                            </div>
                            <div className="w-full md:w-44">
                                <select
                                    value={commentStatus}
                                    onChange={(e) => setCommentStatus(e.target.value)}
                                    className="w-full bg-[#0D0D0D] border border-[#333333] focus:border-[#BAD306] focus:ring-0 text-xs px-3 py-2 text-[#F8F8F8] font-sans rounded-none"
                                >
                                    <option value="all">Semua Status</option>
                                    <option value="published">Tayang (Published)</option>
                                    <option value="hidden">Disembunyikan (Hidden)</option>
                                    <option value="deleted">Dihapus (Deleted)</option>
                                </select>
                            </div>
                            <button
                                type="submit"
                                className="px-4 py-2 text-xs font-display tracking-wider uppercase bg-[#BAD306] text-[#111111] hover:bg-[#a6bd05] font-bold rounded-none"
                            >
                                Filter
                            </button>
                        </form>

                        {/* Bulk Actions Banner if Comments Selected */}
                        {canModerateComments && selectedCommentIds.length > 0 && (
                            <div className="bg-[#1B2012] border border-[#BAD306] p-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 animate-in fade-in duration-100">
                                <div className="text-xs font-mono text-[#BAD306]">
                                    <strong>{selectedCommentIds.length}</strong> komentar terpilih untuk moderasi massal.
                                </div>
                                <div className="flex items-center gap-2">
                                    <button
                                        type="button"
                                        onClick={() => openBulkModal('hide')}
                                        className="px-3 py-1.5 text-xs font-display tracking-wider uppercase bg-[#2A1818] hover:bg-[#3D2020] text-[#E56458] border border-[#E56458]/50 rounded-none"
                                    >
                                        Sembunyikan
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => openBulkModal('unhide')}
                                        className="px-3 py-1.5 text-xs font-display tracking-wider uppercase bg-[#1F2F1C] hover:bg-[#2A4325] text-[#86EFAC] border border-[#48733E] rounded-none"
                                    >
                                        Pulihkan (Tayangkan)
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => openBulkModal('delete')}
                                        className="px-3 py-1.5 text-xs font-display tracking-wider uppercase bg-[#E56458] hover:bg-[#d44f43] text-white border border-[#E56458] rounded-none"
                                    >
                                        Hapus Konten
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setSelectedCommentIds([])}
                                        className="px-2 py-1.5 text-xs font-mono text-[#888888] hover:text-white"
                                    >
                                        Batal
                                    </button>
                                </div>
                            </div>
                        )}

                        {/* Comments Table */}
                        <div className="bg-[#141414] border border-[#262626] overflow-x-auto">
                            <table className="w-full text-left text-xs font-sans">
                                <thead className="bg-[#111111] border-b border-[#262626] text-[10px] font-mono uppercase tracking-wider text-[#888888]">
                                    <tr>
                                        {canModerateComments && (
                                            <th className="px-4 py-3 w-8">
                                                <input
                                                    type="checkbox"
                                                    checked={comments.data.length > 0 && selectedCommentIds.length === comments.data.length}
                                                    onChange={toggleSelectAllComments}
                                                    className="rounded-none bg-[#0D0D0D] border-[#444444] text-[#BAD306] focus:ring-0"
                                                />
                                            </th>
                                        )}
                                        <th className="px-4 py-3">Pengarang & Komik</th>
                                        <th className="px-4 py-3">Konten Komentar</th>
                                        <th className="px-4 py-3">Laporan</th>
                                        <th className="px-4 py-3">Status</th>
                                        <th className="px-4 py-3 text-right">Moderasi Cepat</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-[#202020]">
                                    {comments.data.length === 0 ? (
                                        <tr>
                                            <td colSpan={6} className="px-4 py-10 text-center text-[#888888] font-mono">
                                                Tidak ada komentar yang cocok dengan filter.
                                            </td>
                                        </tr>
                                    ) : (
                                        comments.data.map((c) => {
                                            const isSelected = selectedCommentIds.includes(c.id);
                                            return (
                                                <tr
                                                    key={c.id}
                                                    className={`hover:bg-[#181818] transition-colors ${
                                                        isSelected ? 'bg-[#1A2210]/30' : ''
                                                    }`}
                                                >
                                                    {canModerateComments && (
                                                        <td className="px-4 py-3">
                                                            <input
                                                                type="checkbox"
                                                                checked={isSelected}
                                                                onChange={() => toggleSelectComment(c.id)}
                                                                className="rounded-none bg-[#0D0D0D] border-[#444444] text-[#BAD306] focus:ring-0"
                                                            />
                                                        </td>
                                                    )}
                                                    <td className="px-4 py-3 whitespace-nowrap">
                                                        <div className="font-semibold text-[#F8F8F8]">
                                                            {c.user ? c.user.name : 'Anonim'}
                                                        </div>
                                                        <div className="text-[10px] font-mono text-[#888888]">
                                                            {c.user?.username ? `@${c.user.username}` : ''}
                                                        </div>
                                                        <div className="text-[10px] font-mono text-[#BAD306] mt-1 truncate max-w-xs">
                                                            {c.comic?.title || '-'}
                                                        </div>
                                                    </td>
                                                    <td className="px-4 py-3 max-w-lg">
                                                        <div className="text-[#F8F8F8] text-xs leading-relaxed font-sans">
                                                            {c.body}
                                                        </div>
                                                        {c.moderation_reason && (
                                                            <div className="text-[10px] font-mono text-[#E56458] mt-1">
                                                                Alasan Moderasi: {c.moderation_reason}
                                                            </div>
                                                        )}
                                                        <div className="text-[10px] font-mono text-[#666666] mt-1">
                                                            Diposting: {c.human_time}
                                                        </div>
                                                    </td>
                                                    <td className="px-4 py-3 whitespace-nowrap">
                                                        {c.reports_count > 0 ? (
                                                            <span className="px-2 py-0.5 bg-[#E56458]/10 border border-[#E56458] text-[#E56458] text-[10px] font-mono uppercase font-bold">
                                                                {c.reports_count} Laporan
                                                            </span>
                                                        ) : (
                                                            <span className="text-[10px] font-mono text-[#666666]">0</span>
                                                        )}
                                                    </td>
                                                    <td className="px-4 py-3 whitespace-nowrap">
                                                        {c.status === 'published' && (
                                                            <span className="px-2 py-0.5 bg-[#1F2F1C] border border-[#48733E] text-[#86EFAC] text-[10px] font-mono uppercase">
                                                                Tayang
                                                            </span>
                                                        )}
                                                        {c.status === 'hidden' && (
                                                            <span className="px-2 py-0.5 bg-[#2A1818] border border-[#E56458]/60 text-[#E56458] text-[10px] font-mono uppercase">
                                                                Hidden
                                                            </span>
                                                        )}
                                                        {c.status === 'deleted' && (
                                                            <span className="px-2 py-0.5 bg-[#1C1C1C] border border-[#444444] text-[#888888] text-[10px] font-mono uppercase">
                                                                Dihapus
                                                            </span>
                                                        )}
                                                    </td>
                                                    <td className="px-4 py-3 text-right whitespace-nowrap">
                                                        {canModerateComments && (
                                                            <div className="flex items-center justify-end gap-1.5">
                                                                {c.status === 'published' ? (
                                                                    <button
                                                                        type="button"
                                                                        onClick={() => openBulkModal('hide', c.id)}
                                                                        className="px-2 py-1 text-[11px] font-display tracking-wider uppercase text-[#E56458] bg-[#2A1818] hover:bg-[#3D2020] border border-[#E56458]/50 rounded-none"
                                                                    >
                                                                        Hide
                                                                    </button>
                                                                ) : (
                                                                    <button
                                                                        type="button"
                                                                        onClick={() => openBulkModal('unhide', c.id)}
                                                                        className="px-2 py-1 text-[11px] font-display tracking-wider uppercase text-[#86EFAC] bg-[#1F2F1C] hover:bg-[#2A4325] border border-[#48733E] rounded-none"
                                                                    >
                                                                        Unhide
                                                                    </button>
                                                                )}
                                                                {c.status !== 'deleted' && (
                                                                    <button
                                                                        type="button"
                                                                        onClick={() => openBulkModal('delete', c.id)}
                                                                        className="px-2 py-1 text-[11px] font-display tracking-wider uppercase text-white bg-[#E56458] hover:bg-[#d44f43] border border-[#E56458] rounded-none"
                                                                    >
                                                                        Hapus
                                                                    </button>
                                                                )}
                                                            </div>
                                                        )}
                                                    </td>
                                                </tr>
                                            );
                                        })
                                    )}
                                </tbody>
                            </table>
                        </div>

                        {/* Pagination for Comments */}
                        {comments.total > 0 && (
                            <div className="flex flex-col sm:flex-row items-center justify-between gap-4 pt-2 text-xs font-mono text-[#888888]">
                                <div>
                                    Menampilkan {comments.from || 0} - {comments.to || 0} dari {comments.total} komentar
                                </div>
                                <div className="flex items-center gap-1">
                                    {comments.links.map((link, idx) => (
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
                )}
            </div>

            {/* Resolve Report Modal */}
            {selectedReport && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80">
                    <div className="w-full max-w-lg bg-[#161616] border-2 border-[#333333] shadow-2xl p-6 text-[#F8F8F8] rounded-none">
                        <div className="flex items-center justify-between border-b border-[#262626] pb-3 mb-4">
                            <h3 className="font-display text-lg tracking-wider uppercase text-[#F8F8F8]">
                                Tindak Laporan #{selectedReport.id}
                            </h3>
                            <button
                                type="button"
                                onClick={() => setSelectedReport(null)}
                                disabled={isResolving}
                                className="text-[#888888] hover:text-white px-2 py-1 text-sm font-mono border border-transparent hover:border-[#444444] rounded-none"
                            >
                                [X]
                            </button>
                        </div>

                        {resolveError && (
                            <div className="mb-4 p-3 bg-[#E56458]/10 border border-[#E56458] text-[#E56458] text-xs font-mono">
                                {resolveError}
                            </div>
                        )}

                        <form onSubmit={handleResolveSubmit} className="space-y-4">
                            {/* Report Details Brief */}
                            <div className="bg-[#0D0D0D] border border-[#262626] p-3 text-xs space-y-1">
                                <div className="text-[#BAD306] font-mono uppercase font-bold">
                                    Alasan: {selectedReport.reason_code}
                                </div>
                                {selectedReport.details && (
                                    <div className="text-[#AAAAAA] italic">&ldquo;{selectedReport.details}&rdquo;</div>
                                )}
                                {selectedReport.comment && (
                                    <div className="pt-2 mt-2 border-t border-[#202020] text-[#F8F8F8] font-sans">
                                        Isi Komentar: {selectedReport.comment.body}
                                    </div>
                                )}
                            </div>

                            {/* Status Resolution Choice */}
                            <div>
                                <label className="block text-xs font-mono uppercase tracking-wider text-[#888888] mb-1">
                                    Keputusan Laporan
                                </label>
                                <select
                                    value={resolveStatus}
                                    onChange={(e) => setResolveStatus(e.target.value as 'resolved' | 'dismissed')}
                                    className="w-full bg-[#0D0D0D] border border-[#333333] focus:border-[#BAD306] focus:ring-0 text-[#F8F8F8] px-3 py-2 text-xs font-sans rounded-none"
                                >
                                    <option value="resolved">Selesaikan & Tindak (Laporan Diterima)</option>
                                    <option value="dismissed">Tolak / Abaikan Laporan (Laporan Gugur)</option>
                                </select>
                            </div>

                            {/* Action on Comment if resolved */}
                            {resolveStatus === 'resolved' && (
                                <div>
                                    <label className="block text-xs font-mono uppercase tracking-wider text-[#888888] mb-1">
                                        Tindakan Terhadap Komentar yang Dilaporkan
                                    </label>
                                    <select
                                        value={resolveAction}
                                        onChange={(e) => setResolveAction(e.target.value as 'none' | 'hide' | 'delete')}
                                        className="w-full bg-[#0D0D0D] border border-[#333333] focus:border-[#BAD306] focus:ring-0 text-[#F8F8F8] px-3 py-2 text-xs font-sans rounded-none"
                                    >
                                        <option value="hide">Sembunyikan Komentar (Hidden dari pembaca)</option>
                                        <option value="delete">Hapus Konten Komentar (Redacted / Takedown)</option>
                                        <option value="none">Biarkan Tayang (Hanya Catat Resolusi)</option>
                                    </select>
                                </div>
                            )}

                            {/* Reason / Notes */}
                            <div>
                                <label className="block text-xs font-mono uppercase tracking-wider text-[#888888] mb-1">
                                    Catatan Moderator
                                </label>
                                <textarea
                                    rows={3}
                                    value={resolveReason}
                                    onChange={(e) => setResolveReason(e.target.value)}
                                    placeholder="Alasan keputusan moderasi..."
                                    className="w-full bg-[#0D0D0D] border border-[#333333] focus:border-[#BAD306] focus:ring-0 text-[#F8F8F8] px-3 py-2 text-xs font-sans rounded-none"
                                />
                            </div>

                            {/* Buttons */}
                            <div className="flex items-center justify-end gap-3 pt-3 border-t border-[#262626]">
                                <button
                                    type="button"
                                    onClick={() => setSelectedReport(null)}
                                    disabled={isResolving}
                                    className="px-4 py-2 text-xs font-display tracking-wider uppercase text-[#AAAAAA] hover:text-white bg-[#222222] hover:bg-[#2A2A2A] border border-[#333333] rounded-none"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    disabled={isResolving}
                                    className="px-4 py-2 text-xs font-display tracking-wider uppercase font-bold bg-[#BAD306] text-[#111111] hover:bg-[#a6bd05] border border-[#BAD306] rounded-none"
                                >
                                    {isResolving ? 'Memproses...' : 'Simpan Keputusan'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Bulk Comment Moderation Modal */}
            {bulkModalOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80">
                    <div className="w-full max-w-lg bg-[#161616] border-2 border-[#333333] shadow-2xl p-6 text-[#F8F8F8] rounded-none">
                        <div className="flex items-center justify-between border-b border-[#262626] pb-3 mb-4">
                            <h3 className="font-display text-lg tracking-wider uppercase text-[#F8F8F8]">
                                Moderasi Komentar ({selectedCommentIds.length} Komentar)
                            </h3>
                            <button
                                type="button"
                                onClick={() => setBulkModalOpen(false)}
                                disabled={isBulking}
                                className="text-[#888888] hover:text-white px-2 py-1 text-sm font-mono border border-transparent hover:border-[#444444] rounded-none"
                            >
                                [X]
                            </button>
                        </div>

                        {bulkError && (
                            <div className="mb-4 p-3 bg-[#E56458]/10 border border-[#E56458] text-[#E56458] text-xs font-mono">
                                {bulkError}
                            </div>
                        )}

                        <form onSubmit={handleBulkSubmit} className="space-y-4">
                            <div>
                                <label className="block text-xs font-mono uppercase tracking-wider text-[#888888] mb-1">
                                    Tindakan yang Dipilih
                                </label>
                                <div className="text-sm font-mono font-bold uppercase text-[#BAD306]">
                                    {bulkAction === 'hide' && 'Sembunyikan dari Publik (Hide)'}
                                    {bulkAction === 'unhide' && 'Pulihkan ke Publik (Unhide / Publish)'}
                                    {bulkAction === 'delete' && 'Hapus Konten Komentar (Delete / Takedown)'}
                                </div>
                            </div>

                            <div>
                                <label className="block text-xs font-mono uppercase tracking-wider text-[#888888] mb-1">
                                    Alasan Moderasi (Wajib, min 3 karakter)
                                </label>
                                <textarea
                                    required
                                    rows={3}
                                    value={bulkReason}
                                    onChange={(e) => setBulkReason(e.target.value)}
                                    placeholder="Jelaskan alasan tindakan moderasi..."
                                    className="w-full bg-[#0D0D0D] border border-[#333333] focus:border-[#BAD306] focus:ring-0 text-[#F8F8F8] px-3 py-2 text-xs font-sans rounded-none"
                                />
                            </div>

                            {/* Buttons */}
                            <div className="flex items-center justify-end gap-3 pt-3 border-t border-[#262626]">
                                <button
                                    type="button"
                                    onClick={() => setBulkModalOpen(false)}
                                    disabled={isBulking}
                                    className="px-4 py-2 text-xs font-display tracking-wider uppercase text-[#AAAAAA] hover:text-white bg-[#222222] hover:bg-[#2A2A2A] border border-[#333333] rounded-none"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    disabled={isBulking || bulkReason.trim().length < 3}
                                    className={`px-4 py-2 text-xs font-display tracking-wider uppercase font-bold rounded-none ${
                                        bulkAction === 'delete'
                                            ? 'bg-[#E56458] text-white hover:bg-[#d44f43] border border-[#E56458]'
                                            : 'bg-[#BAD306] text-[#111111] hover:bg-[#a6bd05] border border-[#BAD306]'
                                    }`}
                                >
                                    {isBulking ? 'Memproses...' : 'Terapkan Tindakan'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}
