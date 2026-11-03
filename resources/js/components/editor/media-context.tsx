import { createContext, useContext } from 'react';

export type MediaUrls = Record<string, { url: string; alt: string | null }>;

/**
 * URLs of the media referenced by the document, keyed by media ID. Image nodes only store the ID.
 */
export const MediaUrlsContext = createContext<MediaUrls>({});

export function useMediaUrl(mediaId: string | null) {
    const urls = useContext(MediaUrlsContext);

    return mediaId ? urls[mediaId] : undefined;
}
