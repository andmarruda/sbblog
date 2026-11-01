type Messages = Record<string, string>;
export type Replacements = Record<string, string | number>;

// Laravel's JSON translation files (lang/{locale}.json) are the single source of truth for
// both PHP and React. Keys are the English source strings.
const bundles = import.meta.glob<Messages>('../../../lang/*.json', {
    eager: true,
    import: 'default',
});

const messages: Record<string, Messages> = Object.fromEntries(
    Object.entries(bundles).map(([path, bundle]) => [
        path.replace(/^.*\/([^/]+)\.json$/, '$1'),
        bundle,
    ]),
);

function replace(line: string, replacements: Replacements): string {
    return Object.entries(replacements)
        .sort(([a], [b]) => b.length - a.length)
        .reduce(
            (result, [key, value]) =>
                result.replaceAll(`:${key}`, String(value)),
            line,
        );
}

export function translate(
    locale: string,
    key: string,
    replacements: Replacements = {},
): string {
    return replace(messages[locale]?.[key] ?? key, replacements);
}

/**
 * Pluralization compatible with Laravel's trans_choice():
 * "{0} None|{1} One|[2,*] :count items" or "One item|:count items".
 */
export function translateChoice(
    locale: string,
    key: string,
    count: number,
    replacements: Replacements = {},
): string {
    const segments = (messages[locale]?.[key] ?? key).split('|');
    const values = { count, ...replacements };

    for (const segment of segments) {
        const match = segment.match(
            /^\s*(\{(\d+)\}|\[(\d+),(\d+|\*)\])\s*(.*)$/s,
        );

        if (!match) {
            continue;
        }

        const [, , exact, min, max, text] = match;
        const matches =
            exact !== undefined
                ? count === Number(exact)
                : count >= Number(min) && (max === '*' || count <= Number(max));

        if (matches) {
            return replace(text, values);
        }
    }

    const plain = segments.map((segment) =>
        segment.replace(/^\s*(\{\d+\}|\[\d+,(\d+|\*)\])\s*/, ''),
    );
    const line =
        count === 1 || plain.length === 1 ? plain[0] : plain[plain.length - 1];

    return replace(line, values);
}
