import React, { FormEventHandler } from 'react';
import { useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

export default function ConfirmPassword() {
    const { data, setData, post, processing, errors, reset } = useForm({
        password: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/confirm-password', {
            onFinish: () => reset('password'),
        });
    };

    return (
        <AppLayout title="Konfirmasi Kata Sandi">
            <div className="max-w-md mx-auto py-8">
                <div className="bg-white p-8 rounded-xl border border-gray-200 shadow-xs">
                    <h1 className="text-2xl font-bold tracking-tight text-gray-900 mb-2">
                        Area Aman
                    </h1>
                    <p className="text-sm text-gray-500 mb-6">
                        Ini adalah area yang dilindungi. Harap konfirmasi kata sandi Anda sebelum melanjutkan.
                    </p>

                    <form onSubmit={submit} className="space-y-4">
                        <div>
                            <label htmlFor="password" className="block text-sm font-medium text-gray-700">
                                Kata Sandi
                            </label>
                            <input
                                id="password"
                                type="password"
                                name="password"
                                value={data.password}
                                autoComplete="current-password"
                                onChange={(e) => setData('password', e.target.value)}
                                className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-hidden focus:ring-1 focus:ring-indigo-500"
                                required
                            />
                            {errors.password && (
                                <p className="mt-1 text-xs text-rose-600">{errors.password}</p>
                            )}
                        </div>

                        <button
                            type="submit"
                            disabled={processing}
                            className="w-full justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50"
                        >
                            {processing ? 'Memverifikasi...' : 'Konfirmasi'}
                        </button>
                    </form>
                </div>
            </div>
        </AppLayout>
    );
}
