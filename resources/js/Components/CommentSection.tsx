import React, { useState, useEffect, useCallback } from 'react';
import { Link } from '@inertiajs/react';
import { CommentItem, User } from '@/types';

interface CommentSectionProps {
    comicSlug: string;
    chapterKey: string;
    currentUser: User | null;
}

export default function CommentSection({
    comicSlug,
    chapterKey,
    currentUser,
}: CommentSectionProps) {
    const [comments, setComments] = useState<CommentItem[]>([]);
    const [totalCount, setTotalCount] = useState<number>(0);
    const [isLoading, setIsLoading] = useState<boolean>(true);
    const [fetchError, setFetchError] = useState<string | null>(null);

    // Root composer state
    const [newBody, setNewBody] = useState<string>('');
    const [isSubmittingNew, setIsSubmittingNew] = useState<boolean>(false);

    // Reply composer state
    const [replyingToId, setReplyingToId] = useState<number | null>(null);
    const [replyBody, setReplyBody] = useState<string>('');
    const [isSubmittingReply, setIsSubmittingReply] = useState<boolean>(false);

    // Edit composer state
    const [editingId, setEditingId] = useState<number | null>(null);
    const [editBody, setEditBody] = useState<string>('');
    const [isSubmittingEdit, setIsSubmittingEdit] = useState<boolean>(false);

    // Report modal state
    const [reportingComment, setReportingComment] = useState<CommentItem | null>(null);
    const [reportReason, setReportReason] = useState<string>('spam');
    const [reportDetails, setReportDetails] = useState<string>('');
    const [isSubmittingReport, setIsSubmittingReport] = useState<boolean>(false);

    // Moderation modal state
    const [moderatingComment, setModeratingComment] = useState<CommentItem | null>(null);
    const [moderateAction, setModerateAction] = useState<'hide' | 'unhide' | 'delete'>('hide');
    const [moderateReason, setModerateReason] = useState<string>('');
    const [isSubmittingModerate, setIsSubmittingModerate] = useState<boolean>(false);

    // Status feedback toast/banner
    const [banner, setBanner] = useState<{ type: 'success' | 'error'; text: string } | null>(null);

    const getHeaders = useCallback(() => {
        const token = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
        return {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': token,
        };
    }, []);

    // Load comments
    const loadComments = useCallback(async () => {
        setIsLoading(true);
        setFetchError(null);
        try {
            const res = await fetch(
                `/comments?comic_slug=${encodeURIComponent(comicSlug)}&chapter_key=${encodeURIComponent(chapterKey)}`,
                {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                }
            );

            if (!res.ok) {
                throw new Error('Gagal memuat komentar.');
            }

            const data = await res.json();
            setComments(data.data || []);
            setTotalCount(data.total || 0);
        } catch (err: unknown) {
            setFetchError(err instanceof Error ? err.message : 'Terjadi gangguan saat memuat komentar.');
        } finally {
            setIsLoading(false);
        }
    }, [comicSlug, chapterKey]);

    useEffect(() => {
        loadComments();
    }, [loadComments]);

    const showBanner = (type: 'success' | 'error', text: string) => {
        setBanner({ type, text });
        setTimeout(() => {
            setBanner((current) => (current?.text === text ? null : current));
        }, 5000);
    };

    // Submit new root comment
    const handleSubmitNew = async (e: React.FormEvent) => {
        e.preventDefault();
        const trimmed = newBody.trim();
        if (trimmed.length < 2) {
            showBanner('error', 'Komentar minimal 2 karakter.');
            return;
        }

        setIsSubmittingNew(true);
        try {
            const res = await fetch('/comments', {
                method: 'POST',
                headers: getHeaders(),
                body: JSON.stringify({
                    comic_slug: comicSlug,
                    chapter_key: chapterKey,
                    body: trimmed,
                }),
            });

            const data = await res.json();

            if (!res.ok) {
                if (res.status === 429) {
                    showBanner('error', 'Terlalu banyak permintaan komentar. Silakan tunggu beberapa saat.');
                } else {
                    showBanner('error', data.message || 'Gagal mengirim komentar.');
                }
                return;
            }

            setNewBody('');
            showBanner('success', data.message || 'Komentar berhasil dikirim.');
            loadComments();
        } catch {
            showBanner('error', 'Gagal terhubung ke server.');
        } finally {
            setIsSubmittingNew(false);
        }
    };

    // Submit reply (single-level)
    const handleSubmitReply = async (parentId: number) => {
        const trimmed = replyBody.trim();
        if (trimmed.length < 2) {
            showBanner('error', 'Balasan minimal 2 karakter.');
            return;
        }

        setIsSubmittingReply(true);
        try {
            const res = await fetch('/comments', {
                method: 'POST',
                headers: getHeaders(),
                body: JSON.stringify({
                    comic_slug: comicSlug,
                    chapter_key: chapterKey,
                    parent_id: parentId,
                    body: trimmed,
                }),
            });

            const data = await res.json();

            if (!res.ok) {
                if (res.status === 429) {
                    showBanner('error', 'Terlalu banyak permintaan komentar. Silakan tunggu beberapa saat.');
                } else {
                    showBanner('error', data.message || 'Gagal mengirim balasan.');
                }
                return;
            }

            setReplyBody('');
            setReplyingToId(null);
            showBanner('success', data.message || 'Balasan berhasil dikirim.');
            loadComments();
        } catch {
            showBanner('error', 'Gagal terhubung ke server.');
        } finally {
            setIsSubmittingReply(false);
        }
    };

    // Submit edit
    const handleSubmitEdit = async (commentId: number) => {
        const trimmed = editBody.trim();
        if (trimmed.length < 2) {
            showBanner('error', 'Komentar minimal 2 karakter.');
            return;
        }

        setIsSubmittingEdit(true);
        try {
            const res = await fetch(`/comments/${commentId}`, {
                method: 'PATCH',
                headers: getHeaders(),
                body: JSON.stringify({ body: trimmed }),
            });

            const data = await res.json();

            if (!res.ok) {
                showBanner('error', data.message || 'Gagal memperbarui komentar.');
                return;
            }

            setEditingId(null);
            setEditBody('');
            showBanner('success', data.message || 'Komentar berhasil diperbarui.');
            loadComments();
        } catch {
            showBanner('error', 'Gagal terhubung ke server.');
        } finally {
            setIsSubmittingEdit(false);
        }
    };

    // Delete comment
    const handleDelete = async (comment: CommentItem) => {
        if (!window.confirm('Apakah Anda yakin ingin menghapus komentar ini?')) {
            return;
        }

        try {
            const res = await fetch(`/comments/${comment.id}`, {
                method: 'DELETE',
                headers: getHeaders(),
            });

            const data = await res.json();

            if (!res.ok) {
                showBanner('error', data.message || 'Gagal menghapus komentar.');
                return;
            }

            showBanner('success', data.message || 'Komentar berhasil dihapus.');
            loadComments();
        } catch {
            showBanner('error', 'Gagal terhubung ke server.');
        }
    };

    // Toggle like
    const handleToggleLike = async (comment: CommentItem) => {
        if (!currentUser) {
            showBanner('error', 'Silakan masuk akun terlebih dahulu untuk memberikan suka.');
            return;
        }

        try {
            const res = await fetch(`/comments/${comment.id}/like`, {
                method: 'POST',
                headers: getHeaders(),
            });

            const data = await res.json();

            if (!res.ok) {
                showBanner('error', data.message || 'Gagal memperbarui reaksi.');
                return;
            }

            // Optimistic update local comment tree
            const updateLikeInList = (list: CommentItem[]): CommentItem[] => {
                return list.map((item) => {
                    if (item.id === comment.id) {
                        return {
                            ...item,
                            likes_count: data.likes_count,
                            user_has_liked: data.has_liked,
                        };
                    }
                    if (item.replies && item.replies.length > 0) {
                        return {
                            ...item,
                            replies: updateLikeInList(item.replies),
                        };
                    }
                    return item;
                });
            };

            setComments(updateLikeInList(comments));
        } catch {
            showBanner('error', 'Gagal memproses suka.');
        }
    };

    // Submit report
    const handleSubmitReport = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!reportingComment) return;

        setIsSubmittingReport(true);
        try {
            const res = await fetch(`/comments/${reportingComment.id}/report`, {
                method: 'POST',
                headers: getHeaders(),
                body: JSON.stringify({
                    reason_code: reportReason,
                    details: reportDetails.trim() || null,
                }),
            });

            const data = await res.json();

            if (!res.ok) {
                if (res.status === 429) {
                    showBanner('error', 'Terlalu banyak laporan. Silakan coba kembali nanti.');
                } else {
                    showBanner('error', data.message || 'Gagal mengirim laporan.');
                }
                return;
            }

            setReportingComment(null);
            setReportDetails('');
            setReportReason('spam');
            showBanner('success', data.message || 'Laporan berhasil dikirim.');
        } catch {
            showBanner('error', 'Gagal terhubung ke server.');
        } finally {
            setIsSubmittingReport(false);
        }
    };

    // Submit moderation
    const handleSubmitModerate = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!moderatingComment) return;

        if (moderateReason.trim().length < 3) {
            showBanner('error', 'Alasan moderasi wajib diisi minimal 3 karakter.');
            return;
        }

        setIsSubmittingModerate(true);
        try {
            const res = await fetch(`/comments/${moderatingComment.id}/moderate`, {
                method: 'POST',
                headers: getHeaders(),
                body: JSON.stringify({
                    action: moderateAction,
                    reason: moderateReason.trim(),
                }),
            });

            const data = await res.json();

            if (!res.ok) {
                showBanner('error', data.message || 'Gagal memproses moderasi.');
                return;
            }

            setModeratingComment(null);
            setModerateReason('');
            showBanner('success', data.message || 'Komentar berhasil dimoderasi.');
            loadComments();
        } catch {
            showBanner('error', 'Gagal terhubung ke server.');
        } finally {
            setIsSubmittingModerate(false);
        }
    };

    return (
        <section
            aria-label="Diskusi Komunitas Chapter"
            className="mt-8 mb-16 mx-3 sm:mx-0 p-4 sm:p-6 bg-[#161616] border-2 border-[#222222] rounded-none text-left"
        >
            {/* Header */}
            <div className="flex items-center justify-between pb-4 border-b-2 border-[#222222] mb-6">
                <div>
                    <h2 className="font-display text-xl sm:text-2xl uppercase tracking-wider text-[#F8F8F8]">
                        Diskusi Chapter
                    </h2>
                    <p className="text-xs text-[#AAAAAA] mt-0.5">
                        Berikan tanggapan, teori, atau diskusi seputar chapter ini.
                    </p>
                </div>
                <div className="flex items-center gap-1.5 px-3 py-1 bg-[#111111] border border-[#333333]">
                    <span className="font-mono text-xs text-[#BAD306] font-bold">{totalCount}</span>
                    <span className="font-display text-xs uppercase tracking-wider text-[#AAAAAA]">Komentar</span>
                </div>
            </div>

            {/* Notification Banner */}
            {banner && (
                <div
                    role="alert"
                    className={`mb-6 p-3 text-xs sm:text-sm font-medium border-l-4 ${
                        banner.type === 'success'
                            ? 'bg-[#111111] border-[#72BC8F] text-[#72BC8F]'
                            : 'bg-[#111111] border-[#E56458] text-[#E56458]'
                    }`}
                >
                    {banner.text}
                </div>
            )}

            {/* Root Comment Composer */}
            {currentUser ? (
                <form onSubmit={handleSubmitNew} className="mb-8">
                    <div className="relative">
                        <label htmlFor="comment-body" className="sr-only">
                            Tulis komentar
                        </label>
                        <textarea
                            id="comment-body"
                            value={newBody}
                            onChange={(e) => setNewBody(e.target.value)}
                            maxLength={1000}
                            rows={3}
                            placeholder="Tulis komentar Anda... (maksimal 1000 karakter)"
                            className="w-full p-3 bg-[#111111] text-[#F8F8F8] border border-[#333333] focus:border-[#BAD306] focus:outline-none text-sm placeholder-[#777777] rounded-none resize-y"
                        />
                    </div>
                    <div className="flex items-center justify-between mt-2">
                        <span className="font-mono text-xs text-[#777777]">
                            {newBody.length}/1000 karakter
                        </span>
                        <button
                            type="submit"
                            disabled={isSubmittingNew || newBody.trim().length < 2}
                            className="px-6 py-2 font-display text-xs sm:text-sm uppercase tracking-wider bg-[#BAD306] text-[#111111] font-bold border-2 border-[#BAD306] hover:bg-[#E0FF00] hover:border-[#E0FF00] transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#BAD306] disabled:opacity-50 disabled:cursor-not-allowed rounded-none"
                        >
                            {isSubmittingNew ? 'Mengirim...' : 'Kirim Komentar'}
                        </button>
                    </div>
                </form>
            ) : (
                <div className="p-4 bg-[#111111] border border-[#333333] text-center mb-8">
                    <p className="text-xs sm:text-sm text-[#AAAAAA] mb-3">
                        Masuk ke akun Komikini Anda untuk ikut berdiskusi dan memberikan suka.
                    </p>
                    <Link
                        href="/login"
                        className="inline-block px-5 py-2 font-display text-xs uppercase tracking-wider bg-[#BAD306] text-[#111111] font-bold border border-[#BAD306] hover:bg-[#E0FF00] rounded-none"
                    >
                        Masuk Akun
                    </Link>
                </div>
            )}

            {/* Error & Loading States */}
            {isLoading && (
                <div className="py-8 text-center text-[#AAAAAA] text-sm font-mono">
                    Memuat komentar...
                </div>
            )}

            {fetchError && (
                <div className="p-4 bg-[#111111] border border-[#E56458] text-center my-4">
                    <p className="text-xs sm:text-sm text-[#E56458] mb-2">{fetchError}</p>
                    <button
                        type="button"
                        onClick={loadComments}
                        className="px-4 py-1.5 font-display text-xs uppercase tracking-wider bg-[#222222] text-[#F8F8F8] border border-[#444444] hover:border-[#BAD306] rounded-none"
                    >
                        Coba Lagi
                    </button>
                </div>
            )}

            {/* Comments List */}
            {!isLoading && !fetchError && comments.length === 0 && (
                <div className="py-12 text-center border border-dashed border-[#333333]">
                    <p className="text-xs sm:text-sm text-[#777777]">
                        Belum ada komentar untuk chapter ini. Jadilah yang pertama berkomentar.
                    </p>
                </div>
            )}

            {!isLoading && comments.length > 0 && (
                <div className="space-y-6">
                    {comments.map((comment) => (
                        <div
                            key={comment.id}
                            className="border-b border-[#222222] pb-6 last:border-b-0 last:pb-0"
                        >
                            {/* Comment Item Component */}
                            <CommentCard
                                comment={comment}
                                isReply={false}
                                onReply={() => {
                                    setReplyingToId(replyingToId === comment.id ? null : comment.id);
                                    setReplyBody('');
                                }}
                                onEdit={() => {
                                    setEditingId(comment.id);
                                    setEditBody(comment.body);
                                }}
                                onDelete={() => handleDelete(comment)}
                                onLike={() => handleToggleLike(comment)}
                                onReport={() => {
                                    setReportingComment(comment);
                                    setReportReason('spam');
                                    setReportDetails('');
                                }}
                                onModerate={() => {
                                    setModeratingComment(comment);
                                    setModerateAction(comment.status === 'hidden' ? 'unhide' : 'hide');
                                    setModerateReason('');
                                }}
                            />

                            {/* Inline Edit Form for Root Comment */}
                            {editingId === comment.id && (
                                <div className="mt-3 p-3 bg-[#111111] border border-[#444444]">
                                    <textarea
                                        value={editBody}
                                        onChange={(e) => setEditBody(e.target.value)}
                                        maxLength={1000}
                                        rows={3}
                                        className="w-full p-2 bg-[#161616] text-[#F8F8F8] border border-[#333333] focus:border-[#BAD306] focus:outline-none text-sm rounded-none resize-y"
                                    />
                                    <div className="flex items-center justify-between mt-2">
                                        <span className="font-mono text-xs text-[#777777]">
                                            {editBody.length}/1000
                                        </span>
                                        <div className="flex gap-2">
                                            <button
                                                type="button"
                                                onClick={() => setEditingId(null)}
                                                className="px-3 py-1 font-display text-xs uppercase tracking-wider text-[#AAAAAA] hover:text-[#F8F8F8] border border-[#333333] rounded-none"
                                            >
                                                Batal
                                            </button>
                                            <button
                                                type="button"
                                                onClick={() => handleSubmitEdit(comment.id)}
                                                disabled={isSubmittingEdit || editBody.trim().length < 2}
                                                className="px-4 py-1 font-display text-xs uppercase tracking-wider bg-[#BAD306] text-[#111111] font-bold border border-[#BAD306] hover:bg-[#E0FF00] rounded-none disabled:opacity-50"
                                            >
                                                {isSubmittingEdit ? 'Menyimpan...' : 'Simpan'}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* Inline Reply Composer for Root Comment */}
                            {replyingToId === comment.id && (
                                <div className="mt-3 ml-4 sm:ml-8 p-3 bg-[#111111] border-l-2 border-[#BAD306]">
                                    <p className="text-xs text-[#AAAAAA] mb-2 font-medium">
                                        Membalas komentar <span className="text-[#BAD306]">@{comment.user.username}</span>:
                                    </p>
                                    <textarea
                                        value={replyBody}
                                        onChange={(e) => setReplyBody(e.target.value)}
                                        maxLength={1000}
                                        rows={2}
                                        placeholder="Tulis balasan Anda... (maksimal 1000 karakter)"
                                        className="w-full p-2 bg-[#161616] text-[#F8F8F8] border border-[#333333] focus:border-[#BAD306] focus:outline-none text-sm rounded-none resize-y"
                                    />
                                    <div className="flex items-center justify-between mt-2">
                                        <span className="font-mono text-xs text-[#777777]">
                                            {replyBody.length}/1000
                                        </span>
                                        <div className="flex gap-2">
                                            <button
                                                type="button"
                                                onClick={() => setReplyingToId(null)}
                                                className="px-3 py-1 font-display text-xs uppercase tracking-wider text-[#AAAAAA] hover:text-[#F8F8F8] border border-[#333333] rounded-none"
                                            >
                                                Batal
                                            </button>
                                            <button
                                                type="button"
                                                onClick={() => handleSubmitReply(comment.id)}
                                                disabled={isSubmittingReply || replyBody.trim().length < 2}
                                                className="px-4 py-1 font-display text-xs uppercase tracking-wider bg-[#BAD306] text-[#111111] font-bold border border-[#BAD306] hover:bg-[#E0FF00] rounded-none disabled:opacity-50"
                                            >
                                                {isSubmittingReply ? 'Mengirim...' : 'Kirim Balasan'}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* Replies List (Single-level only: no further nesting allowed) */}
                            {comment.replies && comment.replies.length > 0 && (
                                <div className="mt-4 ml-4 sm:ml-8 pl-3 sm:pl-4 border-l-2 border-[#262626] space-y-4">
                                    {comment.replies.map((reply) => (
                                        <div key={reply.id}>
                                            <CommentCard
                                                comment={reply}
                                                isReply={true}
                                                onEdit={() => {
                                                    setEditingId(reply.id);
                                                    setEditBody(reply.body);
                                                }}
                                                onDelete={() => handleDelete(reply)}
                                                onLike={() => handleToggleLike(reply)}
                                                onReport={() => {
                                                    setReportingComment(reply);
                                                    setReportReason('spam');
                                                    setReportDetails('');
                                                }}
                                                onModerate={() => {
                                                    setModeratingComment(reply);
                                                    setModerateAction(reply.status === 'hidden' ? 'unhide' : 'hide');
                                                    setModerateReason('');
                                                }}
                                            />

                                            {/* Inline Edit Form for Reply */}
                                            {editingId === reply.id && (
                                                <div className="mt-2 p-3 bg-[#111111] border border-[#444444]">
                                                    <textarea
                                                        value={editBody}
                                                        onChange={(e) => setEditBody(e.target.value)}
                                                        maxLength={1000}
                                                        rows={2}
                                                        className="w-full p-2 bg-[#161616] text-[#F8F8F8] border border-[#333333] focus:border-[#BAD306] focus:outline-none text-sm rounded-none resize-y"
                                                    />
                                                    <div className="flex items-center justify-between mt-2">
                                                        <span className="font-mono text-xs text-[#777777]">
                                                            {editBody.length}/1000
                                                        </span>
                                                        <div className="flex gap-2">
                                                            <button
                                                                type="button"
                                                                onClick={() => setEditingId(null)}
                                                                className="px-3 py-1 font-display text-xs uppercase tracking-wider text-[#AAAAAA] hover:text-[#F8F8F8] border border-[#333333] rounded-none"
                                                            >
                                                                Batal
                                                            </button>
                                                            <button
                                                                type="button"
                                                                onClick={() => handleSubmitEdit(reply.id)}
                                                                disabled={isSubmittingEdit || editBody.trim().length < 2}
                                                                className="px-4 py-1 font-display text-xs uppercase tracking-wider bg-[#BAD306] text-[#111111] font-bold border border-[#BAD306] hover:bg-[#E0FF00] rounded-none disabled:opacity-50"
                                                            >
                                                                {isSubmittingEdit ? 'Menyimpan...' : 'Simpan'}
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    ))}
                </div>
            )}

            {/* Accessible Report Modal */}
            {reportingComment && (
                <div
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="report-modal-title"
                    className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-grayscale"
                    onClick={(e) => {
                        if (e.target === e.currentTarget) setReportingComment(null);
                    }}
                >
                    <div className="w-full max-w-md bg-[#161616] border-2 border-[#444444] p-5 sm:p-6 rounded-none text-left">
                        <div className="flex items-center justify-between pb-3 border-b border-[#333333] mb-4">
                            <h3 id="report-modal-title" className="font-display text-lg uppercase tracking-wider text-[#F8F8F8]">
                                Laporkan Komentar
                            </h3>
                            <button
                                type="button"
                                onClick={() => setReportingComment(null)}
                                className="text-[#AAAAAA] hover:text-[#F8F8F8] p-1"
                                aria-label="Tutup dialog"
                            >
                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <form onSubmit={handleSubmitReport} className="space-y-4">
                            <div>
                                <label htmlFor="report-reason" className="block text-xs font-display uppercase tracking-wider text-[#AAAAAA] mb-1">
                                    Alasan Pelaporan
                                </label>
                                <select
                                    id="report-reason"
                                    value={reportReason}
                                    onChange={(e) => setReportReason(e.target.value)}
                                    className="w-full p-2 bg-[#111111] text-[#F8F8F8] border border-[#333333] focus:border-[#BAD306] focus:outline-none text-sm rounded-none"
                                >
                                    <option value="spam">Spam atau Iklan Tidak Pantas</option>
                                    <option value="harassment">Pelecehan atau Ujaran Kebencian</option>
                                    <option value="spoiler">Spoiler Cerita Tanpa Peringatan</option>
                                    <option value="inappropriate">Konten Asusila atau Tidak Pantas</option>
                                    <option value="other">Alasan Lainnya</option>
                                </select>
                            </div>

                            <div>
                                <label htmlFor="report-details" className="block text-xs font-display uppercase tracking-wider text-[#AAAAAA] mb-1">
                                    Keterangan Tambahan (Opsional)
                                </label>
                                <textarea
                                    id="report-details"
                                    value={reportDetails}
                                    onChange={(e) => setReportDetails(e.target.value)}
                                    maxLength={500}
                                    rows={3}
                                    placeholder="Jelaskan detail pelanggaran untuk memudahkan peninjauan..."
                                    className="w-full p-2 bg-[#111111] text-[#F8F8F8] border border-[#333333] focus:border-[#BAD306] focus:outline-none text-sm rounded-none resize-none"
                                />
                                <span className="font-mono text-[11px] text-[#777777] block text-right mt-1">
                                    {reportDetails.length}/500
                                </span>
                            </div>

                            <div className="flex justify-end gap-2 pt-2 border-t border-[#333333]">
                                <button
                                    type="button"
                                    onClick={() => setReportingComment(null)}
                                    className="px-4 py-2 font-display text-xs uppercase tracking-wider text-[#AAAAAA] hover:text-[#F8F8F8] border border-[#333333] rounded-none"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    disabled={isSubmittingReport}
                                    className="px-5 py-2 font-display text-xs uppercase tracking-wider bg-[#E56458] text-[#FFFFFF] font-bold border border-[#E56458] hover:bg-[#c94d42] rounded-none disabled:opacity-50"
                                >
                                    {isSubmittingReport ? 'Mengirim...' : 'Kirim Laporan'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Accessible Moderation Modal */}
            {moderatingComment && (
                <div
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="moderate-modal-title"
                    className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-grayscale"
                    onClick={(e) => {
                        if (e.target === e.currentTarget) setModeratingComment(null);
                    }}
                >
                    <div className="w-full max-w-md bg-[#161616] border-2 border-[#444444] p-5 sm:p-6 rounded-none text-left">
                        <div className="flex items-center justify-between pb-3 border-b border-[#333333] mb-4">
                            <h3 id="moderate-modal-title" className="font-display text-lg uppercase tracking-wider text-[#F8F8F8]">
                                Tindakan Moderasi Komentar
                            </h3>
                            <button
                                type="button"
                                onClick={() => setModeratingComment(null)}
                                className="text-[#AAAAAA] hover:text-[#F8F8F8] p-1"
                                aria-label="Tutup dialog"
                            >
                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <form onSubmit={handleSubmitModerate} className="space-y-4">
                            <div>
                                <label className="block text-xs font-display uppercase tracking-wider text-[#AAAAAA] mb-2">
                                    Pilih Tindakan
                                </label>
                                <div className="space-y-2">
                                    <label className="flex items-center gap-2 p-2 bg-[#111111] border border-[#333333] cursor-pointer">
                                        <input
                                            type="radio"
                                            name="modAction"
                                            value="hide"
                                            checked={moderateAction === 'hide'}
                                            onChange={() => setModerateAction('hide')}
                                            className="accent-[#BAD306]"
                                        />
                                        <span className="text-xs text-[#F8F8F8]">Sembunyikan Komentar (Hidden)</span>
                                    </label>
                                    <label className="flex items-center gap-2 p-2 bg-[#111111] border border-[#333333] cursor-pointer">
                                        <input
                                            type="radio"
                                            name="modAction"
                                            value="unhide"
                                            checked={moderateAction === 'unhide'}
                                            onChange={() => setModerateAction('unhide')}
                                            className="accent-[#BAD306]"
                                        />
                                        <span className="text-xs text-[#F8F8F8]">Publikasikan Kembali (Unhide)</span>
                                    </label>
                                    <label className="flex items-center gap-2 p-2 bg-[#111111] border border-[#333333] cursor-pointer">
                                        <input
                                            type="radio"
                                            name="modAction"
                                            value="delete"
                                            checked={moderateAction === 'delete'}
                                            onChange={() => setModerateAction('delete')}
                                            className="accent-[#E56458]"
                                        />
                                        <span className="text-xs text-[#E56458]">Hapus Komentar Permanen (Deleted Tombstone)</span>
                                    </label>
                                </div>
                            </div>

                            <div>
                                <label htmlFor="mod-reason" className="block text-xs font-display uppercase tracking-wider text-[#AAAAAA] mb-1">
                                    Alasan Moderasi (Wajib, dicatat di log audit)
                                </label>
                                <textarea
                                    id="mod-reason"
                                    value={moderateReason}
                                    onChange={(e) => setModerateReason(e.target.value)}
                                    maxLength={500}
                                    rows={2}
                                    required
                                    placeholder="Contoh: Mengandung pelecehan dan ujaran kebencian..."
                                    className="w-full p-2 bg-[#111111] text-[#F8F8F8] border border-[#333333] focus:border-[#BAD306] focus:outline-none text-sm rounded-none resize-none"
                                />
                            </div>

                            <div className="flex justify-end gap-2 pt-2 border-t border-[#333333]">
                                <button
                                    type="button"
                                    onClick={() => setModeratingComment(null)}
                                    className="px-4 py-2 font-display text-xs uppercase tracking-wider text-[#AAAAAA] hover:text-[#F8F8F8] border border-[#333333] rounded-none"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    disabled={isSubmittingModerate || moderateReason.trim().length < 3}
                                    className="px-5 py-2 font-display text-xs uppercase tracking-wider bg-[#BAD306] text-[#111111] font-bold border border-[#BAD306] hover:bg-[#E0FF00] rounded-none disabled:opacity-50"
                                >
                                    {isSubmittingModerate ? 'Memproses...' : 'Terapkan'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </section>
    );
}

interface CommentCardProps {
    comment: CommentItem;
    isReply: boolean;
    onReply?: () => void;
    onEdit: () => void;
    onDelete: () => void;
    onLike: () => void;
    onReport: () => void;
    onModerate: () => void;
}

function CommentCard({
    comment,
    isReply,
    onReply,
    onEdit,
    onDelete,
    onLike,
    onReport,
    onModerate,
}: CommentCardProps) {
    const isDeleted = comment.status === 'deleted';
    const isHidden = comment.status === 'hidden';

    return (
        <div className="group">
            {/* Author info & Metadata */}
            <div className="flex items-center justify-between gap-2 mb-1">
                <div className="flex items-center gap-2 min-w-0">
                    <div className="w-6 h-6 bg-[#222222] border border-[#333333] flex items-center justify-center font-display text-xs text-[#BAD306] uppercase shrink-0">
                        {comment.user.name.charAt(0)}
                    </div>
                    <span className="font-display text-xs sm:text-sm tracking-wider uppercase text-[#F8F8F8] truncate">
                        {comment.user.name}
                    </span>
                    <span className="text-xs text-[#777777] hidden sm:inline">
                        @{comment.user.username}
                    </span>
                    <span className="text-[#333333]">•</span>
                    <span className="text-[11px] text-[#777777] shrink-0 font-mono">
                        {comment.human_time}
                    </span>
                    {comment.edited_at && !isDeleted && (
                        <span className="text-[11px] text-[#777777] italic shrink-0">
                            (diedit)
                        </span>
                    )}
                </div>

                {/* Status Badges for Moderated/Deleted Comments */}
                {isHidden && (
                    <span className="px-1.5 py-0.5 font-display text-[10px] uppercase tracking-wider bg-[#332211] text-[#E5A858] border border-[#553311]">
                        Disembunyikan
                    </span>
                )}
                {isDeleted && (
                    <span className="px-1.5 py-0.5 font-display text-[10px] uppercase tracking-wider bg-[#221111] text-[#E56458] border border-[#441111]">
                        Terhapus
                    </span>
                )}
            </div>

            {/* Comment Body - STRICT ANTI-XSS: pure plain text interpolation, NO dangerouslySetInnerHTML */}
            <div className="my-2">
                {isDeleted ? (
                    <p className="text-xs sm:text-sm text-[#777777] italic select-none">
                        [Komentar ini telah dihapus]
                    </p>
                ) : isHidden ? (
                    <div className="p-2 bg-[#1c1712] border border-[#3a2c20]">
                        <p className="text-xs text-[#E5A858] italic mb-1">
                            Komentar ini telah disembunyikan oleh moderator.
                        </p>
                        {comment.moderation_reason && (
                            <p className="text-[11px] text-[#AAAAAA]">
                                Alasan: {comment.moderation_reason}
                            </p>
                        )}
                    </div>
                ) : (
                    <p className="text-xs sm:text-sm text-[#F8F8F8] whitespace-pre-wrap break-words font-sans">
                        {comment.body}
                    </p>
                )}
            </div>

            {/* Actions Bar */}
            <div className="flex items-center gap-3 sm:gap-4 mt-2 pt-1 text-xs">
                {/* Like Button */}
                {!isDeleted && (
                    <button
                        type="button"
                        onClick={onLike}
                        aria-label={`Sukai komentar (${comment.likes_count} suka)`}
                        className={`flex items-center gap-1.5 px-2 py-1 transition-colors border ${
                            comment.user_has_liked
                                ? 'bg-[#18200a] text-[#BAD306] border-[#BAD306]'
                                : 'bg-[#111111] text-[#AAAAAA] border-[#333333] hover:text-[#F8F8F8] hover:border-[#444444]'
                        } rounded-none`}
                    >
                        <svg
                            className={`w-3.5 h-3.5 ${comment.user_has_liked ? 'fill-[#BAD306]' : 'fill-none'}`}
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth="2"
                                d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017a2 2 0 01-1.414-.586l-4.293-4.293A2 2 0 015 14.707V9a2 2 0 012-2h4.586a1 1 0 01.707.293l1.707 1.707z"
                            />
                        </svg>
                        <span className="font-mono text-xs font-semibold">{comment.likes_count}</span>
                    </button>
                )}

                {/* Reply Button (Root comments only!) */}
                {!isReply && !isDeleted && onReply && (
                    <button
                        type="button"
                        onClick={onReply}
                        className="font-display text-xs uppercase tracking-wider text-[#AAAAAA] hover:text-[#BAD306] transition-colors"
                    >
                        Balas
                    </button>
                )}

                {/* Edit Button */}
                {comment.can_edit && !isDeleted && (
                    <button
                        type="button"
                        onClick={onEdit}
                        className="font-display text-xs uppercase tracking-wider text-[#AAAAAA] hover:text-[#BAD306] transition-colors"
                    >
                        Edit
                    </button>
                )}

                {/* Delete Button */}
                {comment.can_delete && !isDeleted && (
                    <button
                        type="button"
                        onClick={onDelete}
                        className="font-display text-xs uppercase tracking-wider text-[#AAAAAA] hover:text-[#E56458] transition-colors"
                    >
                        Hapus
                    </button>
                )}

                {/* Report Button */}
                {!isDeleted && (
                    <button
                        type="button"
                        onClick={onReport}
                        className="font-display text-xs uppercase tracking-wider text-[#777777] hover:text-[#E56458] transition-colors"
                    >
                        Laporkan
                    </button>
                )}

                {/* Moderate Button */}
                {comment.can_moderate && (
                    <button
                        type="button"
                        onClick={onModerate}
                        className="font-display text-xs uppercase tracking-wider text-[#BAD306] hover:underline transition-colors ml-auto"
                    >
                        [Moderasi]
                    </button>
                )}
            </div>
        </div>
    );
}
