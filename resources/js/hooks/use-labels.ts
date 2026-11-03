import { useMemo } from 'react';
import { useTranslation } from '@/hooks/use-translation';

/**
 * Translated labels for enum values sent by the server.
 */
export function useLabels() {
    const { t } = useTranslation();

    return useMemo(
        () => ({
            workspaceRole: {
                owner: t('Owner'),
                admin: t('Admin'),
                member: t('Member'),
            } as Record<string, string>,
            role: {
                owner: t('Owner'),
                admin: t('Admin'),
                editor: t('Editor'),
                author: t('Author'),
                reviewer: t('Reviewer'),
                designer: t('Designer'),
                viewer: t('Viewer'),
            } as Record<string, string>,
            twoFactorPolicy: {
                off: t('Off'),
                optional: t('Optional'),
                required_by_role: t('Required for some roles'),
                required_all: t('Required for everyone'),
            } as Record<string, string>,
            commentPolicy: {
                disabled: t('Disabled'),
                moderated: t('Moderated'),
                open: t('Open'),
            } as Record<string, string>,
        }),
        [t],
    );
}
