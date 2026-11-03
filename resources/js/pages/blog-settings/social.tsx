import { Form } from '@inertiajs/react';
import { useState } from 'react';
import { PageTitle } from '@/components/app/page-title';
import { SettingsSection } from '@/components/app/settings-section';
import {
    SocialLinksFields,
    type SocialNetwork,
} from '@/components/content/social-links-fields';
import { Button } from '@/components/ui/button';
import { useBlogRoute } from '@/hooks/use-blog-route';
import { useTranslation } from '@/hooks/use-translation';
import { update } from '@/routes/blogs/settings/social';

type Props = {
    networks: SocialNetwork[];
    links: Record<string, string>;
};

export default function BlogSocial({ networks, links: initial }: Props) {
    const { t } = useTranslation();
    const args = useBlogRoute();
    const [links, setLinks] = useState<Record<string, string>>(initial);

    return (
        <>
            <PageTitle title={t('Social links')} />
            <SettingsSection
                title={t('Social links')}
                description={t("The blog's profiles, shown by the theme.")}
            >
                <Form
                    action={update(args)}
                    transform={() => ({ links })}
                    options={{ preserveScroll: true }}
                    className="grid max-w-2xl gap-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <SocialLinksFields
                                networks={networks}
                                value={links}
                                onChange={setLinks}
                                errors={errors}
                                errorKey="links"
                            />
                            <div>
                                <Button type="submit" disabled={processing}>
                                    {t('Save')}
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </SettingsSection>
        </>
    );
}

BlogSocial.layout = {
    breadcrumbs: [{ title: 'Blog settings' }, { title: 'Social links' }],
};
