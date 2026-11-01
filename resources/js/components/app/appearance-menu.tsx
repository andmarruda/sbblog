import { Monitor, Moon, Sun } from 'lucide-react';
import {
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
} from '@/components/ui/dropdown-menu';
import { useAppearance, type Appearance } from '@/hooks/use-appearance';
import { useTranslation } from '@/hooks/use-translation';

export const appearanceOptions = [
    { value: 'light', label: 'Light', icon: Sun },
    { value: 'dark', label: 'Dark', icon: Moon },
    { value: 'system', label: 'System', icon: Monitor },
] as const;

/**
 * Radio items to place inside any dropdown menu.
 */
export function AppearanceMenuItems() {
    const { t } = useTranslation();
    const { appearance, updateAppearance } = useAppearance();

    return (
        <DropdownMenuRadioGroup
            value={appearance}
            onValueChange={(value) => updateAppearance(value as Appearance)}
        >
            {appearanceOptions.map(({ value, label, icon: Icon }) => (
                <DropdownMenuRadioItem key={value} value={value}>
                    <Icon />
                    {t(label)}
                </DropdownMenuRadioItem>
            ))}
        </DropdownMenuRadioGroup>
    );
}
