import React from 'react';

interface ErrorStateProps {
    title?: string;
    message?: string;
    onRetry?: () => void;
}

export const ErrorState: React.FC<ErrorStateProps> = ({
    title = 'Terjadi Gangguan Layanan',
    message = 'Gagal memuat konten dari penyedia komik. Silakan periksa koneksi Anda dan coba lagi.',
    onRetry,
}) => {
    return (
        <div className="flex flex-col items-center justify-center p-8 text-center bg-[#161616] border border-[#E56458] max-w-md mx-auto my-8 rounded-none">
            <div className="w-12 h-12 mb-4 bg-[#111111] border border-[#444444] flex items-center justify-center text-[#E56458]">
                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        strokeWidth="1.5"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                    />
                </svg>
            </div>
            <h3 className="font-display text-2xl uppercase tracking-wider text-[#E56458] mb-1">{title}</h3>
            <p className="text-sm text-[#AAAAAA] mb-5">{message}</p>
            {onRetry && (
                <button
                    type="button"
                    onClick={onRetry}
                    className="inline-flex items-center justify-center px-5 py-2.5 font-display text-sm tracking-wider uppercase text-[#F8F8F8] bg-[#111111] border border-[#444444] hover:border-[#BAD306] hover:text-[#BAD306] transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#BAD306] rounded-none"
                >
                    Muat Ulang
                </button>
            )}
        </div>
    );
};
