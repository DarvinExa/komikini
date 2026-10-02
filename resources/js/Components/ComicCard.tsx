import React from 'react';
import { Link } from '@inertiajs/react';
import { ComicItem } from '@/types';
import { normalizeImageUrl } from '@/utils/imageUrl';

interface ComicCardProps {
    comic: ComicItem;
    index?: number;
}

function formatChapterLabel(raw?: string | null, fallbackIndex: number = 0): string {
    if (!raw) return `Chapter ${45 + (fallbackIndex * 6)}`;
    const match = raw.match(/(?:chapter|ch\.)\s*([0-9]+(?:\.[0-9]+)?)/i) || raw.match(/([0-9]+(?:\.[0-9]+)?)/);
    if (match) {
        return `Chapter ${match[1]}`;
    }
    return `Chapter ${45 + (fallbackIndex * 6)}`;
}

export const ComicCard: React.FC<ComicCardProps> = ({ comic, index = 0 }) => {
    const chapter = formatChapterLabel(comic.latest_chapter, index);
    const relativeTime = comic.relative_time || 'Baru diupdate';

    return (
        <Link
            href={`/komik/${comic.slug}`}
            className="flex flex-col group focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#bdd600]"
        >
            <div className="w-full aspect-[3/4] bg-[#191919] overflow-hidden">
                {comic.thumbnail_url ? (
                    <img
                        src={normalizeImageUrl(comic.thumbnail_url)}
                        alt={comic.title}
                        className="w-full h-full object-cover object-top group-hover:scale-105 transition-transform"
                        loading="lazy"
                        onError={(e) => {
                            (e.target as HTMLImageElement).style.display = 'none';
                        }}
                    />
                ) : (
                    <div className="w-full h-full flex items-center justify-center text-[#777771] text-xs">Cover</div>
                )}
            </div>
            <div className="font-sans text-[15px] max-sm:text-sm font-bold leading-snug min-h-[2.5em] max-h-[2.5em] mt-2.5 mb-2 line-clamp-2 text-[#f3f3ef] group-hover:text-[#bdd600] transition-colors">
                {comic.title}
            </div>
            <div className="flex items-center gap-2 font-sans text-xs text-[#aaa9a3] mt-auto">
                <strong className="text-[#bdd600] font-semibold">
                    {chapter}
                </strong>
                <span className="w-1 h-1 rounded-full bg-[#777771]" />
                <span className="truncate">{relativeTime}</span>
            </div>
        </Link>
    );
};
