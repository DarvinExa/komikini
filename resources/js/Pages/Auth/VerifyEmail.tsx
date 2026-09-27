import React, { FormEventHandler } from 'react';
import { useForm, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

interface VerifyEmailProps {
    status?: string;
}

export default function VerifyEmail({ status }: VerifyEmailProps) {
    const { post, processing } = useForm();

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/email/verification-notification');
    };

    return (
        <AppLayout title="Verifikasi Email">
            <div className="max-w-md mx-auto py-8">
                <div className="bg-white p-8 rounded-xl border border-gray-200 shadow-xs">
                    <h1 className="text-2xl font-bold tracking-tight text-gray-900 mb-2">
                        Verifikasi Alamat Email
                    </h1>
                    <p className="text-sm text-gray-600 mb-6">
                        Terima kasih telah mendaftar! Sebelum melanjutkan, mohon periksa kotak masuk email Anda dan klik tautan verifikasi yang kami kirimkan. Jika Anda belum menerimanya, kami dapat mengirimkan tautan baru.
                    </p>

                    {status === 'verification-link-sent' && (
                        <div className="mb-4 p-3 rounded-lg bg-emerald-50 text-emerald-700 text-sm font-medium">
                            Tautan verifikasi baru telah dikirimkan ke alamat email yang Anda daftarkan.
                        </div>
                    )}

                    <form onSubmit={submit} className="flex items-center justify-between">
                        <button
                            type="submit"
                            disabled={processing}
                            className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50"
                        >
                            {processing ? 'Mengirim...' : 'Kirim Ulang Email Verifikasi'}
                        </button>

                        <Link
                            href="/logout"
                            method="post"
                            as="button"
                            className="text-sm text-gray-600 underline hover:text-gray-900"
                        >
                            Keluar
                        </Link>
                    </form>
                </div>
            </div>
        </AppLayout>
    );
}
