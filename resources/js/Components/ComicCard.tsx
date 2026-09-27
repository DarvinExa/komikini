import React from 'react';
import { Link } from '@inertiajs/react';
import { ComicItem } from '@/types';

interface ComicCardProps {
    comic: ComicItem;
}

export const ComicCard: React.FC<ComicCardProps> = ({ comic }) => {
    return (
        <article className="group relative flex flex-col bg-[#161616] border-2 border-[#222222] hover:border-[#BAD306] transition-colors duration-150 rounded-none">
            <Link
                href={`/komik/${comic.slug}`}
                className="flex flex-col flex-1 focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#BAD306]"
                aria-label={`Buka detail komik ${comic.title}`}
            >
                {/* 3:4 Thumbnail Container */}
                <div className="relative aspect-[3/4] w-full overflow-hidden bg-[#111111]">
                    {comic.thumbnail_url ? (
                        <img
                            src={comic.thumbnail_url}
                            alt={`Sampul komik ${comic.title}`}
                            loading="lazy"
                            className="h-full w-full object-cover rounded-none"
                            onError={(e) => {
                                (e.target as HTMLImageElement).style.display = 'none';
                            }}
                        />
                    ) : (
                        <div className="flex h-full w-full items-center justify-center font-display text-xs text-[#777777] uppercase tracking-wider">
                            Tanpa Sampul
                        </div>
                    )}

                    {/* Origin Badge */}
                    <span className="absolute top-2 left-2 px-1.5 py-0.5 text-xs font-display tracking-wider uppercase bg-[#111111] text-[#F8F8F8] border border-[#444444] rounded-none">
                        {comic.comic_type}
                    </span>
                </div>

                {/* Comic Title */}
                <div className="p-2.5 flex flex-col flex-1 justify-between">
                    <h3
                        className="font-display text-lg text-[#F8F8F8] group-hover:text-[#BAD306] line-clamp-2 uppercase tracking-wide leading-tight transition-colors"
                        title={comic.title}
                    >
                        {comic.title}
                    </h3>
                </div>

                {/* Latest Chapter Full-width Action */}
                {comic.latest_chapter ? (
                    <span className="w-full py-1.5 px-2 bg-[#222222] group-hover:bg-[#BAD306] group-hover:text-[#111111] text-[#AAAAAA] text-xs font-display tracking-wider uppercase text-center block transition-colors border-t border-[#333333]">
                        {comic.latest_chapter}
                    </span>
                ) : (
                    <span className="w-full py-1.5 px-2 bg-[#1A1A1A] text-[#777777] text-xs font-display tracking-wider uppercase text-center block border-t border-[#222222]">
                        Detail Komik
                    </span>
                )}
            </Link>
        </article>
    );
};
