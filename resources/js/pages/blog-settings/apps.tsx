import { router } from '@inertiajs/react';
import { Plug } from 'lucide-react';
import { ConfirmDialog } from '@/components/app/confirm-dialog';
import { EmptyState } from '@/components/app/empty-state';
import { PageTitle } from '@/components/app/page-title';
import { SettingsSection } from '@/components/app/settings-section';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useBlogRoute } from '@/hooks/use-blog-route';
import { useTranslation } from '@/hooks/use-translation';
import { destroy } from '@/routes/blogs/settings/apps';

type Props = {
    installations: {
        id: string;
        app: string | null;
        first_party: boolean;
        scopes: string[];
        installed_by: string | null;
        created_at: string | null;
    }[];
};

export default function BlogApps({ installations }: Props) {
    const { t, formatDate } = useTranslation();
    const args = useBlogRoute();

    return (
        <>
            <PageTitle title={t('Apps')} />
            <SettingsSection
                title={t('Apps')}
                description={t(
                    'Apps connected to this blog. They can read and suggest, never publish.',
                )}
            >
                {installations.length === 0 ? (
                    <EmptyState icon={Plug} title={t('No apps connected')} />
                ) : (
                    <ul className="divide-y rounded-lg border">
                        {installations.map((installation) => (
                            <li
                                key={installation.id}
                                className="flex flex-wrap items-center gap-3 p-3"
                            >
                                <div className="min-w-0 flex-1">
                                    <p className="font-medium">
                                        {installation.app}
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        {t('Connected by :name on :date', {
                                            name:
                                                installation.installed_by ??
                                                '—',
                                            date: formatDate(
                                                installation.created_at,
                                            ),
                                        })}
                                    </p>
                                    <div className="mt-1 flex flex-wrap gap-1">
                                        {installation.scopes.map((scope) => (
                                            <Badge
                                                key={scope}
                                                variant="outline"
                                            >
                                                {scope}
                                            </Badge>
                                        ))}
                                    </div>
                                </div>
                                <ConfirmDialog
                                    trigger={
                                        <Button variant="outline" size="sm">
                                            {t('Disconnect')}
                                        </Button>
                                    }
                                    title={t('Disconnect :name?', {
                                        name: installation.app ?? '',
                                    })}
                                    description={t('Its access stops at once.')}
                                    confirmLabel={t('Disconnect')}
                                    destructive
                                    onConfirm={() =>
                                        router.delete(
                                            destroy({
                                                ...args,
                                                appInstallation:
                                                    installation.id,
                                            }),
                                            { preserveScroll: true },
                                        )
                                    }
                                />
                            </li>
                        ))}
                    </ul>
                )}
            </SettingsSection>
        </>
    );
}

BlogApps.layout = {
    breadcrumbs: [{ title: 'Blog settings' }, { title: 'Apps' }],
};
