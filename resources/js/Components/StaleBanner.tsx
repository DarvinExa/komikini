import React from 'react';

interface StaleBannerProps {
    message?: string;
}

export const StaleBanner: React.FC<StaleBannerProps> = ({
    message = 'Penyedia upstream sedang lambat. Menampilkan data cache tersimpan.',
}) => {
    return (
        <aside
            className="mb-6 px-4 py-3 bg-[#161616] border-l-4 border-l-[#BAD306] border-y border-r border-[#444444] text-[#F8F8F8] text-xs flex items-start gap-3 rounded-none w-full max-w-full min-w-0 overflow-hidden"
            role="status"
            aria-live="polite"
        >
            <div className="text-[#BAD306] shrink-0 mt-0.5">
                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <p className="font-medium text-xs text-[#F8F8F8] flex-1 min-w-0 break-words leading-normal m-0">
                {message}
            </p>
        </aside>
    );
};
