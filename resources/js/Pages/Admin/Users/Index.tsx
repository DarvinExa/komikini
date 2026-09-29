import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

interface UserItem {
    id: number;
    public_id: string;
    name: string;
    username: string | null;
    email: string;
    status: string;
    suspended_until: string | null;
    is_suspended: boolean;
    roles: string[];
    created_at: string | null;
    can_suspend: boolean;
    can_delete: boolean;
    can_assign_roles: boolean;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedUsers {
    data: UserItem[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

interface UsersIndexProps {
    users: PaginatedUsers;
    filters: {
        search: string;
        status: string;
        role: string;
    };
    availableRoles: string[];
}

export default function UsersIndex({ users, filters, availableRoles }: UsersIndexProps) {
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || '');
    const [role, setRole] = useState(filters.role || '');

    // Modal states
    const [selectedUser, setSelectedUser] = useState<UserItem | null>(null);
    const [actionType, setActionType] = useState<'suspend' | 'unsuspend' | 'roles' | 'delete' | null>(null);
    const [password, setPassword] = useState('');
    const [suspendReason, setSuspendReason] = useState('');
    const [suspendDuration, setSuspendDuration] = useState<string>('7');
    const [selectedRoles, setSelectedRoles] = useState<string[]>([]);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [errorMessage, setErrorMessage] = useState<string | null>(null);

    const handleFilterSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/admin/users', { search, status, role }, { preserveState: true });
    };

    const handleResetFilters = () => {
        setSearch('');
        setStatus('');
        setRole('');
        router.get('/admin/users', {}, { preserveState: true });
    };

    const openModal = (user: UserItem, action: 'suspend' | 'unsuspend' | 'roles' | 'delete') => {
        setSelectedUser(user);
        setActionType(action);
        setPassword('');
        setErrorMessage(null);

        if (action === 'roles') {
            setSelectedRoles([...user.roles]);
        } else if (action === 'suspend') {
            setSuspendReason('');
            setSuspendDuration('7');
        }
    };

    const closeModal = () => {
        setSelectedUser(null);
        setActionType(null);
        setPassword('');
        setErrorMessage(null);
    };

    const handleModalSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedUser || !actionType || isSubmitting) return;

        setIsSubmitting(true);
        setErrorMessage(null);

