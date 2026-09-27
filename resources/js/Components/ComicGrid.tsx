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
            <div className="py-12 text-center text-[#AAAAAA] bg-[#161616] border border-[#222222] rounded-none">
                <p className="text-sm">{emptyMessage}</p>
            </div>
        );
    }

    return (
        <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3 sm:gap-4">
            {comics.map((comic) => (
                <ComicCard key={comic.slug} comic={comic} />
            ))}
        </div>
    );
};
