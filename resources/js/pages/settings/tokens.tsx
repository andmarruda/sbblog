import { Form, router } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { useState } from 'react';
import { ConfirmDialog } from '@/components/app/confirm-dialog';
import { FormField } from '@/components/app/form-field';
import { PageTitle } from '@/components/app/page-title';
import { PasswordInput } from '@/components/app/password-input';
import { SecretAlert } from '@/components/app/secret-alert';
import { SettingsSection } from '@/components/app/settings-section';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useTranslation } from '@/hooks/use-translation';
import { edit as editProfile } from '@/routes/profile';
import { destroy, store } from '@/routes/tokens';

type Props = {
    tokens: {
        id: number;
        name: string;
        abilities: string[];
        last_used_at: string | null;
        expires_at: string | null;
        created_at: string | null;
    }[];
    scopes: Record<string, string>;
    newToken: string | null;
};

export default function Tokens({ tokens, scopes, newToken }: Props) {
    const { t, formatDate } = useTranslation();
    const [abilities, setAbilities] = useState<string[]>(['articles:read']);
    const [expires, setExpires] = useState('90');

    return (
        <>
            <PageTitle title={t('API tokens')} />
            <SettingsSection
                title={t('API tokens')}
                description={t(
                    'For scripts and tools that use the API as you. They never get more than your own permissions.',
                )}
            >
                {newToken && (
                    <SecretAlert
                        label={t(
                            'Your new token. Copy it now: it will not be shown again.',
                        )}
                        value={newToken}
                    />
                )}
                <Form
                    action={store()}
                    transform={(data) => ({
                        ...data,
                        abilities,
                        expires_in_days:
                            expires === 'never' ? null : Number(expires),
                    })}
                    resetOnSuccess
                    options={{ preserveScroll: true }}
                    className="grid gap-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <FormField
                                id="token-name"
                                label={t('Name')}
                                error={errors.name}
                            >
                                <Input id="token-name" name="name" required />
                            </FormField>
                            <fieldset className="grid gap-2">
                                <legend className="mb-1 text-sm font-medium">
                                    {t('Scopes')}
                                </legend>
                                {Object.entries(scopes).map(
                                    ([key, description]) => (
                                        <div
                                            key={key}
                                            className="flex items-center gap-2"
                                        >
                                            <Checkbox
                                                id={`token-${key}`}
                                                checked={abilities.includes(
                                                    key,
                                                )}
                                                onCheckedChange={(checked) =>
                                                    setAbilities((current) =>
                                                        checked === true
                                                            ? [...current, key]
                                                            : current.filter(
                                                                  (value) =>
                                                                      value !==
                                                                      key,
                                                              ),
                                                    )
                                                }
                                            />
                                            <Label
                                                htmlFor={`token-${key}`}
                                                className="font-normal"
                                            >
                                                {t(description)}
                                            </Label>
                                        </div>
                                    ),
                                )}
                                {errors.abilities && (
                                    <p className="text-sm text-destructive">
                                        {errors.abilities}
                                    </p>
                                )}
                            </fieldset>
                            <FormField id="token-expires" label={t('Expires')}>
                                <Select
                                    value={expires}
                                    onValueChange={setExpires}
                                >
                                    <SelectTrigger
                                        id="token-expires"
                                        className="w-full"
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="30">
                                            {t('In 30 days')}
                                        </SelectItem>
                                        <SelectItem value="90">
                                            {t('In 90 days')}
                                        </SelectItem>
                                        <SelectItem value="365">
                                            {t('In a year')}
                                        </SelectItem>
                                        <SelectItem value="never">
                                            {t('Never')}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </FormField>
                            <FormField
                                id="token-password"
                                label={t('Password')}
                                error={errors.password}
                            >
                                <PasswordInput
                                    id="token-password"
                                    name="password"
                                    autoComplete="current-password"
                                    required
                                />
                            </FormField>
                            <div>
                                <Button type="submit" disabled={processing}>
                                    {t('Create token')}
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
                {tokens.length > 0 && (
                    <ul className="divide-y rounded-lg border">
                        {tokens.map((token) => (
                            <li
                                key={token.id}
                                className="flex flex-wrap items-center gap-3 p-3"
                            >
                                <div className="min-w-0 flex-1">
                                    <p className="font-medium">{token.name}</p>
                                    <p className="text-sm text-muted-foreground">
                                        {[
                                            token.last_used_at
                                                ? t('Last used :date', {
                                                      date: formatDate(
                                                          token.last_used_at,
                                                          true,
                                                      ),
                                                  })
                                                : t('Never used'),
                                            token.expires_at &&
                                                t('Expires :date', {
                                                    date: formatDate(
                                                        token.expires_at,
                                                    ),
                                                }),
                                        ]
                                            .filter(Boolean)
                                            .join(' · ')}
                                    </p>
                                    <div className="mt-1 flex flex-wrap gap-1">
                                        {token.abilities.map((ability) => (
                                            <Badge
                                                key={ability}
                                                variant="outline"
                                            >
                                                {ability}
                                            </Badge>
                                        ))}
                                    </div>
                                </div>
                                <ConfirmDialog
                                    trigger={
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            aria-label={t('Revoke')}
                                        >
                                            <Trash2 />
                                        </Button>
                                    }
                                    title={t('Revoke :name?', {
                                        name: token.name,
                                    })}
                                    confirmLabel={t('Revoke')}
                                    destructive
                                    onConfirm={() =>
                                        router.delete(destroy(token.id), {
                                            preserveScroll: true,
                                        })
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

Tokens.layout = {
    breadcrumbs: [
        { title: 'Account settings', href: editProfile().url },
        { title: 'API tokens' },
    ],
};
