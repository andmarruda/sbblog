export type EmbedProvider = 'youtube' | 'vimeo';

/**
 * Read a YouTube or Vimeo link. Only the provider and video ID are stored, never the URL.
 */
export function parseEmbedUrl(
    input: string,
): { provider: EmbedProvider; videoId: string } | null {
    let url: URL;

    try {
        url = new URL(input.trim());
    } catch {
        return null;
    }

    const host = url.hostname.replace(/^www\./, '');
    let videoId: string | null = null;
    let provider: EmbedProvider | null = null;

    if (host === 'youtu.be') {
        provider = 'youtube';
        videoId = url.pathname.slice(1);
    } else if (
        host === 'youtube.com' ||
        host === 'm.youtube.com' ||
        host === 'youtube-nocookie.com'
    ) {
        provider = 'youtube';
        videoId =
            url.searchParams.get('v') ??
            url.pathname.match(/\/(?:embed|shorts|live)\/([^/?]+)/)?.[1] ??
            null;
    } else if (host === 'vimeo.com' || host === 'player.vimeo.com') {
        provider = 'vimeo';
        videoId = url.pathname.match(/(\d+)/)?.[1] ?? null;
    }

    return provider && videoId && /^[A-Za-z0-9_-]{1,64}$/.test(videoId)
        ? { provider, videoId }
        : null;
}

export function embedSrc(provider: EmbedProvider, videoId: string): string {
    return provider === 'youtube'
        ? `https://www.youtube-nocookie.com/embed/${videoId}`
        : `https://player.vimeo.com/video/${videoId}`;
}
