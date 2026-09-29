import React, { FormEventHandler } from 'react';
import { useForm, usePage, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { PageProps } from '@/types';

interface EditProfileProps {
    mustVerifyEmail: boolean;
    status?: string;
}

export default function Edit({ mustVerifyEmail, status }: EditProfileProps) {
    const user = usePage<PageProps>().props.auth.user;

    // Profile form
    const profileForm = useForm({
        name: user?.name || '',
        username: user?.username || '',
        email: user?.email || '',
    });

    const updateProfile: FormEventHandler = (e) => {
        e.preventDefault();
        profileForm.patch('/profile');
    };

    // Password form
    const passwordForm = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const updatePassword: FormEventHandler = (e) => {
        e.preventDefault();
        passwordForm.put('/password', {
            onSuccess: () => passwordForm.reset(),
        });
    };

    // Other sessions form
    const otherSessionsForm = useForm({
        password: '',
    });

    const logoutOtherSessions: FormEventHandler = (e) => {
        e.preventDefault();
        otherSessionsForm.post('/profile/logout-other-sessions', {
            onSuccess: () => otherSessionsForm.reset(),
        });
    };

    return (
        <AppLayout title="Profil Akun" noIndex={true}>
            <div className="max-w-3xl mx-auto space-y-8">
                <div className="pb-4 border-b-2 border-[#222222]">
                    <h1 className="font-display text-3xl uppercase tracking-wider text-[#F8F8F8]">
                        Pengaturan Akun & Keamanan
                    </h1>
                    <p className="text-sm text-[#AAAAAA] mt-1">
                        Kelola data profil, kata sandi, dan sesi aktif perangkat Anda.
                    </p>
                </div>

                {status === 'profile-updated' && (
                    <div className="p-3 bg-[#BAD306]/10 border border-[#BAD306] text-[#BAD306] text-xs font-mono" role="status" aria-live="polite">
                        [OK] Profil berhasil diperbarui.
                    </div>
                )}

                {status === 'password-updated' && (
                    <div className="p-3 bg-[#BAD306]/10 border border-[#BAD306] text-[#BAD306] text-xs font-mono" role="status" aria-live="polite">
                        [OK] Kata sandi berhasil diperbarui.
                    </div>
                )}

                {status === 'other-sessions-logged-out' && (
                    <div className="p-3 bg-[#BAD306]/10 border border-[#BAD306] text-[#BAD306] text-xs font-mono" role="status" aria-live="polite">
                        [OK] Sesi pada perangkat lain berhasil dikeluarkan.
                    </div>
                )}

                {/* Section 1: Data Profil */}
                <div className="p-6 bg-[#161616] border-2 border-[#222222] rounded-none">
                    <h2 className="font-display text-xl uppercase tracking-wide text-[#F8F8F8] mb-1">
                        Informasi Profil
                    </h2>
                    <p className="text-xs text-[#AAAAAA] mb-6">
                        Perbarui nama, username, dan alamat email akun Anda.
                    </p>

                    <form onSubmit={updateProfile} className="space-y-4 max-w-xl">
                        <div>
                            <label htmlFor="name" className="block text-xs font-display tracking-wider uppercase text-[#AAAAAA] mb-1">
                                Nama Lengkap
                            </label>
                            <input
                                id="name"
                                type="text"
                                value={profileForm.data.name}
                                onChange={(e) => profileForm.setData('name', e.target.value)}
                                aria-describedby={profileForm.errors.name ? 'name-error' : undefined}
                                className="w-full bg-[#111111] text-sm text-[#F8F8F8] px-3.5 py-2.5 rounded-none border border-[#444444] focus:border-[#BAD306] focus:outline-none transition-colors"
                                required
                            />
                            {profileForm.errors.name && (
                                <p id="name-error" className="mt-1 text-xs text-[#E56458] font-mono">{profileForm.errors.name}</p>
                            )}
                        </div>

                        <div>
                            <label htmlFor="username" className="block text-xs font-display tracking-wider uppercase text-[#AAAAAA] mb-1">
                                Username
                            </label>
                            <input
                                id="username"
                                type="text"
                                value={profileForm.data.username}
                                onChange={(e) => profileForm.setData('username', e.target.value)}
                                aria-describedby={profileForm.errors.username ? 'username-error' : undefined}
                                className="w-full bg-[#111111] text-sm text-[#F8F8F8] px-3.5 py-2.5 rounded-none border border-[#444444] focus:border-[#BAD306] focus:outline-none transition-colors"
                            />
                            {profileForm.errors.username && (
                                <p id="username-error" className="mt-1 text-xs text-[#E56458] font-mono">{profileForm.errors.username}</p>
                            )}
                        </div>

                        <div>
                            <label htmlFor="email" className="block text-xs font-display tracking-wider uppercase text-[#AAAAAA] mb-1">
                                Alamat Email
                            </label>
                            <input
                                id="email"
                                type="email"
                                value={profileForm.data.email}
                                onChange={(e) => profileForm.setData('email', e.target.value)}
                                aria-describedby={profileForm.errors.email ? 'email-error' : undefined}
                                className="w-full bg-[#111111] text-sm text-[#F8F8F8] px-3.5 py-2.5 rounded-none border border-[#444444] focus:border-[#BAD306] focus:outline-none transition-colors"
                                required
                            />
                            {profileForm.errors.email && (
                                <p id="email-error" className="mt-1 text-xs text-[#E56458] font-mono">{profileForm.errors.email}</p>
                            )}
                        </div>

                        {mustVerifyEmail && user?.email_verified_at === null && (
                            <div className="p-3 bg-[#111111] border border-[#444444] text-xs text-[#AAAAAA]">
                                Alamat email Anda belum diverifikasi.{' '}
                                <Link
                                    href="/email/verification-notification"
                                    method="post"
                                    as="button"
                                    className="font-bold text-[#BAD306] hover:underline"
                                >
                                    Kirim ulang email verifikasi.
                                </Link>
                            </div>
                        )}

                        <button
                            type="submit"
                            disabled={profileForm.processing}
                            className="px-5 py-2.5 min-h-[44px] font-display text-xs tracking-wider uppercase text-[#111111] bg-[#BAD306] hover:bg-[#E0FF00] border-2 border-[#BAD306] font-bold rounded-none transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#BAD306] disabled:opacity-50"
                        >
                            {profileForm.processing ? 'Menyimpan...' : 'Simpan Profil'}
                        </button>
                    </form>
                </div>

                {/* Section 2: Update Password */}
                <div className="p-6 bg-[#161616] border-2 border-[#222222] rounded-none">
                    <h2 className="font-display text-xl uppercase tracking-wide text-[#F8F8F8] mb-1">
                        Perbarui Kata Sandi
                    </h2>
                    <p className="text-xs text-[#AAAAAA] mb-6">
                        Pastikan akun Anda menggunakan kata sandi yang panjang dan acak demi keamanan.
                    </p>

                    <form onSubmit={updatePassword} className="space-y-4 max-w-xl">
                        <div>
                            <label htmlFor="current_password" className="block text-xs font-display tracking-wider uppercase text-[#AAAAAA] mb-1">
                                Kata Sandi Saat Ini
                            </label>
                            <input
                                id="current_password"
                                type="password"
                                value={passwordForm.data.current_password}
                                autoComplete="current-password"
                                onChange={(e) => passwordForm.setData('current_password', e.target.value)}
                                aria-describedby={passwordForm.errors.current_password ? 'current-password-error' : undefined}
                                className="w-full bg-[#111111] text-sm text-[#F8F8F8] px-3.5 py-2.5 rounded-none border border-[#444444] focus:border-[#BAD306] focus:outline-none transition-colors"
                                required
                            />
                            {passwordForm.errors.current_password && (
                                <p id="current-password-error" className="mt-1 text-xs text-[#E56458] font-mono">{passwordForm.errors.current_password}</p>
                            )}
                        </div>

                        <div>
                            <label htmlFor="new_password" className="block text-xs font-display tracking-wider uppercase text-[#AAAAAA] mb-1">
                                Kata Sandi Baru
                            </label>
                            <input
                                id="new_password"
                                type="password"
                                value={passwordForm.data.password}
                                autoComplete="new-password"
                                onChange={(e) => passwordForm.setData('password', e.target.value)}
                                aria-describedby={passwordForm.errors.password ? 'new-password-error' : undefined}
                                className="w-full bg-[#111111] text-sm text-[#F8F8F8] px-3.5 py-2.5 rounded-none border border-[#444444] focus:border-[#BAD306] focus:outline-none transition-colors"
                                required
                            />
                            {passwordForm.errors.password && (
                                <p id="new-password-error" className="mt-1 text-xs text-[#E56458] font-mono">{passwordForm.errors.password}</p>
                            )}
                        </div>

                        <div>
                            <label htmlFor="confirm_password" className="block text-xs font-display tracking-wider uppercase text-[#AAAAAA] mb-1">
                                Konfirmasi Kata Sandi Baru
                            </label>
                            <input
                                id="confirm_password"
                                type="password"
                                value={passwordForm.data.password_confirmation}
                                autoComplete="new-password"
                                onChange={(e) => passwordForm.setData('password_confirmation', e.target.value)}
                                aria-describedby={passwordForm.errors.password_confirmation ? 'confirm-password-error' : undefined}
                                className="w-full bg-[#111111] text-sm text-[#F8F8F8] px-3.5 py-2.5 rounded-none border border-[#444444] focus:border-[#BAD306] focus:outline-none transition-colors"
                                required
                            />
                            {passwordForm.errors.password_confirmation && (
                                <p id="confirm-password-error" className="mt-1 text-xs text-[#E56458] font-mono">{passwordForm.errors.password_confirmation}</p>
                            )}
                        </div>

                        <button
                            type="submit"
                            disabled={passwordForm.processing}
                            className="px-5 py-2.5 min-h-[44px] font-display text-xs tracking-wider uppercase text-[#111111] bg-[#BAD306] hover:bg-[#E0FF00] border-2 border-[#BAD306] font-bold rounded-none transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#BAD306] disabled:opacity-50"
                        >
                            {passwordForm.processing ? 'Menyimpan...' : 'Ganti Kata Sandi'}
                        </button>
                    </form>
                </div>

                {/* Section 3: Logout Other Sessions */}
                <div className="p-6 bg-[#161616] border-2 border-[#222222] rounded-none">
                    <h2 className="font-display text-xl uppercase tracking-wide text-[#F8F8F8] mb-1">
                        Sesi Perangkat Lain
                    </h2>
                    <p className="text-xs text-[#AAAAAA] mb-6">
                        Keluarkan akun Anda dari semua sesi browser dan perangkat lain jika mencurigai adanya akses tidak sah.
                    </p>

                    <form onSubmit={logoutOtherSessions} className="space-y-4 max-w-xl">
                        <div>
                            <label htmlFor="session_password" className="block text-xs font-display tracking-wider uppercase text-[#AAAAAA] mb-1">
                                Konfirmasi Kata Sandi Anda
                            </label>
                            <input
                                id="session_password"
                                type="password"
                                value={otherSessionsForm.data.password}
                                onChange={(e) => otherSessionsForm.setData('password', e.target.value)}
                                aria-describedby={otherSessionsForm.errors.password ? 'session-password-error' : undefined}
                                className="w-full bg-[#111111] text-sm text-[#F8F8F8] px-3.5 py-2.5 rounded-none border border-[#444444] focus:border-[#BAD306] focus:outline-none transition-colors"
                                placeholder="Masukkan kata sandi saat ini"
                                required
                            />
                            {otherSessionsForm.errors.password && (
                                <p id="session-password-error" className="mt-1 text-xs text-[#E56458] font-mono">{otherSessionsForm.errors.password}</p>
                            )}
                        </div>

                        <button
                            type="submit"
                            disabled={otherSessionsForm.processing}
                            className="px-5 py-2.5 min-h-[44px] font-display text-xs tracking-wider uppercase text-[#F8F8F8] bg-[#161616] hover:bg-[#E56458] border border-[#E56458] font-bold rounded-none transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#E56458] disabled:opacity-50"
                        >
                            {otherSessionsForm.processing ? 'Memproses...' : 'Keluarkan dari Perangkat Lain'}
                        </button>
                    </form>
                </div>
            </div>
        </AppLayout>
    );
}
