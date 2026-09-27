import React from 'react';
import { Link } from '@inertiajs/react';

interface PaginationProps {
    currentPage: number;
    hasNextPage: boolean;
    hasPrevPage: boolean;
    totalPages?: number | null;
    baseUrl?: string;
    queryParams?: Record<string, string | number>;
}

export const Pagination: React.FC<PaginationProps> = ({
    currentPage,
    hasNextPage,
    hasPrevPage,
    totalPages,
    baseUrl = typeof window !== 'undefined' ? window.location.pathname : '',
    queryParams = {},
}) => {
    const buildUrl = (page: number) => {
        const params = new URLSearchParams();
        Object.entries(queryParams).forEach(([k, v]) => {
            if (k !== 'page' && v !== undefined && v !== '') {
                params.set(k, String(v));
            }
        });
        params.set('page', String(page));
        return `${baseUrl}?${params.toString()}`;
    };

    if (!hasPrevPage && !hasNextPage) {
        return null;
    }

    return (
        <nav className="flex items-center justify-center gap-3 py-6" aria-label="Paginasi">
            {hasPrevPage ? (
                <Link
                    href={buildUrl(currentPage - 1)}
                    className="px-4 py-2 font-display text-sm tracking-wider uppercase text-[#F8F8F8] bg-[#161616] border border-[#444444] hover:border-[#BAD306] hover:text-[#BAD306] transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#BAD306] rounded-none"
                    aria-label="Halaman sebelumnya"
                >
                    Sebelumnya
                </Link>
            ) : (
                <span
                    className="px-4 py-2 font-display text-sm tracking-wider uppercase text-[#777777] bg-[#111111] border border-[#222222] cursor-not-allowed rounded-none"
                    aria-disabled="true"
                >
                    Sebelumnya
                </span>
            )}

            <span className="font-display text-xs tracking-wider uppercase text-[#AAAAAA] px-2">
                Halaman {currentPage} {totalPages ? `dari ${totalPages}` : ''}
            </span>

            {hasNextPage ? (
                <Link
                    href={buildUrl(currentPage + 1)}
                    className="px-4 py-2 font-display text-sm tracking-wider uppercase text-[#F8F8F8] bg-[#161616] border border-[#444444] hover:border-[#BAD306] hover:text-[#BAD306] transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#BAD306] rounded-none"
                    aria-label="Halaman selanjutnya"
                >
                    Selanjutnya
                </Link>
            ) : (
                <span
                    className="px-4 py-2 font-display text-sm tracking-wider uppercase text-[#777777] bg-[#111111] border border-[#222222] cursor-not-allowed rounded-none"
                    aria-disabled="true"
                >
                    Selanjutnya
                </span>
            )}
        </nav>
    );
};
