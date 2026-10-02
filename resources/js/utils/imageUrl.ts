/**
 * Normalizes comic cover thumbnails and chapter image URLs to avoid
 * ISP DNS64 synthesis of local ULA IP addresses (fd00::/8) which triggers
 * Chrome's Private Network Access (PNA) CORS block on https://komikini.my.id.
 */
export function normalizeImageUrl(url?: string | null): string {
    if (!url) return '';

    return url
        .replace('thumbnail.komiku.to', 'thumbnail.komiku.org')
        .replace(/https?:\/\/(?:img|image\d*)\.komiku\.to\//i, 'https://img.komiku.org/');
}
