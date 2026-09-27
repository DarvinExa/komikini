import React from 'react';
import { Link } from '@inertiajs/react';

interface EmptyStateProps {
    title?: string;
    description?: string;
    actionHref?: string;
    actionLabel?: string;
}

export const EmptyState: React.FC<EmptyStateProps> = ({
    title = 'Tidak ada hasil ditemukan',
    description = 'Coba kata kunci lain atau jelajahi katalog komik.',
    actionHref = '/',
    actionLabel = 'Kembali ke Beranda',
}) => {
    return (
        <div className="flex flex-col items-center justify-center p-8 sm:p-12 text-center bg-[#161616] border border-[#444444] max-w-md mx-auto my-8 rounded-none">
            <div className="w-12 h-12 mb-4 bg-[#111111] border border-[#222222] flex items-center justify-center text-[#AAAAAA]">
                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        strokeWidth="1.5"
                        d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                    />
                </svg>
            </div>
            <h3 className="font-display text-2xl uppercase tracking-wider text-[#F8F8F8] mb-1">{title}</h3>
            <p className="text-sm text-[#AAAAAA] mb-6">{description}</p>
            {actionHref && (
                <Link
                    href={actionHref}
                    className="inline-flex items-center justify-center px-5 py-2.5 font-display text-sm tracking-wider uppercase text-[#111111] bg-[#BAD306] hover:bg-[#E0FF00] border-2 border-[#BAD306] transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#BAD306] rounded-none"
                >
                    {actionLabel}
                </Link>
            )}
        </div>
    );
};
