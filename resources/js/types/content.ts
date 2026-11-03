export type MediaItem = {
    id: string;
    url: string;
    thumb_url: string;
    mime_type: string;
    size_bytes: number;
    width: number | null;
    height: number | null;
    alt_text: string | null;
    uploaded_by: string | null;
    created_at: string | null;
};

export type ArticleStatus =
    | 'draft'
    | 'in_review'
    | 'scheduled'
    | 'published'
    | 'archived';

export type TiptapDocument = {
    type: 'doc';
    content: Record<string, unknown>[];
};

export type Option = { id: string; name: string };
