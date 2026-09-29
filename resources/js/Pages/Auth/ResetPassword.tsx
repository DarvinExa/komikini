import React, { FormEventHandler } from 'react';
import { useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

interface ResetPasswordProps {
    token: string;
    email: string;
}

export default function ResetPassword({ token, email }: ResetPasswordProps) {
    const { data, setData, post, processing, errors, reset } = useForm({
        token: token,
        email: email,
        password: '',
        password_confirmation: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/reset-password', {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <AppLayout title="Atur Ulang Kata Sandi" noIndex={true}>
            <div className="max-w-md mx-auto py-8">
                <div className="bg-[#161616] p-6 sm:p-8 border-2 border-[#222222] rounded-none">
                    <h1 className="font-display text-3xl uppercase tracking-wider text-[#F8F8F8] mb-2">
                        Perbarui Kata Sandi
                    </h1>
                    <p className="text-xs text-[#AAAAAA] mb-6">
                        Silakan buat kata sandi baru untuk akun Anda.
                    </p>

                    <form onSubmit={submit} className="space-y-4">
                        <div>
                            <label htmlFor="email" className="block text-xs font-display tracking-wider uppercase text-[#AAAAAA] mb-1">
                                Alamat Email
                            </label>
                            <input
                                id="email"
                                type="email"
                                name="email"
                                value={data.email}
                                autoComplete="username"
                                onChange={(e) => setData('email', e.target.value)}
                                aria-describedby={errors.email ? 'email-error' : undefined}
                                className="w-full bg-[#111111] text-sm text-[#F8F8F8] px-3.5 py-2.5 rounded-none border border-[#444444] focus:border-[#BAD306] focus:outline-none transition-colors"
                                required
                            />
                            {errors.email && (
                                <p id="email-error" className="mt-1 text-xs text-[#E56458] font-mono">{errors.email}</p>
                            )}
                        </div>

                        <div>
                            <label htmlFor="password" className="block text-xs font-display tracking-wider uppercase text-[#AAAAAA] mb-1">
                                Kata Sandi Baru
                            </label>
                            <input
                                id="password"
                                type="password"
                                name="password"
                                value={data.password}
                                autoComplete="new-password"
                                onChange={(e) => setData('password', e.target.value)}
                                aria-describedby={errors.password ? 'password-error' : undefined}
                                className="w-full bg-[#111111] text-sm text-[#F8F8F8] px-3.5 py-2.5 rounded-none border border-[#444444] focus:border-[#BAD306] focus:outline-none transition-colors"
                                required
                            />
                            {errors.password && (
                                <p id="password-error" className="mt-1 text-xs text-[#E56458] font-mono">{errors.password}</p>
                            )}
                        </div>

                        <div>
                            <label htmlFor="password_confirmation" className="block text-xs font-display tracking-wider uppercase text-[#AAAAAA] mb-1">
                                Konfirmasi Kata Sandi Baru
                            </label>
                            <input
                                id="password_confirmation"
                                type="password"
                                name="password_confirmation"
                                value={data.password_confirmation}
                                autoComplete="new-password"
                                onChange={(e) => setData('password_confirmation', e.target.value)}
                                aria-describedby={errors.password_confirmation ? 'password-confirmation-error' : undefined}
                                className="w-full bg-[#111111] text-sm text-[#F8F8F8] px-3.5 py-2.5 rounded-none border border-[#444444] focus:border-[#BAD306] focus:outline-none transition-colors"
                                required
                            />
                            {errors.password_confirmation && (
                                <p id="password-confirmation-error" className="mt-1 text-xs text-[#E56458] font-mono">{errors.password_confirmation}</p>
                            )}
                        </div>

                        <button
                            type="submit"
                            disabled={processing}
                            className="w-full py-3 min-h-[44px] font-display text-sm tracking-wider uppercase text-[#111111] bg-[#BAD306] hover:bg-[#E0FF00] border-2 border-[#BAD306] font-bold rounded-none transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#BAD306] disabled:opacity-50"
                        >
                            {processing ? 'Memperbarui...' : 'Simpan Kata Sandi Baru'}
                        </button>
                    </form>
                </div>
            </div>
        </AppLayout>
    );
}
