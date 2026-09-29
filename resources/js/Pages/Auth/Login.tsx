import React, { FormEventHandler } from 'react';
import { useForm, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

interface LoginProps {
    status?: string;
}

export default function Login({ status }: LoginProps) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/login', {
            onFinish: () => reset('password'),
        });
    };

    return (
        <AppLayout title="Masuk ke Akun" noIndex={true}>
            <div className="max-w-md mx-auto py-8">
                <div className="bg-[#161616] p-6 sm:p-8 border-2 border-[#222222] rounded-none">
                    <h1 className="font-display text-3xl uppercase tracking-wider text-[#F8F8F8] mb-2">
                        Masuk ke Akun
                    </h1>
                    <p className="text-xs text-[#AAAAAA] mb-6">
                        Masuk untuk melanjutkan bacaan dan mengakses fitur akun Anda.
                    </p>

                    {status && (
                        <div className="mb-4 p-3 bg-[#BAD306]/10 border border-[#BAD306] text-[#BAD306] text-xs font-mono" role="status" aria-live="polite">
                            [OK] {status}
                        </div>
                    )}

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
                            <div className="flex items-center justify-between mb-1">
                                <label htmlFor="password" className="block text-xs font-display tracking-wider uppercase text-[#AAAAAA]">
                                    Kata Sandi
                                </label>
                                <Link
                                    href="/forgot-password"
                                    className="text-xs text-[#AAAAAA] hover:text-[#BAD306] transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#BAD306]"
                                >
                                    Lupa kata sandi?
                                </Link>
                            </div>
                            <input
                                id="password"
                                type="password"
                                name="password"
                                value={data.password}
                                autoComplete="current-password"
                                onChange={(e) => setData('password', e.target.value)}
                                aria-describedby={errors.password ? 'password-error' : undefined}
                                className="w-full bg-[#111111] text-sm text-[#F8F8F8] px-3.5 py-2.5 rounded-none border border-[#444444] focus:border-[#BAD306] focus:outline-none transition-colors"
                                required
                            />
                            {errors.password && (
                                <p id="password-error" className="mt-1 text-xs text-[#E56458] font-mono">{errors.password}</p>
                            )}
                        </div>

                        <div className="flex items-center">
                            <input
                                id="remember"
                                type="checkbox"
                                name="remember"
                                checked={data.remember}
                                onChange={(e) => setData('remember', e.target.checked)}
                                className="h-4 w-4 rounded-none bg-[#111111] border-[#444444] text-[#BAD306] focus:ring-[#BAD306] focus:ring-offset-0"
                            />
                            <label htmlFor="remember" className="ml-2 block text-xs text-[#AAAAAA]">
                                Ingat sesi saya di perangkat ini
                            </label>
                        </div>

                        <button
                            type="submit"
                            disabled={processing}
                            className="w-full py-3 min-h-[44px] font-display text-sm tracking-wider uppercase text-[#111111] bg-[#BAD306] hover:bg-[#E0FF00] border-2 border-[#BAD306] font-bold rounded-none transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#BAD306] disabled:opacity-50"
                        >
                            {processing ? 'Memproses...' : 'Masuk'}
                        </button>
                    </form>

                    <div className="mt-6 pt-4 border-t border-[#222222] text-center text-xs text-[#AAAAAA]">
                        Belum punya akun?{' '}
                        <Link href="/register" className="font-bold text-[#BAD306] hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#BAD306]">
                            Daftar sekarang
                        </Link>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
