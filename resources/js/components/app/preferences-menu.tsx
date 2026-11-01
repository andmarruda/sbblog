import { Languages, Palette } from 'lucide-react';
import { AppearanceMenuItems } from '@/components/app/appearance-menu';
import { LocaleMenuItems } from '@/components/app/locale-menu';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useTranslation } from '@/hooks/use-translation';

/**
 * Language and theme picker for pages without the user menu (auth, errors).
 */
export function PreferencesMenu() {
    const { t } = useTranslation();

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    aria-label={t('Preferences')}
                >
                    <Languages />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-48">
                <DropdownMenuLabel className="flex items-center gap-2">
                    <Languages className="size-4" />
                    {t('Language')}
                </DropdownMenuLabel>
                <LocaleMenuItems />
                <DropdownMenuSeparator />
                <DropdownMenuLabel className="flex items-center gap-2">
                    <Palette className="size-4" />
                    {t('Appearance')}
                </DropdownMenuLabel>
                <AppearanceMenuItems />
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
