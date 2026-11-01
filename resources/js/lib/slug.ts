/**
 * URL slug: lowercase ASCII, accents removed, words joined by dashes.
 * Mirrors Laravel's Str::slug() closely enough for previews; the server re-slugs.
 */
export function slugify(value: string): string {
    return value
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
}
