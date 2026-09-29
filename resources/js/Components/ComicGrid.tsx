import React from 'react';
import { ComicItem } from '@/types';
import { ComicCard } from './ComicCard';

interface ComicGridProps {
    comics: ComicItem[];
    emptyMessage?: string;
}

export const ComicGrid: React.FC<ComicGridProps> = ({
    comics,
    emptyMessage = 'Tidak ada komik yang dapat ditampilkan.',
}) => {
    if (comics.length === 0) {
        return (
            <div className="py-12 text-center text-[#aaa9a3] bg-[#191919] border border-[#303030]">
                <p className="text-sm">{emptyMessage}</p>
            </div>
        );
    }

    return (
        <div className="grid grid-cols-6 max-lg:grid-cols-4 max-sm:grid-cols-2 gap-4 max-sm:gap-x-3.5 max-sm:gap-y-[30px]">
            {comics.map((comic, idx) => (
                <ComicCard key={comic.slug} comic={comic} index={idx} />
            ))}
        </div>
    );
};
