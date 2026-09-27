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
        <AppLayout title="Profil Akun">
            <div className="max-w-4xl mx-auto space-y-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight text-gray-900">
                        Pengaturan Akun & Keamanan
                    </h1>
                    <p className="text-sm text-gray-500">
                        Kelola data profil, kata sandi, dan sesi aktif perangkat Anda.
                    </p>
                </div>

                {status === 'profile-updated' && (
                    <div className="p-3 rounded-lg bg-emerald-50 text-emerald-700 text-sm font-medium">
                        Profil berhasil diperbarui.
                    </div>
                )}

                {status === 'password-updated' && (
                    <div className="p-3 rounded-lg bg-emerald-50 text-emerald-700 text-sm font-medium">
                        Kata sandi berhasil diperbarui.
                    </div>
                )}

                {status === 'other-sessions-logged-out' && (
                    <div className="p-3 rounded-lg bg-emerald-50 text-emerald-700 text-sm font-medium">
                        Sesi pada perangkat lain berhasil dikeluarkan.
                    </div>
                )}

                {/* Section 1: Data Profil */}
                <div className="p-6 bg-white rounded-xl border border-gray-200 shadow-xs">
                    <h2 className="text-lg font-semibold text-gray-900 mb-1">
                        Informasi Profil
                    </h2>
                    <p className="text-xs text-gray-500 mb-6">
                        Perbarui nama, username, dan alamat email akun Anda.
                    </p>

                    <form onSubmit={updateProfile} className="space-y-4 max-w-xl">
                        <div>
                            <label htmlFor="name" className="block text-sm font-medium text-gray-700">
                                Nama
                            </label>
                            <input
                                id="name"
                                type="text"
                                value={profileForm.data.name}
                                onChange={(e) => profileForm.setData('name', e.target.value)}
                                className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-hidden focus:ring-1 focus:ring-indigo-500"
                                required
                            />
                            {profileForm.errors.name && (
                                <p className="mt-1 text-xs text-rose-600">{profileForm.errors.name}</p>
                            )}
                        </div>

                        <div>
                            <label htmlFor="username" className="block text-sm font-medium text-gray-700">
                                Username
                            </label>
                            <input
                                id="username"
                                type="text"
                                value={profileForm.data.username}
                                onChange={(e) => profileForm.setData('username', e.target.value)}
                                className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-hidden focus:ring-1 focus:ring-indigo-500"
                            />
                            {profileForm.errors.username && (
                                <p className="mt-1 text-xs text-rose-600">{profileForm.errors.username}</p>
                            )}
                        </div>

                        <div>
                            <label htmlFor="email" className="block text-sm font-medium text-gray-700">
                                Email
                            </label>
                            <input
                                id="email"
                                type="email"
                                value={profileForm.data.email}
                                onChange={(e) => profileForm.setData('email', e.target.value)}
                                className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-hidden focus:ring-1 focus:ring-indigo-500"
                                required
                            />
                            {profileForm.errors.email && (
                                <p className="mt-1 text-xs text-rose-600">{profileForm.errors.email}</p>
                            )}
                        </div>

                        {mustVerifyEmail && user?.email_verified_at === null && (
                            <div className="p-3 rounded-lg bg-amber-50 text-amber-800 text-xs">
                                Alamat email Anda belum diverifikasi.{' '}
                                <Link
                                    href="/email/verification-notification"
                                    method="post"
                                    as="button"
                                    className="font-medium underline hover:text-amber-900"
                                >
                                    Klik di sini untuk mengirim ulang email verifikasi.
                                </Link>
                            </div>
                        )}

                        <button
                            type="submit"
                            disabled={profileForm.processing}
                            className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50"
                        >
                            {profileForm.processing ? 'Menyimpan...' : 'Simpan Profil'}
                        </button>
                    </form>
                </div>

                {/* Section 2: Update Password */}
                <div className="p-6 bg-white rounded-xl border border-gray-200 shadow-xs">
                    <h2 className="text-lg font-semibold text-gray-900 mb-1">
                        Perbarui Kata Sandi
                    </h2>
                    <p className="text-xs text-gray-500 mb-6">
                        Pastikan akun Anda menggunakan kata sandi yang panjang dan acak demi keamanan.
                    </p>

                    <form onSubmit={updatePassword} className="space-y-4 max-w-xl">
                        <div>
                            <label htmlFor="current_password" className="block text-sm font-medium text-gray-700">
                                Kata Sandi Saat Ini
                            </label>
                            <input
                                id="current_password"
                                type="password"
                                value={passwordForm.data.current_password}
                                autoComplete="current-password"
                                onChange={(e) => passwordForm.setData('current_password', e.target.value)}
                                className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-hidden focus:ring-1 focus:ring-indigo-500"
                                required
                            />
                            {passwordForm.errors.current_password && (
                                <p className="mt-1 text-xs text-rose-600">{passwordForm.errors.current_password}</p>
                            )}
                        </div>

                        <div>
                            <label htmlFor="new_password" className="block text-sm font-medium text-gray-700">
                                Kata Sandi Baru
                            </label>
                            <input
                                id="new_password"
                                type="password"
                                value={passwordForm.data.password}
                                autoComplete="new-password"
                                onChange={(e) => passwordForm.setData('password', e.target.value)}
                                className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-hidden focus:ring-1 focus:ring-indigo-500"
                                required
                            />
                            {passwordForm.errors.password && (
                                <p className="mt-1 text-xs text-rose-600">{passwordForm.errors.password}</p>
                            )}
                        </div>

                        <div>
                            <label htmlFor="confirm_password" className="block text-sm font-medium text-gray-700">
                                Konfirmasi Kata Sandi Baru
                            </label>
                            <input
                                id="confirm_password"
                                type="password"
                                value={passwordForm.data.password_confirmation}
                                autoComplete="new-password"
                                onChange={(e) => passwordForm.setData('password_confirmation', e.target.value)}
                                className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-hidden focus:ring-1 focus:ring-indigo-500"
                                required
                            />
                            {passwordForm.errors.password_confirmation && (
                                <p className="mt-1 text-xs text-rose-600">{passwordForm.errors.password_confirmation}</p>
                            )}
                        </div>

                        <button
                            type="submit"
                            disabled={passwordForm.processing}
                            className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50"
                        >
                            {passwordForm.processing ? 'Menyimpan...' : 'Ganti Kata Sandi'}
                        </button>
                    </form>
                </div>

                {/* Section 3: Logout Other Sessions */}
                <div className="p-6 bg-white rounded-xl border border-gray-200 shadow-xs">
                    <h2 className="text-lg font-semibold text-gray-900 mb-1">
                        Sesi Perangkat Lain
                    </h2>
                    <p className="text-xs text-gray-500 mb-6">
                        Keluarkan akun Anda dari semua sesi browser dan perangkat lain jika mencurigai adanya akses tidak sah.
                    </p>

                    <form onSubmit={logoutOtherSessions} className="space-y-4 max-w-xl">
                        <div>
                            <label htmlFor="session_password" className="block text-sm font-medium text-gray-700">
                                Konfirmasi Kata Sandi Anda
                            </label>
                            <input
                                id="session_password"
                                type="password"
                                value={otherSessionsForm.data.password}
                                onChange={(e) => otherSessionsForm.setData('password', e.target.value)}
                                className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-hidden focus:ring-1 focus:ring-indigo-500"
                                placeholder="Masukkan kata sandi saat ini"
                                required
                            />
                            {otherSessionsForm.errors.password && (
                                <p className="mt-1 text-xs text-rose-600">{otherSessionsForm.errors.password}</p>
                            )}
                        </div>

                        <button
                            type="submit"
                            disabled={otherSessionsForm.processing}
                            className="rounded-md bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-700 focus:outline-hidden focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 disabled:opacity-50"
                        >
                            {otherSessionsForm.processing ? 'Memproses...' : 'Keluarkan dari Perangkat Lain'}
                        </button>
                    </form>
                </div>
            </div>
        </AppLayout>
    );
}
