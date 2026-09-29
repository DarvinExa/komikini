import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

interface RoleItem {
    id: number;
    name: string;
    is_system_role: boolean;
    users_count: number;
    permissions: string[];
    can_delete: boolean;
    can_assign_permissions: boolean;
}

interface RolesIndexProps {
    roles: RoleItem[];
    allPermissions: string[];
    canCreateRole: boolean;
}

export default function RolesIndex({ roles, allPermissions, canCreateRole }: RolesIndexProps) {
    // Modal states
    const [selectedRole, setSelectedRole] = useState<RoleItem | null>(null);
    const [modalMode, setModalMode] = useState<'create' | 'permissions' | 'delete' | null>(null);

    // Form states
    const [roleName, setRoleName] = useState('');
    const [selectedPermissions, setSelectedPermissions] = useState<string[]>([]);
    const [password, setPassword] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [errorMessage, setErrorMessage] = useState<string | null>(null);

    // Group permissions by prefix before dot (e.g. comics, chapters, comments, etc.)
    const groupedPermissions = allPermissions.reduce<Record<string, string[]>>((acc, perm) => {
        const prefix = perm.split('.')[0] || 'lainnya';
        if (!acc[prefix]) acc[prefix] = [];
        acc[prefix].push(perm);
        return acc;
    }, {});

    const openCreateModal = () => {
        setSelectedRole(null);
        setModalMode('create');
        setRoleName('');
        setSelectedPermissions([]);
        setPassword('');
        setErrorMessage(null);
    };

    const openPermissionsModal = (role: RoleItem) => {
        setSelectedRole(role);
        setModalMode('permissions');
        setSelectedPermissions([...role.permissions]);
        setPassword('');
        setErrorMessage(null);
    };

    const openDeleteModal = (role: RoleItem) => {
        setSelectedRole(role);
        setModalMode('delete');
        setPassword('');
        setErrorMessage(null);
    };

    const closeModal = () => {
        setSelectedRole(null);
        setModalMode(null);
        setPassword('');
        setErrorMessage(null);
    };

    const togglePermission = (perm: string) => {
        if (selectedPermissions.includes(perm)) {
            setSelectedPermissions(selectedPermissions.filter((p) => p !== perm));
        } else {
            setSelectedPermissions([...selectedPermissions, perm]);
        }
    };

    const toggleGroup = (groupPerms: string[]) => {
        const allInGroupSelected = groupPerms.every((p) => selectedPermissions.includes(p));
        if (allInGroupSelected) {
            setSelectedPermissions(selectedPermissions.filter((p) => !groupPerms.includes(p)));
        } else {
            const set = new Set([...selectedPermissions, ...groupPerms]);
            setSelectedPermissions(Array.from(set));
        }
    };

    const toggleAll = () => {
        if (selectedPermissions.length === allPermissions.length) {
            setSelectedPermissions([]);
        } else {
            setSelectedPermissions([...allPermissions]);
        }
    };

    const handleFormSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!modalMode || isSubmitting) return;

        setIsSubmitting(true);
        setErrorMessage(null);

        if (modalMode === 'create') {
            router.post(
                '/admin/roles',
                {
                    name: roleName.toLowerCase(),
                    permissions: selectedPermissions,
                    password: password,
                },
                {
                    onSuccess: () => {
                        setIsSubmitting(false);
                        closeModal();
                    },
                    onError: (errors) => {
                        setIsSubmitting(false);
                        setErrorMessage(errors.name || errors.permissions || errors.password || errors.message || 'Gagal membuat role.');
                    },
                }
            );
        } else if (modalMode === 'permissions' && selectedRole) {
            router.put(
                `/admin/roles/${selectedRole.id}/permissions`,
                {
                    permissions: selectedPermissions,
                    password: password,
                },
                {
                    onSuccess: () => {
                        setIsSubmitting(false);
                        closeModal();
                    },
                    onError: (errors) => {
                        setIsSubmitting(false);
                        setErrorMessage(errors.permissions || errors.password || errors.message || 'Gagal memperbarui permissions.');
                    },
                }
            );
        } else if (modalMode === 'delete' && selectedRole) {
            router.delete(
                `/admin/roles/${selectedRole.id}`,
                {
                    data: { password },
                    onSuccess: () => {
                        setIsSubmitting(false);
                        closeModal();
                    },
                    onError: (errors) => {
                        setIsSubmitting(false);
                        setErrorMessage(errors.password || errors.message || 'Gagal menghapus role.');
                    },
                }
            );
        }
    };

    return (
        <AdminLayout
            title="Peran & Matriks Permission"
            description="Manajemen peran sistem bawaan dan kustom, konfigurasi matriks hak akses, serta proteksi invariasi superadmin."
        >
            <div className="space-y-6">
                {/* Actions Bar */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <p className="text-xs text-[#888888] font-sans">
                        Peran sistem (<span className="text-[#BAD306] font-mono">superadmin, admin, moderator, user</span>) tidak dapat dihapus.
                    </p>
                    {canCreateRole && (
                        <button
                            type="button"
                            onClick={openCreateModal}
                            className="px-4 py-2 text-xs font-display tracking-wider uppercase bg-[#BAD306] text-[#111111] hover:bg-[#a6bd05] font-bold rounded-none shrink-0"
                        >
                            + Tambah Peran Kustom
                        </button>
                    )}
                </div>

                {/* Roles Cards Grid */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    {roles.map((role) => (
                        <div
                            key={role.id}
                            className={`bg-[#141414] border p-5 flex flex-col justify-between ${
                                role.name === 'superadmin'
                                    ? 'border-[#BAD306]/70'
                                    : 'border-[#262626]'
                            }`}
                        >
                            <div>
                                <div className="flex items-center justify-between mb-3">
                                    <h3 className="font-display text-base tracking-wider uppercase text-[#F8F8F8]">
                                        {role.name}
                                    </h3>
                                    {role.is_system_role ? (
                                        <span className="px-2 py-0.5 bg-[#1F1F1F] border border-[#333333] text-[9px] font-mono uppercase text-[#AAAAAA]">
                                            SISTEM (TERKUNCI)
                                        </span>
                                    ) : (
                                        <span className="px-2 py-0.5 bg-[#BAD306]/10 border border-[#BAD306] text-[9px] font-mono uppercase text-[#BAD306] font-bold">
                                            KUSTOM
                                        </span>
                                    )}
                                </div>

                                <div className="space-y-2 text-xs font-mono text-[#888888]">
                                    <div className="flex items-center justify-between">
                                        <span>Pengguna Menggunakan:</span>
                                        <span className="text-[#F8F8F8] font-bold">{role.users_count}</span>
                                    </div>
                                    <div className="flex items-center justify-between">
                                        <span>Permissions Diberikan:</span>
                                        <span className="text-[#BAD306] font-bold">
                                            {role.name === 'superadmin' ? 'SEMUA (Bypass)' : `${role.permissions.length} / ${allPermissions.length}`}
                                        </span>
                                    </div>
                                </div>

                                {/* Permissions Snippets */}
                                <div className="mt-4 pt-3 border-t border-[#222222]">
                                    <div className="text-[10px] font-mono uppercase tracking-wider text-[#666666] mb-2">
                                        Sampel Hak Akses:
                                    </div>
                                    <div className="flex flex-wrap gap-1 max-h-24 overflow-hidden">
                                        {role.permissions.slice(0, 6).map((p) => (
                                            <span
                                                key={p}
                                                className="px-1.5 py-0.5 bg-[#1A1A1A] border border-[#303030] text-[10px] font-mono text-[#AAAAAA]"
                                            >
                                                {p}
                                            </span>
                                        ))}
                                        {role.permissions.length > 6 && (
                                            <span className="px-1.5 py-0.5 bg-[#1A1A1A] border border-[#303030] text-[10px] font-mono text-[#666666]">
                                                +{role.permissions.length - 6} lainnya
                                            </span>
                                        )}
                                        {role.permissions.length === 0 && (
                                            <span className="text-[10px] font-mono text-[#555555]">
                                                Belum ada hak akses eksplisit.
                                            </span>
                                        )}
                                    </div>
                                </div>
                            </div>

                            {/* Actions */}
                            <div className="mt-5 pt-3 border-t border-[#222222] flex items-center justify-end gap-2">
                                {role.can_assign_permissions && (
                                    <button
                                        type="button"
                                        onClick={() => openPermissionsModal(role)}
                                        className="px-3 py-1.5 text-xs font-display tracking-wider uppercase text-[#AAAAAA] hover:text-[#BAD306] bg-[#1E1E1E] hover:bg-[#252525] border border-[#333333] rounded-none"
                                    >
                                        Kelola Matriks
                                    </button>
                                )}
                                {role.can_delete && (
                                    <button
                                        type="button"
                                        onClick={() => openDeleteModal(role)}
                                        className="px-3 py-1.5 text-xs font-display tracking-wider uppercase text-[#E56458] hover:bg-[#E56458] hover:text-white border border-[#E56458] rounded-none"
                                    >
                                        Hapus
                                    </button>
                                )}
                            </div>
                        </div>
                    ))}
                </div>
            </div>

            {/* Role Modal (Create or Permissions or Delete) */}
            {modalMode && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80">
                    <div className="w-full max-w-2xl bg-[#161616] border-2 border-[#333333] shadow-2xl p-6 text-[#F8F8F8] rounded-none max-h-[90vh] flex flex-col">
                        <div className="flex items-center justify-between border-b border-[#262626] pb-3 mb-4 shrink-0">
                            <h3 className="font-display text-lg tracking-wider uppercase text-[#F8F8F8]">
                                {modalMode === 'create' && 'Tambah Peran Kustom Baru'}
                                {modalMode === 'permissions' && `Konfigurasi Matriks Permission: ${selectedRole?.name}`}
                                {modalMode === 'delete' && `Hapus Peran: ${selectedRole?.name}`}
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
                            <div className="mb-4 p-3 bg-[#E56458]/10 border border-[#E56458] text-[#E56458] text-xs font-mono shrink-0">
                                {errorMessage}
                            </div>
                        )}

                        <form onSubmit={handleFormSubmit} className="flex-1 flex flex-col min-h-0 space-y-4">
                            {/* Role Name input for Create */}
                            {modalMode === 'create' && (
                                <div className="shrink-0">
                                    <label className="block text-xs font-mono uppercase tracking-wider text-[#888888] mb-1">
                                        Nama Peran (Huruf kecil, angka, garis bawah atau strip, cth: content-editor)
                                    </label>
                                    <input
                                        type="text"
                                        required
                                        pattern="[a-z0-9_-]+"
                                        value={roleName}
                                        onChange={(e) => setRoleName(e.target.value.toLowerCase())}
                                        placeholder="cth: content-editor"
                                        className="w-full bg-[#0D0D0D] border border-[#333333] focus:border-[#BAD306] focus:ring-0 text-[#F8F8F8] px-3 py-2 text-xs font-mono rounded-none"
                                    />
                                </div>
                            )}

                            {/* Permissions Matrix for Create & Permissions */}
                            {(modalMode === 'create' || modalMode === 'permissions') && (
                                <div className="flex-1 flex flex-col min-h-0">
                                    <div className="flex items-center justify-between mb-2 shrink-0">
                                        <label className="block text-xs font-mono uppercase tracking-wider text-[#888888]">
                                            Matriks Hak Akses ({selectedPermissions.length} / {allPermissions.length} dipilih)
                                        </label>
                                        <button
                                            type="button"
                                            onClick={toggleAll}
                                            className="text-[10px] font-mono uppercase text-[#BAD306] hover:underline"
                                        >
                                            {selectedPermissions.length === allPermissions.length ? 'Batal Pilih Semua' : 'Pilih Semua'}
                                        </button>
                                    </div>

                                    <div className="flex-1 overflow-y-auto space-y-4 bg-[#0D0D0D] border border-[#262626] p-4 text-xs">
                                        {Object.entries(groupedPermissions).map(([group, perms]) => {
                                            const allInGroup = perms.every((p) => selectedPermissions.includes(p));
                                            return (
                                                <div key={group} className="border border-[#1F1F1F] p-3 bg-[#111111]">
                                                    <div className="flex items-center justify-between mb-2">
                                                        <span className="font-mono text-[11px] uppercase tracking-wider text-[#BAD306] font-bold">
                                                            Modul: {group}
                                                        </span>
                                                        <button
                                                            type="button"
                                                            onClick={() => toggleGroup(perms)}
                                                            className="text-[10px] font-mono text-[#888888] hover:text-white"
                                                        >
                                                            {allInGroup ? 'Batal Pilih Modul' : 'Pilih Modul'}
                                                        </button>
                                                    </div>
                                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                        {perms.map((p) => {
                                                            const isChecked = selectedPermissions.includes(p);
                                                            return (
                                                                <label
                                                                    key={p}
                                                                    className="flex items-center gap-2 p-1.5 bg-[#171717] border border-[#262626] hover:bg-[#202020] cursor-pointer"
                                                                >
                                                                    <input
                                                                        type="checkbox"
                                                                        checked={isChecked}
                                                                        onChange={() => togglePermission(p)}
                                                                        className="rounded-none bg-[#0D0D0D] border-[#444444] text-[#BAD306] focus:ring-0"
                                                                    />
                                                                    <span className={`font-mono text-[11px] ${isChecked ? 'text-[#F8F8F8] font-bold' : 'text-[#888888]'}`}>
                                                                        {p}
                                                                    </span>
                                                                </label>
                                                            );
                                                        })}
                                                    </div>
                                                </div>
                                            );
                                        })}
                                    </div>
                                </div>
                            )}

                            {/* Delete Role Warning */}
                            {modalMode === 'delete' && (
                                <p className="text-sm text-[#E56458] leading-relaxed font-sans bg-[#E56458]/10 border border-[#E56458]/30 p-3 shrink-0">
                                    PERINGATAN: Peran kustom <strong className="text-white">[{selectedRole?.name}]</strong> akan dihapus secara permanen. Pengguna yang memiliki peran ini akan kehilangan hak akses yang terkait.
                                </p>
                            )}

                            {/* Password Confirmation */}
                            <div className="shrink-0 pt-2">
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
                            <div className="flex items-center justify-end gap-3 pt-3 border-t border-[#262626] shrink-0">
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
                                        modalMode === 'delete'
                                            ? 'bg-[#E56458] text-white hover:bg-[#d44f43] border border-[#E56458]'
                                            : 'bg-[#BAD306] text-[#111111] hover:bg-[#a6bd05] border border-[#BAD306]'
                                    }`}
                                >
                                    {isSubmitting ? 'Memproses...' : 'Simpan Konfigurasi'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}
