import React from 'react';
import { Head, usePage } from '@inertiajs/react';
import { PageProps } from '@/types';

export interface SeoHeadProps {
    title?: string;
    description?: string;
    canonical?: string;
    ogImage?: string | null;
    ogType?: 'website' | 'article' | 'book';
    noIndex?: boolean;
    jsonLd?: Record<string, unknown> | Array<Record<string, unknown>> | null;
}

export const SeoHead: React.FC<SeoHeadProps> = ({
    title,
    description = 'Platform baca komik manga, manhwa, dan manhua bahasa Indonesia tercepat, terlengkap, dan tanpa gangguan.',
    canonical,
    ogImage,
    ogType = 'website',
    noIndex = false,
    jsonLd,
}) => {
    const { appName } = usePage<PageProps>().props;
    const fullTitle = title ? `${title} - ${appName}` : `${appName} - Baca Manga, Manhwa & Manhua Online Bahasa Indonesia`;

    // Compute canonical URL if not provided
    const canonicalUrl = canonical || (typeof window !== 'undefined' ? window.location.origin + window.location.pathname : undefined);

    return (
        <Head>
            <title>{fullTitle}</title>
            <meta name="description" content={description} />
            {canonicalUrl && <link rel="canonical" href={canonicalUrl} />}

            {/* Robots */}
            {noIndex && <meta name="robots" content="noindex, nofollow" />}

            {/* Open Graph */}
            <meta property="og:title" content={fullTitle} />
            <meta property="og:description" content={description} />
            <meta property="og:type" content={ogType} />
            {canonicalUrl && <meta property="og:url" content={canonicalUrl} />}
            <meta property="og:site_name" content={appName} />
            {ogImage && <meta property="og:image" content={ogImage} />}

            {/* Twitter Card */}
            <meta name="twitter:card" content={ogImage ? 'summary_large_image' : 'summary'} />
            <meta name="twitter:title" content={fullTitle} />
            <meta name="twitter:description" content={description} />
            {ogImage && <meta name="twitter:image" content={ogImage} />}

            {/* Structured Data (JSON-LD) */}
            {jsonLd && (
                <script type="application/ld+json">
                    {JSON.stringify(jsonLd)}
                </script>
            )}
        </Head>
    );
};

export default SeoHead;
