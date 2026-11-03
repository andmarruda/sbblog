import { router } from '@inertiajs/react';
import { Check, Palette } from 'lucide-react';
import { useState } from 'react';
import { ConfirmDialog } from '@/components/app/confirm-dialog';
import { EmptyState } from '@/components/app/empty-state';
import { FormField } from '@/components/app/form-field';
import { PageTitle } from '@/components/app/page-title';
import { SettingsSection } from '@/components/app/settings-section';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { useBlogRoute } from '@/hooks/use-blog-route';
import { useTranslation } from '@/hooks/use-translation';
import {
    activate,
    destroy,
    store,
    update,
    upgrade,
} from '@/routes/blogs/settings/themes';

type Field = {
    key: string;
    type: 'color' | 'select' | 'boolean' | 'text';
    label: string;
    group?: string;
    options?: string[];
};

type ConfigValue = string | boolean;

type Installed = {
    id: string;
    name: string;
    version: string;
    latest_version: string;
    is_active: boolean;
    fields: Field[];
    config: Record<string, ConfigValue>;
};

type Props = {
    catalog: {
        id: string;
        slug: string;
        name: string;
        description: string | null;
        preview_url: string | null;
        package_name: string;
        latest_version: string;
        installed: boolean;
    }[];
    installed: Installed[];
};

