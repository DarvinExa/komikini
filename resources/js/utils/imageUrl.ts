/**
 * Normalizes and proxies comic cover thumbnails and chapter image URLs
 * through same-origin /img-proxy. This completely eliminates:
 * 1. ISP DNS64 synthesis of local ULA IP addresses (fd00::/8)
 * 2. Chrome Private Network Access (PNA) CORS blocks
 * 3. Third-party CDN hotlink blocking
 */
export function normalizeImageUrl(url?: string | null): string {
    if (!url) return '';

    // If already proxied or relative, return as is
    if (url.startsWith('/img-proxy') || url.startsWith('/')) {
        return url;
    }

    // Normalize known upstream domains first
    const normalized = url
        .replace('thumbnail.komiku.to', 'thumbnail.komiku.org')
        .replace(/https?:\/\/(?:img|image\d*)\.komiku\.to\//i, 'https://img.komiku.org/');

    return `/img-proxy?url=${encodeURIComponent(normalized)}`;
}
