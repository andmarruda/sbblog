import { usePage } from '@inertiajs/react';
import { useCallback, useMemo } from 'react';
import {
    translate,
    translateChoice,
    type Replacements,
} from '@/i18n/translator';

export function useTranslation() {
    const { locale } = usePage().props;

    const t = useCallback(
        (key: string, replacements?: Replacements) =>
            translate(locale, key, replacements),
        [locale],
    );

    const tc = useCallback(
        (key: string, count: number, replacements?: Replacements) =>
            translateChoice(locale, key, count, replacements),
        [locale],
    );

    const formatters = useMemo(
        () => ({
            date: new Intl.DateTimeFormat(locale, { dateStyle: 'medium' }),
            dateTime: new Intl.DateTimeFormat(locale, {
                dateStyle: 'medium',
                timeStyle: 'short',
            }),
            number: new Intl.NumberFormat(locale),
        }),
        [locale],
    );

    const formatDate = useCallback(
        (value: string | Date | null, withTime = false) =>
            value === null
                ? ''
                : (withTime ? formatters.dateTime : formatters.date).format(
                      new Date(value),
                  ),
        [formatters],
    );

    const formatNumber = useCallback(
        (value: number) => formatters.number.format(value),
        [formatters],
    );

    return { t, tc, locale, formatDate, formatNumber };
}
