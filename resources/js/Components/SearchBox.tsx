import React, { useState } from 'react';
import { router } from '@inertiajs/react';

interface SearchBoxProps {
    id?: string;
    initialQuery?: string;
    placeholder?: string;
    className?: string;
    onSearch?: (query: string) => void;
}

export const SearchBox: React.FC<SearchBoxProps> = ({
    id = 'search-input',
    initialQuery = '',
    placeholder = 'Cari komik, manga, manhwa...',
    className = '',
    onSearch,
}) => {
    const [query, setQuery] = useState(initialQuery);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        const trimmed = query.trim();
        if (onSearch) {
            onSearch(trimmed);
        } else if (trimmed) {
            router.get('/search', { q: trimmed });
        }
    };

    return (
        <form onSubmit={handleSubmit} className={`relative flex items-center ${className}`} role="search">
            <label htmlFor={id} className="sr-only">
                Cari komik
            </label>
            <div className="absolute left-3 pointer-events-none text-[#AAAAAA]">
                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        strokeWidth="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                    />
                </svg>
            </div>
            <input
                id={id}
                type="search"
                value={query}
                onChange={(e) => setQuery(e.target.value)}
                placeholder={placeholder}
                maxLength={100}
                className="w-full bg-[#161616] text-[#F8F8F8] placeholder-[#777777] pl-9 pr-9 py-2 min-h-[40px] rounded-none border border-[#444444] focus:border-[#BAD306] focus:outline-none text-sm transition-colors"
            />
            {query && (
                <button
                    type="button"
                    onClick={() => setQuery('')}
                    className="absolute right-2 p-1.5 min-w-[36px] min-h-[36px] flex items-center justify-center text-[#AAAAAA] hover:text-[#F8F8F8] focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#BAD306]"
                    aria-label="Hapus kata kunci pencarian"
                >
                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            )}
        </form>
    );
};
