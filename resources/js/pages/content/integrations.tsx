import { Form, router } from '@inertiajs/react';
import { Pencil, Plug, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { ConfirmDialog } from '@/components/app/confirm-dialog';
import { EmptyState } from '@/components/app/empty-state';
import { FormField } from '@/components/app/form-field';
import { PageHeader } from '@/components/app/page-header';
import { PasswordInput } from '@/components/app/password-input';
import { SettingsSection } from '@/components/app/settings-section';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import { useBlogRoute } from '@/hooks/use-blog-route';
import { useTranslation } from '@/hooks/use-translation';
import { destroy, index, store, toggle, update } from '@/routes/integrations';

type Scope = 'blog' | 'author' | 'article';
type Provider =
    | 'ga4'
    | 'google_ads'
    | 'gtm'
    | 'meta_pixel'
    | 'clarity'
    | 'plausible'
    | 'custom';

type Integration = {
    id: string;
    scope: Scope;
    provider: Provider;
    config: Record<string, string | null>;
    placement: 'head' | 'body_start' | 'body_end';
    consent_category: 'analytics' | 'ads' | 'functional';
    page_rule: 'all' | 'articles' | 'home';
    enabled: boolean;
    author_profile_id: string | null;
    article_id: string | null;
    target: string | null;
};

type Props = {
    integrations: Integration[];
    authors: { id: string; display_name: string }[];
    articles: { id: string; title: string }[];
    preview: {
        article: string | null;
        html: { head: string; body_start: string; body_end: string };
    };
    canRawScripts: boolean;
};

const PROVIDERS: Record<
    Provider,
    {
        name: string;
        consent: Integration['consent_category'];
        fields: { key: string; placeholder: string; optional?: boolean }[];
    }
> = {
    ga4: {
        name: 'Google Analytics 4',
        consent: 'analytics',
        fields: [{ key: 'measurement_id', placeholder: 'G-XXXXXXXXXX' }],
    },
    google_ads: {
        name: 'Google Ads',
        consent: 'ads',
        fields: [
            { key: 'conversion_id', placeholder: 'AW-123456789' },
            {
                key: 'conversion_label',
                placeholder: 'AbC-D_efG',
                optional: true,
            },
        ],
    },
    gtm: {
        name: 'Google Tag Manager',
        consent: 'analytics',
        fields: [{ key: 'container_id', placeholder: 'GTM-XXXXXX' }],
    },
    meta_pixel: {
        name: 'Meta Pixel',
        consent: 'ads',
        fields: [{ key: 'pixel_id', placeholder: '123456789012345' }],
    },
    clarity: {
        name: 'Microsoft Clarity',
        consent: 'analytics',
        fields: [{ key: 'project_id', placeholder: 'abcdef1234' }],
    },
    plausible: {
        name: 'Plausible',
        consent: 'analytics',
        fields: [
            { key: 'domain', placeholder: 'blog.example.com' },
            {
                key: 'script_url',
                placeholder: 'https://plausible.io/js/script.js',
                optional: true,
            },
        ],
    },
    custom: {
        name: 'Custom script',
        consent: 'functional',
        fields: [{ key: 'name', placeholder: 'Chat widget' }],
    },
};

const ID_KEYS: Record<Provider, string> = {
    ga4: 'measurement_id',
    google_ads: 'conversion_id',
    gtm: 'container_id',
    meta_pixel: 'pixel_id',
    clarity: 'project_id',
    plausible: 'domain',
    custom: 'name',
};

function IntegrationDialog({
    integration,
    props,
    onClose,
}: {
    integration: Integration | null;
    props: Props;
    onClose: () => void;
}) {
    const { t } = useTranslation();
    const args = useBlogRoute();
    const [provider, setProvider] = useState<Provider>(
        integration?.provider ?? 'ga4',
    );
    const [scope, setScope] = useState<Scope>(integration?.scope ?? 'blog');
    const [target, setTarget] = useState(
        integration?.author_profile_id ?? integration?.article_id ?? '',
    );
    const [config, setConfig] = useState<Record<string, string>>(
        Object.fromEntries(
            Object.entries(integration?.config ?? {}).map(([key, value]) => [
                key,
                value ?? '',
            ]),
        ),
    );
    const [placement, setPlacement] = useState<Integration['placement']>(
        integration?.placement ?? 'head',
    );
    const [consent, setConsent] = useState<Integration['consent_category']>(
        integration?.consent_category ?? 'analytics',
    );
    const fieldLabels: Record<string, string> = {
        name: t('Name'),
        measurement_id: t('Measurement ID'),
        conversion_id: t('Conversion ID'),
        conversion_label: t('Conversion label'),
        container_id: t('Container ID'),
        pixel_id: t('Pixel ID'),
        project_id: t('Project ID'),
        domain: t('Domain'),
        script_url: t('Script URL'),
    };
    const [pageRule, setPageRule] = useState<Integration['page_rule']>(
        integration?.page_rule ?? 'all',
    );
    const providers = (Object.keys(PROVIDERS) as Provider[]).filter(
        (key) =>
            key !== 'custom' ||
            props.canRawScripts ||
            integration?.provider === 'custom',
    );

    const consentLabels = {
        analytics: t('Analytics'),
        ads: t('Advertising'),
        functional: t('Functional'),
    };

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>
                        {integration
                            ? t('Edit integration')
                            : t('New integration')}
                    </DialogTitle>
                    <DialogDescription>
                        {t(
                            'Scripts run only after the reader consents to their category.',
                        )}
                    </DialogDescription>
                </DialogHeader>
                <Form
                    action={
                        integration
                            ? update({ ...args, integration: integration.id })
                            : store(args)
                    }
                    transform={(data) => ({
                        ...data,
                        provider,
                        scope,
                        author_profile_id:
                            scope === 'author' ? target || null : null,
                        article_id: scope === 'article' ? target || null : null,
                        config,
                        placement,
                        consent_category: consent,
                        page_rule: pageRule,
                        enabled: integration?.enabled ?? true,
                    })}
                    options={{ preserveScroll: true }}
                    onSuccess={onClose}
                    className="grid gap-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <FormField
                                id="provider"
                                label={t('Service')}
                                error={errors.provider}
                            >
                                <Select
                                    value={provider}
                                    onValueChange={(value) => {
                                        const next = value as Provider;
                                        setProvider(next);
                                        setConsent(PROVIDERS[next].consent);
                                        setConfig({});
                                        if (next === 'custom') {
                                            setScope('blog');
                                        }
                                    }}
                                    disabled={integration !== null}
                                >
                                    <SelectTrigger
                                        id="provider"
                                        className="w-full"
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {providers.map((key) => (
                                            <SelectItem key={key} value={key}>
                                                {key === 'custom'
                                                    ? t('Custom script')
                                                    : PROVIDERS[key].name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </FormField>

                            {PROVIDERS[provider].fields.map((field) => (
                                <FormField
                                    key={field.key}
                                    id={`config-${field.key}`}
                                    label={fieldLabels[field.key] ?? field.key}
                                    hint={
                                        field.optional
                                            ? t('Optional')
                                            : undefined
                                    }
                                    error={errors[`config.${field.key}`]}
                                >
                                    <Input
                                        id={`config-${field.key}`}
                                        value={config[field.key] ?? ''}
                                        placeholder={field.placeholder}
                                        onChange={(event) =>
                                            setConfig({
                                                ...config,
                                                [field.key]: event.target.value,
                                            })
                                        }
                                    />
                                </FormField>
                            ))}

                            {provider === 'custom' && (
                                <FormField
                                    id="config-html"
                                    label={t('HTML')}
                                    hint={t(
                                        'Runs on every page of the blog. Only add code you trust.',
                                    )}
                                    error={errors['config.html']}
                                >
                                    <Textarea
                                        id="config-html"
                                        value={config.html ?? ''}
                                        onChange={(event) =>
                                            setConfig({
                                                ...config,
                                                html: event.target.value,
                                            })
                                        }
                                        rows={6}
                                        className="font-mono text-xs"
                                    />
                                </FormField>
                            )}

                            <FormField
                                id="scope"
                                label={t('Where')}
                                error={errors.scope}
                            >
                                <Select
                                    value={scope}
                                    onValueChange={(value) => {
                                        setScope(value as Scope);
                                        setTarget('');
                                    }}
                                    disabled={provider === 'custom'}
                                >
                                    <SelectTrigger
                                        id="scope"
                                        className="w-full"
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="blog">
                                            {t('Whole blog')}
                                        </SelectItem>
                                        <SelectItem value="author">
                                            {t("One author's articles")}
                                        </SelectItem>
                                        <SelectItem value="article">
                                            {t('One article')}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </FormField>

                            {scope !== 'blog' && (
                                <FormField
                                    id="target"
                                    label={
                                        scope === 'author'
                                            ? t('Author')
                                            : t('Article')
                                    }
                                    error={
                                        errors.author_profile_id ??
                                        errors.article_id
                                    }
                                >
                                    <Select
                                        value={target}
                                        onValueChange={setTarget}
                                    >
                                        <SelectTrigger
                                            id="target"
                                            className="w-full"
                                        >
                                            <SelectValue
                                                placeholder={t('Choose…')}
                                            />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {scope === 'author'
                                                ? props.authors.map(
                                                      (author) => (
                                                          <SelectItem
                                                              key={author.id}
                                                              value={author.id}
                                                          >
                                                              {
                                                                  author.display_name
                                                              }
                                                          </SelectItem>
                                                      ),
                                                  )
                                                : props.articles.map(
                                                      (article) => (
                                                          <SelectItem
                                                              key={article.id}
                                                              value={article.id}
                                                          >
                                                              {article.title ||
                                                                  t('Untitled')}
                                                          </SelectItem>
                                                      ),
                                                  )}
                                        </SelectContent>
                                    </Select>
                                </FormField>
                            )}

                            <div className="grid gap-4 sm:grid-cols-3">
                                <FormField
                                    id="placement"
                                    label={t('Placement')}
                                    error={errors.placement}
                                >
                                    <Select
                                        value={placement}
                                        onValueChange={(value) =>
                                            setPlacement(
                                                value as Integration['placement'],
                                            )
                                        }
                                    >
                                        <SelectTrigger
                                            id="placement"
                                            className="w-full"
                                        >
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="head">
                                                &lt;head&gt;
                                            </SelectItem>
                                            <SelectItem value="body_start">
                                                {t('Body start')}
                                            </SelectItem>
                                            <SelectItem value="body_end">
                                                {t('Body end')}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </FormField>
                                <FormField
                                    id="consent"
                                    label={t('Consent')}
                                    error={errors.consent_category}
                                >
                                    <Select
                                        value={consent}
                                        onValueChange={(value) =>
                                            setConsent(
                                                value as Integration['consent_category'],
                                            )
                                        }
                                    >
                                        <SelectTrigger
                                            id="consent"
                                            className="w-full"
                                        >
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {(
                                                Object.keys(
                                                    consentLabels,
                                                ) as (keyof typeof consentLabels)[]
                                            ).map((key) => (
                                                <SelectItem
                                                    key={key}
                                                    value={key}
                                                >
                                                    {consentLabels[key]}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </FormField>
                                <FormField
                                    id="page_rule"
                                    label={t('Pages')}
                                    error={errors.page_rule}
                                >
                                    <Select
                                        value={pageRule}
                                        onValueChange={(value) =>
                                            setPageRule(
                                                value as Integration['page_rule'],
                                            )
                                        }
                                    >
                                        <SelectTrigger
                                            id="page_rule"
                                            className="w-full"
                                        >
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">
                                                {t('All pages')}
                                            </SelectItem>
                                            <SelectItem value="articles">
                                                {t('Article pages')}
                                            </SelectItem>
                                            <SelectItem value="home">
                                                {t('Other pages')}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </FormField>
                            </div>

                            {provider === 'custom' && (
                                <FormField
                                    id="integration-password"
                                    label={t('Password')}
                                    hint={t('Raw scripts need your password.')}
                                    error={errors.password}
                                >
                                    <PasswordInput
                                        id="integration-password"
                                        name="password"
                                        autoComplete="current-password"
                                        required
                                    />
                                </FormField>
                            )}

                            <DialogFooter>
                                <Button type="submit" disabled={processing}>
                                    {t('Save')}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

export default function Integrations(props: Props) {
    const { t } = useTranslation();
    const args = useBlogRoute();
    const [editing, setEditing] = useState<Integration | null | undefined>(
        undefined,
    );
    const scopeLabels = {
        blog: t('Whole blog'),
        author: t('Authors'),
        article: t('Articles'),
    };
    const consentLabels = {
        analytics: t('Analytics'),
        ads: t('Advertising'),
        functional: t('Functional'),
    };

    const list = (scope: Scope) => {
        const items = props.integrations.filter(
            (integration) => integration.scope === scope,
        );

        if (items.length === 0) {
            return <EmptyState icon={Plug} title={t('Nothing here yet')} />;
        }

        return (
            <ul className="divide-y rounded-lg border">
                {items.map((integration) => (
                    <li
                        key={integration.id}
                        className="flex flex-wrap items-center gap-3 p-3"
                    >
                        <div className="min-w-0 flex-1">
                            <p className="font-medium">
                                {integration.provider === 'custom'
                                    ? t('Custom script')
                                    : PROVIDERS[integration.provider].name}
                                {integration.target && (
                                    <span className="text-muted-foreground">
                                        {' '}
                                        · {integration.target}
                                    </span>
                                )}
                            </p>
                            <p className="font-mono text-sm text-muted-foreground">
                                {
                                    integration.config[
                                        ID_KEYS[integration.provider]
                                    ]
                                }
                            </p>
                        </div>
                        <Badge variant="outline">
                            {consentLabels[integration.consent_category]}
                        </Badge>
                        {integration.provider !== 'custom' && (
                            <Switch
                                checked={integration.enabled}
                                aria-label={t('Enabled')}
                                onCheckedChange={() =>
                                    router.post(
                                        toggle({
                                            ...args,
                                            integration: integration.id,
                                        }),
                                        {},
                                        { preserveScroll: true },
                                    )
                                }
                            />
                        )}
                        <Button
                            variant="ghost"
                            size="icon"
                            aria-label={t('Edit')}
                            onClick={() => setEditing(integration)}
                        >
                            <Pencil />
                        </Button>
                        {integration.provider !== 'custom' && (
                            <ConfirmDialog
                                trigger={
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        aria-label={t('Delete')}
                                    >
                                        <Trash2 />
                                    </Button>
                                }
                                title={t('Remove this integration?')}
                                confirmLabel={t('Remove')}
                                destructive
                                onConfirm={() =>
                                    router.delete(
                                        destroy({
                                            ...args,
                                            integration: integration.id,
                                        }),
                                        { preserveScroll: true },
                                    )
                                }
                            />
                        )}
                    </li>
                ))}
            </ul>
        );
    };

    return (
        <>
            <PageHeader
                title={t('Integrations')}
                description={t(
                    'Analytics, ads and tag managers. Article pages also send article, author and category to your analytics.',
                )}
                actions={
                    <Button onClick={() => setEditing(null)}>
                        <Plus />
                        {t('New integration')}
                    </Button>
                }
            />
            <Tabs defaultValue="blog">
                <TabsList>
                    {(Object.keys(scopeLabels) as Scope[]).map((scope) => (
                        <TabsTrigger key={scope} value={scope}>
                            {scopeLabels[scope]}
                        </TabsTrigger>
                    ))}
                </TabsList>
                {(Object.keys(scopeLabels) as Scope[]).map((scope) => (
                    <TabsContent key={scope} value={scope} className="mt-4">
                        {list(scope)}
                    </TabsContent>
                ))}
            </Tabs>

            <SettingsSection
                title={t('Preview')}
                description={t('What a page of the blog will load.')}
            >
                <Select
                    value={props.preview.article ?? 'home'}
                    onValueChange={(value) =>
                        router.get(
                            index(args).url,
                            value === 'home' ? {} : { article: value },
                            {
                                preserveScroll: true,
                                preserveState: true,
                                only: ['preview'],
                            },
                        )
                    }
                >
                    <SelectTrigger
                        aria-label={t('Preview a page')}
                        className="w-full sm:w-96"
                    >
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="home">
                            {t('A page that is not an article')}
                        </SelectItem>
                        {props.articles.map((article) => (
                            <SelectItem key={article.id} value={article.id}>
                                {article.title || t('Untitled')}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                {(['head', 'body_start', 'body_end'] as const).map(
                    (placement) => (
                        <div key={placement} className="space-y-1">
                            <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                {placement}
                            </p>
                            <pre className="max-h-64 overflow-auto rounded-lg bg-muted p-3 text-xs whitespace-pre-wrap">
                                {props.preview.html[placement] || t('(empty)')}
                            </pre>
                        </div>
                    ),
                )}
            </SettingsSection>

            {editing !== undefined && (
                <IntegrationDialog
                    key={editing?.id ?? 'new'}
                    integration={editing}
                    props={props}
                    onClose={() => setEditing(undefined)}
                />
            )}
        </>
    );
}

Integrations.layout = {
    breadcrumbs: [{ title: 'Integrations' }],
};
