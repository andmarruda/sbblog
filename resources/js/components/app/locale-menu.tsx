import { router, usePage } from '@inertiajs/react';
import {
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
} from '@/components/ui/dropdown-menu';
import { update } from '@/routes/locale';

export function localeName(locale: string) {
    // Each language is named in itself, so users can always find their own.
    const name =
        new Intl.DisplayNames([locale], { type: 'language' }).of(locale) ??
        locale;

    return name.charAt(0).toLocaleUpperCase(locale) + name.slice(1);
}

/**
 * Radio items to place inside any dropdown menu.
 */
export function LocaleMenuItems() {
    const { locale, locales } = usePage().props;

    return (
        <DropdownMenuRadioGroup
            value={locale}
            onValueChange={(value) =>
                router.visit(update(), {
                    data: { locale: value },
                    preserveScroll: true,
                })
            }
        >
            {locales.map((code) => (
                <DropdownMenuRadioItem key={code} value={code}>
                    {localeName(code)}
                </DropdownMenuRadioItem>
            ))}
        </DropdownMenuRadioGroup>
    );
}
