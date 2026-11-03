import { Form } from '@inertiajs/react';
import { FormField } from '@/components/app/form-field';
import { PageTitle } from '@/components/app/page-title';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useTranslation } from '@/hooks/use-translation';
import { store } from '@/routes/onboarding';

type Language = { id: string; code: string; label: string };

type Props = {
    languages: Language[];
    defaultLanguageId: string | null;
};

export default function Onboarding({ languages, defaultLanguageId }: Props) {
    const { t } = useTranslation();

    return (
        <>
            <PageTitle title={t('Set up your blog')} />
            <Form action={store()} className="grid gap-4">
                {({ processing, errors }) => (
                    <>
                        <FormField
                            id="workspace_name"
                            label={t('Workspace name')}
                            hint={t('Your company, team or your own name.')}
                            error={errors.workspace_name}
                        >
                            <Input
                                id="workspace_name"
                                name="workspace_name"
                                autoFocus
                                required
                            />
                        </FormField>
                        <FormField
                            id="blog_name"
                            label={t('Blog name')}
                            error={errors.blog_name}
                        >
                            <Input id="blog_name" name="blog_name" required />
                        </FormField>
                        <FormField
                            id="language_id"
                            label={t('Main language')}
                            error={errors.language_id}
                        >
                            <Select
                                name="language_id"
                                defaultValue={defaultLanguageId ?? undefined}
                            >
                                <SelectTrigger
                                    id="language_id"
                                    className="w-full"
                                >
                                    <SelectValue
                                        placeholder={t('Choose a language')}
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    {languages.map((language) => (
                                        <SelectItem
                                            key={language.id}
                                            value={language.id}
                                        >
                                            {language.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </FormField>
                        <Button type="submit" disabled={processing}>
                            {t('Create blog')}
                        </Button>
                    </>
                )}
            </Form>
        </>
    );
}

Onboarding.layout = {
    title: 'Set up your blog',
    description:
        'Create a workspace and your first blog. You can add more later.',
};