        if (actionType === 'suspend') {
            router.post(
                `/admin/users/${selectedUser.id}/suspend`,
                {
                    reason: suspendReason,
                    duration_days: suspendDuration ? parseInt(suspendDuration, 10) : null,
                    password: password,
                },
                {
                    onSuccess: () => {
                        setIsSubmitting(false);
                        closeModal();
                    },
                    onError: (errors) => {
                        setIsSubmitting(false);
                        setErrorMessage(errors.password || errors.reason || errors.message || 'Gagal menangguhkan pengguna.');
                    },
                }
            );
        } else if (actionType === 'unsuspend') {
            router.post(
                `/admin/users/${selectedUser.id}/unsuspend`,
                { password },
                {
                    onSuccess: () => {
                        setIsSubmitting(false);
                        closeModal();
                    },
                    onError: (errors) => {
                        setIsSubmitting(false);
                        setErrorMessage(errors.password || errors.message || 'Gagal mengaktifkan kembali pengguna.');
                    },
                }
            );
        } else if (actionType === 'roles') {
            router.put(
                `/admin/users/${selectedUser.id}/roles`,
                {
                    roles: selectedRoles,
                    password: password,
                },
                {
                    onSuccess: () => {
                        setIsSubmitting(false);
                        closeModal();
                    },
                    onError: (errors) => {
                        setIsSubmitting(false);
                        setErrorMessage(errors.password || errors.roles || errors.message || 'Gagal mengubah peran.');
                    },
                }
            );
        } else if (actionType === 'delete') {
            router.delete(
                `/admin/users/${selectedUser.id}`,
                {
                    data: { password },
                    onSuccess: () => {
                        setIsSubmitting(false);
                        closeModal();
                    },
                    onError: (errors) => {
                        setIsSubmitting(false);
                        setErrorMessage(errors.password || errors.message || 'Gagal menghapus pengguna.');
                    },
                }
            );
        }
    };

    const toggleRole = (rName: string) => {
        if (selectedRoles.includes(rName)) {
            setSelectedRoles(selectedRoles.filter((r) => r !== rName));
        } else {
            setSelectedRoles([...selectedRoles, rName]);
        }
    };

    return (
        <AdminLayout
            title="Manajemen Pengguna"
            description="Pencarian, pemfilteran, penugasan peran, penangguhan akun, dan penghapusan pengguna."
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
                            placeholder="Cari nama, username, atau email..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            className="w-full bg-[#0D0D0D] border border-[#333333] focus:border-[#BAD306] focus:ring-0 text-xs px-3 py-2 text-[#F8F8F8] font-sans rounded-none placeholder:text-[#666666]"
                        />
                    </div>
                    <div className="w-full md:w-44">
                        <select
                            value={status}
                            onChange={(e) => setStatus(e.target.value)}
                            className="w-full bg-[#0D0D0D] border border-[#333333] focus:border-[#BAD306] focus:ring-0 text-xs px-3 py-2 text-[#F8F8F8] font-sans rounded-none"
                        >
                            <option value="">Semua Status</option>
                            <option value="active">Aktif</option>
                            <option value="suspended">Ditangguhkan</option>
                        </select>
                    </div>
                    <div className="w-full md:w-44">
                        <select
                            value={role}
                            onChange={(e) => setRole(e.target.value)}
                            className="w-full bg-[#0D0D0D] border border-[#333333] focus:border-[#BAD306] focus:ring-0 text-xs px-3 py-2 text-[#F8F8F8] font-sans rounded-none"
                        >
                            <option value="">Semua Peran</option>
                            {availableRoles.map((r) => (
                                <option key={r} value={r}>
                                    {r.toUpperCase()}
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
                        {(search || status || role) && (
                            <button
                                type="button"
                                onClick={handleResetFilters}
                                className="px-3 py-2 text-xs font-display tracking-wider uppercase bg-[#222222] hover:bg-[#2A2A2A] text-[#AAAAAA] hover:text-white border border-[#333333] rounded-none"
                            >
                                Reset
                            </button>
                        )}
                    </div>
                </form>

                {/* Users Table */}
                <div className="bg-[#141414] border border-[#262626] overflow-x-auto">
                    <table className="w-full text-left text-xs font-sans">
                        <thead className="bg-[#111111] border-b border-[#262626] text-[10px] font-mono uppercase tracking-wider text-[#888888]">
                            <tr>
                                <th className="px-4 py-3">Pengguna</th>
                                <th className="px-4 py-3">Status</th>
                                <th className="px-4 py-3">Peran Terdaftar</th>
                                <th className="px-4 py-3">Terdaftar</th>
                                <th className="px-4 py-3 text-right">Aksi Otoritas</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-[#202020]">
                            {users.data.length === 0 ? (
                                <tr>
                                    <td colSpan={5} className="px-4 py-10 text-center text-[#888888] font-mono">
                                        Tidak ditemukan pengguna sesuai kriteria pencarian.
                                    </td>
                                </tr>
                            ) : (
                                users.data.map((u) => (
                                    <tr key={u.id} className="hover:bg-[#181818] transition-colors">
                                        <td className="px-4 py-3">
                                            <div className="font-semibold text-[#F8F8F8]">{u.name}</div>
                                            <div className="text-[11px] font-mono text-[#888888]">
                                                {u.username ? `@${u.username}` : '-'} &bull; {u.email}
                                            </div>
                                            <div className="text-[10px] font-mono text-[#555555]">
                                                ID: {u.public_id}
                                            </div>
                                        </td>
                                        <td className="px-4 py-3 whitespace-nowrap">
                                            {u.is_suspended ? (
                                                <div>
                                                    <span className="px-2 py-0.5 bg-[#E56458]/10 border border-[#E56458] text-[#E56458] text-[10px] font-mono uppercase font-bold">
                                                        Ditangguhkan
                                                    </span>
                                                    {u.suspended_until && (
                                                        <div className="text-[10px] font-mono text-[#888888] mt-1">
                                                            s/d {new Date(u.suspended_until).toLocaleDateString('id-ID')}
                                                        </div>
                                                    )}
                                                </div>
                                            ) : (
                                                <span className="px-2 py-0.5 bg-[#1F2F1C] border border-[#48733E] text-[#86EFAC] text-[10px] font-mono uppercase">
                                                    Aktif
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3">
                                            <div className="flex flex-wrap gap-1">
                                                {u.roles.map((rName) => (
                                                    <span
                                                        key={rName}
                                                        className={`px-1.5 py-0.5 text-[10px] font-mono uppercase border ${
                                                            rName === 'superadmin' || rName === 'admin'
                                                                ? 'border-[#BAD306] text-[#BAD306] bg-[#BAD306]/10 font-bold'
                                                                : 'border-[#444444] text-[#CCCCCC] bg-[#1C1C1C]'
                                                        }`}
                                                    >
                                                        {rName}
                                                    </span>
                                                ))}
                                            </div>
                                        </td>
                                        <td className="px-4 py-3 text-[#888888] font-mono whitespace-nowrap">
                                            {u.created_at ? new Date(u.created_at).toLocaleDateString('id-ID') : '-'}
                                        </td>
                                        <td className="px-4 py-3 text-right whitespace-nowrap">
                                            <div className="flex items-center justify-end gap-1.5">
                                                {u.can_assign_roles && (
                                                    <button
                                                        type="button"
                                                        onClick={() => openModal(u, 'roles')}
                                                        className="px-2 py-1 text-[11px] font-display tracking-wider uppercase text-[#AAAAAA] hover:text-[#BAD306] bg-[#1E1E1E] hover:bg-[#252525] border border-[#333333] rounded-none"
                                                    >
                                                        Peran
                                                    </button>
                                                )}
                                                {u.can_suspend && (
                                                    u.is_suspended ? (
                                                        <button
                                                            type="button"
                                                            onClick={() => openModal(u, 'unsuspend')}
                                                            className="px-2 py-1 text-[11px] font-display tracking-wider uppercase text-[#86EFAC] hover:text-white bg-[#1F2F1C] hover:bg-[#2A4325] border border-[#48733E] rounded-none"
                                                        >
                                                            Aktifkan
                                                        </button>
                                                    ) : (
                                                        <button
                                                            type="button"
                                                            onClick={() => openModal(u, 'suspend')}
                                                            className="px-2 py-1 text-[11px] font-display tracking-wider uppercase text-[#E56458] hover:text-white bg-[#2A1818] hover:bg-[#3D2020] border border-[#E56458]/50 rounded-none"
                                                        >
                                                            Tangguhkan
                                                        </button>
                                                    )
                                                )}
                                                {u.can_delete && (
                                                    <button
                                                        type="button"
                                                        onClick={() => openModal(u, 'delete')}
                                                        className="px-2 py-1 text-[11px] font-display tracking-wider uppercase text-[#E56458] hover:bg-[#E56458] hover:text-white border border-[#E56458] rounded-none"
                                                    >
                                                        Hapus
                                                    </button>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

                {/* Pagination Controls */}
                {users.total > 0 && (
                    <div className="flex flex-col sm:flex-row items-center justify-between gap-4 pt-2 text-xs font-mono text-[#888888]">
                        <div>
                            Menampilkan {users.from || 0} - {users.to || 0} dari {users.total} pengguna
                        </div>
                        <div className="flex items-center gap-1">
                            {users.links.map((link, idx) => (
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

            {/* Action Modals */}
            {selectedUser && actionType && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80">
                    <div className="w-full max-w-lg bg-[#161616] border-2 border-[#333333] shadow-2xl p-6 text-[#F8F8F8] rounded-none">
                        <div className="flex items-center justify-between border-b border-[#262626] pb-3 mb-4">
                            <h3 className="font-display text-lg tracking-wider uppercase text-[#F8F8F8]">
                                {actionType === 'suspend' && `Tangguhkan Pengguna: ${selectedUser.name}`}
                                {actionType === 'unsuspend' && `Aktifkan Kembali: ${selectedUser.name}`}
                                {actionType === 'roles' && `Atur Peran: ${selectedUser.name}`}
                                {actionType === 'delete' && `Hapus Pengguna: ${selectedUser.name}`}
                            </h3>
                            <button
                                type="button"
                                onClick={closeModal}
                                disabled={isSubmitting}
                                className="text-[#888888] hover:text-white px-2 py-1 text-sm font-mono border border-transparent hover:border-[#444444] rounded-none"
                            >
                                [X]
                            </button>
                        </div>

                        {errorMessage && (
                            <div className="mb-4 p-3 bg-[#E56458]/10 border border-[#E56458] text-[#E56458] text-xs font-mono">
                                {errorMessage}
                            </div>
                        )}

                        <form onSubmit={handleModalSubmit} className="space-y-4">
                            {/* Suspend Form Body */}
                            {actionType === 'suspend' && (
                                <>
                                    <div>
                                        <label className="block text-xs font-mono uppercase tracking-wider text-[#888888] mb-1">
                                            Durasi Penangguhan (Hari)
                                        </label>
                                        <select
                                            value={suspendDuration}
                                            onChange={(e) => setSuspendDuration(e.target.value)}
                                            className="w-full bg-[#0D0D0D] border border-[#333333] focus:border-[#BAD306] focus:ring-0 text-[#F8F8F8] px-3 py-2 text-xs font-sans rounded-none"
                                        >
                                            <option value="1">1 Hari (24 Jam)</option>
                                            <option value="3">3 Hari</option>
                                            <option value="7">7 Hari (1 Minggu)</option>
                                            <option value="30">30 Hari (1 Bulan)</option>
                                            <option value="">Permanen (Tanpa Batas Waktu)</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label className="block text-xs font-mono uppercase tracking-wider text-[#888888] mb-1">
                                            Alasan Penangguhan (Wajib & Tercatat di Audit Log)
                                        </label>
                                        <textarea
                                            required
                                            rows={3}
                                            value={suspendReason}
                                            onChange={(e) => setSuspendReason(e.target.value)}
                                            placeholder="Jelaskan pelanggaran aturan komunitas..."
                                            className="w-full bg-[#0D0D0D] border border-[#333333] focus:border-[#BAD306] focus:ring-0 text-[#F8F8F8] px-3 py-2 text-xs font-sans rounded-none"
                                        />
                                    </div>
                                </>
                            )}

                            {/* Unsuspend Warning */}
                            {actionType === 'unsuspend' && (
                                <p className="text-sm text-[#AAAAAA] leading-relaxed font-sans">
                                    Status akun akan dikembalikan menjadi <strong className="text-white">Aktif</strong> dan masa penangguhan dibersihkan.
                                </p>
                            )}

                            {/* Roles Checklist */}
                            {actionType === 'roles' && (
                                <div>
                                    <label className="block text-xs font-mono uppercase tracking-wider text-[#888888] mb-2">
                                        Pilih Peran Sistem & Kustom (Minimal 1)
                                    </label>
                                    <div className="grid grid-cols-2 gap-2 bg-[#0D0D0D] border border-[#262626] p-3 max-h-48 overflow-y-auto">
                                        {availableRoles.map((rName) => {
                                            const isChecked = selectedRoles.includes(rName);
                                            return (
                                                <label
                                                    key={rName}
                                                    className="flex items-center gap-2 p-2 border border-[#222222] bg-[#141414] hover:bg-[#1A1A1A] cursor-pointer text-xs font-mono"
                                                >
                                                    <input
                                                        type="checkbox"
                                                        checked={isChecked}
                                                        onChange={() => toggleRole(rName)}
                                                        className="rounded-none bg-[#0D0D0D] border-[#444444] text-[#BAD306] focus:ring-0"
                                                    />
                                                    <span className={isChecked ? 'text-[#BAD306] font-bold uppercase' : 'text-[#AAAAAA] uppercase'}>
                                                        {rName}
                                                    </span>
                                                </label>
                                            );
                                        })}
                                    </div>
                                </div>
                            )}

                            {/* Delete User Warning */}
                            {actionType === 'delete' && (
                                <p className="text-sm text-[#E56458] leading-relaxed font-sans bg-[#E56458]/10 border border-[#E56458]/30 p-3">
                                    PERINGATAN: Penghapusan pengguna ini bersifat permanen. Riwayat baca, bookmark, dan komentar terkait akan dibersihkan atau dianomikan. Tindakan ini tidak dapat dibatalkan.
                                </p>
                            )}

                            {/* Password Confirmation */}
                            <div>
                                <label className="block text-xs font-mono uppercase tracking-wider text-[#888888] mb-1">
                                    Kata Sandi Anda (Otorisasi Tindakan)
                                </label>
                                <input
                                    type="password"
                                    required
                                    value={password}
                                    onChange={(e) => setPassword(e.target.value)}
                                    placeholder="Masukkan kata sandi akun Anda..."
                                    className="w-full bg-[#0D0D0D] border border-[#333333] focus:border-[#BAD306] focus:ring-0 text-[#F8F8F8] px-3 py-2 text-xs font-sans rounded-none"
                                />
                            </div>

                            {/* Buttons */}
                            <div className="flex items-center justify-end gap-3 pt-3 border-t border-[#262626]">
                                <button
                                    type="button"
                                    onClick={closeModal}
                                    disabled={isSubmitting}
                                    className="px-4 py-2 text-xs font-display tracking-wider uppercase text-[#AAAAAA] hover:text-white bg-[#222222] hover:bg-[#2A2A2A] border border-[#333333] rounded-none"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    disabled={isSubmitting || !password.trim()}
                                    className={`px-4 py-2 text-xs font-display tracking-wider uppercase font-bold rounded-none disabled:opacity-50 ${
                                        actionType === 'delete' || actionType === 'suspend'
                                            ? 'bg-[#E56458] text-white hover:bg-[#d44f43] border border-[#E56458]'
                                            : 'bg-[#BAD306] text-[#111111] hover:bg-[#a6bd05] border border-[#BAD306]'
                                    }`}
                                >
                                    {isSubmitting ? 'Memproses...' : 'Konfirmasi Tindakan'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}
