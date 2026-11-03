import { Link, router } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import { ChevronLeft } from 'lucide-react';
import { useMemo } from 'react';
import { DataTable, type TableQuery } from '@/components/app/data-table';
import { PageHeader } from '@/components/app/page-header';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { useBlogRoute } from '@/hooks/use-blog-route';
import { useTranslation } from '@/hooks/use-translation';
import { events as eventsRoute, index } from '@/routes/publishing';
import type { Paginated } from '@/types';

type Row = {
    id: number;
    sequence: number;
    type: string;
    aggregate_type: string;
    aggregate_id: string;
    occurred_at: string | null;
};

type Props = {
    events: Paginated<Row>;
    types: string[];
    query: TableQuery & { event?: string };
    selected: string | null;
};

const ALL = 'all';

export default function Events({ events, types, query, selected }: Props) {
    const { t, formatDate } = useTranslation();
    const args = useBlogRoute();

    const open = (id: number | null) =>
        router.get(
            eventsRoute(args).url,
            { ...query, event: id ?? undefined },
            { preserveState: true, preserveScroll: true, only: ['selected'] },
        );

    const columns = useMemo<ColumnDef<Row, unknown>[]>(
        () => [
            {
                id: 'sequence',
                header: '#',
                cell: ({ row }) => (
                    <span className="font-mono">{row.original.sequence}</span>
                ),
            },
            {
                id: 'type',
                header: t('Type'),
                cell: ({ row }) => (
                    <button
                        type="button"
                        className="font-mono text-sm hover:underline"
                        onClick={() => open(row.original.id)}
                    >
                        {row.original.type}
                    </button>
                ),
            },
            {
                id: 'aggregate',
                header: t('Item'),
                cell: ({ row }) => (
                    <span className="font-mono text-xs text-muted-foreground">
                        {row.original.aggregate_type}{' '}
                        {row.original.aggregate_id}
                    </span>
                ),
            },
            {
                id: 'occurred_at',
                header: t('When'),
                cell: ({ row }) => (
                    <span className="whitespace-nowrap text-muted-foreground">
                        {formatDate(row.original.occurred_at, true)}
                    </span>
                ),
            },
        ],
        [t, formatDate], // eslint-disable-line react-hooks/exhaustive-deps
    );

    return (
        <>
            <PageHeader
                title={t('Event log')}
                description={t(
                    'Everything frontends received, newest first. Search by item ID.',
                )}
                actions={
                    <Button variant="ghost" asChild>
                        <Link href={index(args)}>
                            <ChevronLeft />
                            {t('Publishing')}
                        </Link>
                    </Button>
                }
            />
            <DataTable
                columns={columns}
                paginator={events}
                query={query}
                getRowId={(row) => String(row.id)}
                toolbar={(apply) => (
                    <Select
                        value={(query.type as string | undefined) ?? ALL}
                        onValueChange={(type) =>
                            apply({ type: type === ALL ? undefined : type })
                        }
                    >
                        <SelectTrigger
                            className="w-full sm:w-56"
                            aria-label={t('Type')}
                        >
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ALL}>
                                {t('All types')}
                            </SelectItem>
                            {types.map((type) => (
                                <SelectItem key={type} value={type}>
                                    {type}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                )}
            />
            <Sheet
                open={selected !== null}
                onOpenChange={(value) => !value && open(null)}
            >
                <SheetContent className="w-full overflow-y-auto sm:max-w-2xl">
                    <SheetHeader>
                        <SheetTitle>{t('Payload')}</SheetTitle>
                    </SheetHeader>
                    <pre className="mx-4 mb-4 overflow-auto rounded-lg bg-muted p-3 text-xs">
                        {selected}
                    </pre>
                </SheetContent>
            </Sheet>
        </>
    );
}

Events.layout = {
    breadcrumbs: [{ title: 'Publishing' }, { title: 'Event log' }],
};
