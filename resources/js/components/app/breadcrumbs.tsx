import { Link } from '@inertiajs/react';
import { Fragment } from 'react';
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import { useTranslation } from '@/hooks/use-translation';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

export function Breadcrumbs({ items }: { items: BreadcrumbItemType[] }) {
    const { t } = useTranslation();

    if (items.length === 0) {
        return null;
    }

    return (
        <Breadcrumb>
            <BreadcrumbList>
                {items.map((item, index) => {
                    const isLast = index === items.length - 1;

                    return (
                        <Fragment key={index}>
                            <BreadcrumbItem>
                                {isLast || !item.href ? (
                                    <BreadcrumbPage>
                                        {t(item.title)}
                                    </BreadcrumbPage>
                                ) : (
                                    <BreadcrumbLink asChild>
                                        <Link href={item.href}>
                                            {t(item.title)}
                                        </Link>
                                    </BreadcrumbLink>
                                )}
                            </BreadcrumbItem>
                            {!isLast && <BreadcrumbSeparator />}
                        </Fragment>
                    );
                })}
            </BreadcrumbList>
        </Breadcrumb>
    );
}
