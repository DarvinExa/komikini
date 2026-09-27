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
        <AppLayout title="Lupa Kata Sandi">
            <div className="max-w-md mx-auto py-8">
                <div className="bg-white p-8 rounded-xl border border-gray-200 shadow-xs">
                    <h1 className="text-2xl font-bold tracking-tight text-gray-900 mb-2">
                        Atur Ulang Kata Sandi
                    </h1>
                    <p className="text-sm text-gray-500 mb-6">
                        Masukkan email akun Anda. Kami akan mengirimkan tautan untuk mengatur ulang kata sandi.
                    </p>

                    {status && (
                        <div className="mb-4 p-3 rounded-lg bg-emerald-50 text-emerald-700 text-sm font-medium">
                            {status}
                        </div>
                    )}

                    <form onSubmit={submit} className="space-y-4">
                        <div>
                            <label htmlFor="email" className="block text-sm font-medium text-gray-700">
                                Email
                            </label>
                            <input
                                id="email"
                                type="email"
                                name="email"
                                value={data.email}
                                autoComplete="username"
                                onChange={(e) => setData('email', e.target.value)}
                                className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-hidden focus:ring-1 focus:ring-indigo-500"
                                required
                            />
                            {errors.email && (
                                <p className="mt-1 text-xs text-rose-600">{errors.email}</p>
                            )}
                        </div>

                        <button
                            type="submit"
                            disabled={processing}
                            className="w-full justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50"
                        >
                            {processing ? 'Mengirim...' : 'Kirim Tautan Atur Ulang'}
                        </button>
                    </form>

                    <div className="mt-6 text-center text-xs text-gray-500">
                        Kembali ke{' '}
                        <Link href="/login" className="font-medium text-indigo-600 hover:text-indigo-500">
                            Halaman Masuk
                        </Link>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
