import React, { FormEventHandler } from 'react';
import { useForm, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

interface ForgotPasswordProps {
    status?: string;
}

export default function ForgotPassword({ status }: ForgotPasswordProps) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/forgot-password');
    };

    return (
        <AppLayout title="Lupa Kata Sandi" noIndex={true}>
            <div className="max-w-md mx-auto py-8">
                <div className="bg-[#161616] p-6 sm:p-8 border-2 border-[#222222] rounded-none">
                    <h1 className="font-display text-3xl uppercase tracking-wider text-[#F8F8F8] mb-2">
                        Atur Ulang Kata Sandi
                    </h1>
                    <p className="text-xs text-[#AAAAAA] mb-6">
                        Masukkan email akun Anda. Kami akan mengirimkan tautan untuk mengatur ulang kata sandi.
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

                        <button
                            type="submit"
                            disabled={processing}
                            className="w-full py-3 min-h-[44px] font-display text-sm tracking-wider uppercase text-[#111111] bg-[#BAD306] hover:bg-[#E0FF00] border-2 border-[#BAD306] font-bold rounded-none transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#BAD306] disabled:opacity-50"
                        >
                            {processing ? 'Mengirim...' : 'Kirim Tautan Atur Ulang'}
                        </button>
                    </form>

                    <div className="mt-6 pt-4 border-t border-[#222222] text-center text-xs text-[#AAAAAA]">
                        Kembali ke{' '}
                        <Link href="/login" className="font-bold text-[#BAD306] hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#BAD306]">
                            Halaman Masuk
                        </Link>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