export default function BlogThemes({ catalog, installed }: Props) {
    const { t } = useTranslation();
    const args = useBlogRoute();
    const options = { preserveScroll: true };

    return (
        <>
            <PageTitle title={t('Theme')} />
            <div className="max-w-3xl space-y-10">
                <SettingsSection
                    title={t('Installed themes')}
                    description={t(
                        'The active theme is sent to your frontends with its settings.',
                    )}
                >
                    {installed.length === 0 ? (
                        <EmptyState
                            icon={Palette}
                            title={t('No theme installed')}
                            description={t(
                                'Install one from the catalog below. Until then, frontends use their own default.',
                            )}
                        />
                    ) : (
                        <div className="space-y-6">
                            {installed.map((item) => (
                                <div
                                    key={item.id}
                                    className="space-y-4 rounded-lg border p-4"
                                >
                                    <div className="flex flex-wrap items-center gap-2">
                                        <p className="font-medium">
                                            {item.name}
                                        </p>
                                        <Badge variant="outline">
                                            v{item.version}
                                        </Badge>
                                        {item.is_active && (
                                            <Badge>
                                                <Check /> {t('Active')}
                                            </Badge>
                                        )}
                                        <div className="ml-auto flex flex-wrap gap-2">
                                            {item.version !==
                                                item.latest_version && (
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() =>
                                                        router.post(
                                                            upgrade({
                                                                ...args,
                                                                theme: item.id,
                                                            }),
                                                            {},
                                                            options,
                                                        )
                                                    }
                                                >
                                                    {t('Update to :version', {
                                                        version:
                                                            item.latest_version,
                                                    })}
                                                </Button>
                                            )}
                                            {!item.is_active && (
                                                <>
                                                    <Button
                                                        size="sm"
                                                        onClick={() =>
                                                            router.post(
                                                                activate({
                                                                    ...args,
                                                                    theme: item.id,
                                                                }),
                                                                {},
                                                                options,
                                                            )
                                                        }
                                                    >
                                                        {t('Activate')}
                                                    </Button>
                                                    <ConfirmDialog
                                                        trigger={
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                            >
                                                                {t('Remove')}
                                                            </Button>
                                                        }
                                                        title={t(
                                                            'Remove :name?',
                                                            {
                                                                name: item.name,
                                                            },
                                                        )}
                                                        description={t(
                                                            'Its settings are lost.',
                                                        )}
                                                        confirmLabel={t(
                                                            'Remove',
                                                        )}
                                                        destructive
                                                        onConfirm={() =>
                                                            router.delete(
                                                                destroy({
                                                                    ...args,
                                                                    theme: item.id,
                                                                }),
                                                                options,
                                                            )
                                                        }
                                                    />
                                                </>
                                            )}
                                        </div>
                                    </div>
                                    <ThemeSettings installed={item} />
                                </div>
                            ))}
                        </div>
                    )}
                </SettingsSection>

                <SettingsSection
                    title={t('Catalog')}
                    description={t('Free themes. Premium themes come later.')}
                >
                    <div className="grid gap-4 sm:grid-cols-2">
                        {catalog.map((theme) => (
                            <div
                                key={theme.id}
                                className="flex flex-col overflow-hidden rounded-lg border"
                            >
                                {theme.preview_url ? (
                                    <img
                                        src={theme.preview_url}
                                        alt=""
                                        className="aspect-video w-full object-cover"
                                    />
                                ) : (
                                    <div className="flex aspect-video items-center justify-center bg-muted text-muted-foreground">
                                        <Palette className="size-8" />
                                    </div>
                                )}
                                <div className="flex flex-1 flex-col gap-2 p-4">
                                    <p className="font-medium">{theme.name}</p>
                                    {theme.description && (
                                        <p className="text-sm text-muted-foreground">
                                            {theme.description}
                                        </p>
                                    )}
                                    <p className="font-mono text-xs text-muted-foreground">
                                        {theme.package_name}@
                                        {theme.latest_version}
                                    </p>
                                    <div className="mt-auto pt-2">
                                        {theme.installed ? (
                                            <Badge variant="secondary">
                                                {t('Installed')}
                                            </Badge>
                                        ) : (
                                            <Button
                                                size="sm"
                                                onClick={() =>
                                                    router.post(
                                                        store(args),
                                                        {
                                                            theme_id: theme.id,
                                                        },
                                                        options,
                                                    )
                                                }
                                            >
                                                {t('Install')}
                                            </Button>
                                        )}
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                </SettingsSection>
            </div>
        </>
    );
}

function ThemeSettings({ installed }: { installed: Installed }) {
    const { t } = useTranslation();
    const args = useBlogRoute();
    const [config, setConfig] = useState(installed.config);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [processing, setProcessing] = useState(false);

    if (installed.fields.length === 0) {
        return null;
    }

    const set = (key: string, value: ConfigValue) =>
        setConfig((current) => ({ ...current, [key]: value }));

    const groups = installed.fields.reduce<Record<string, Field[]>>(
        (all, field) => {
            const group = field.group ?? '';
            (all[group] ??= []).push(field);

            return all;
        },
        {},
    );

    const save = () =>
        router.put(
            update({ ...args, theme: installed.id }),
            { config },
            {
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
                onSuccess: () => setErrors({}),
                onError: (bag) => setErrors(bag),
            },
        );

    return (
        <form
            className="space-y-6"
            onSubmit={(event) => {
                event.preventDefault();
                save();
            }}
        >
            {Object.entries(groups).map(([group, fields]) => (
                <fieldset key={group} className="space-y-3">
                    {group && (
                        <legend className="mb-2 text-sm font-medium text-muted-foreground">
                            {t(group)}
                        </legend>
                    )}
                    <div className="grid gap-4 sm:grid-cols-2">
                        {fields.map((field) => {
                            const id = `${installed.id}-${field.key}`;
                            const value = config[field.key];

                            return (
                                <FormField
                                    key={field.key}
                                    id={id}
                                    label={t(field.label)}
                                    error={errors[`config.${field.key}`]}
                                >
                                    {field.type === 'color' ? (
                                        <div className="flex items-center gap-2">
                                            <input
                                                type="color"
                                                aria-label={t(field.label)}
                                                value={String(
                                                    value ?? '#000000',
                                                )}
                                                onChange={(event) =>
                                                    set(
                                                        field.key,
                                                        event.target.value,
                                                    )
                                                }
                                                className="h-9 w-12 cursor-pointer rounded-md border bg-transparent p-1"
                                            />
                                            <Input
                                                id={id}
                                                value={String(value ?? '')}
                                                onChange={(event) =>
                                                    set(
                                                        field.key,
                                                        event.target.value,
                                                    )
                                                }
                                                className="font-mono"
                                                maxLength={7}
                                            />
                                        </div>
                                    ) : field.type === 'select' ? (
                                        <Select
                                            value={String(value ?? '')}
                                            onValueChange={(next) =>
                                                set(field.key, next)
                                            }
                                        >
                                            <SelectTrigger
                                                id={id}
                                                className="w-full"
                                            >
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {(field.options ?? []).map(
                                                    (option) => (
                                                        <SelectItem
                                                            key={option}
                                                            value={option}
                                                        >
                                                            {t(option)}
                                                        </SelectItem>
                                                    ),
                                                )}
                                            </SelectContent>
                                        </Select>
                                    ) : field.type === 'boolean' ? (
                                        <Switch
                                            id={id}
                                            checked={value === true}
                                            onCheckedChange={(checked) =>
                                                set(field.key, checked)
                                            }
                                        />
                                    ) : (
                                        <Input
                                            id={id}
                                            value={String(value ?? '')}
                                            maxLength={200}
                                            onChange={(event) =>
                                                set(
                                                    field.key,
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    )}
                                </FormField>
                            );
                        })}
                    </div>
                </fieldset>
            ))}
            <Button type="submit" disabled={processing}>
                {t('Save settings')}
            </Button>
        </form>
    );
}

BlogThemes.layout = {
    breadcrumbs: [{ title: 'Blog settings' }, { title: 'Theme' }],
};
