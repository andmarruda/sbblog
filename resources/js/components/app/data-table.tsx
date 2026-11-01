import { router, usePage } from '@inertiajs/react';
import {
    flexRender,
    getCoreRowModel,
    useReactTable,
    type ColumnDef,
} from '@tanstack/react-table';
import {
    ArrowDown,
    ArrowUp,
    ArrowUpDown,
    ChevronLeft,
    ChevronRight,
    Search,
} from 'lucide-react';
import { useEffect, useRef, useState, type ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useTranslation } from '@/hooks/use-translation';
import type { Paginated } from '@/types';

export type TableQuery = {
    search?: string;
    sort?: string;
    direction?: 'asc' | 'desc';
    page?: number;
    [filter: string]: string | number | undefined;
};

type Props<T> = {
    columns: ColumnDef<T, unknown>[];
    paginator: Paginated<T>;
    /** The query the server applied, echoed back as a prop. */
    query: TableQuery;
    searchable?: boolean;
    /** Extra filter controls; call `applyQuery` from them via `onQueryChange`. */
    toolbar?: (applyQuery: (changes: TableQuery) => void) => ReactNode;
    empty?: ReactNode;
    getRowId?: (row: T) => string;
};

/**
 * Server-side table: pagination, sorting, search and filters live in the query string.
 * Columns are sortable when they set `enableSorting: true` and an `id` the server accepts.
 */
export function DataTable<T>({
    columns,
    paginator,
    query,
    searchable = true,
    toolbar,
    empty,
    getRowId,
}: Props<T>) {
    const { t, formatNumber } = useTranslation();
    const { url } = usePage();
    const [search, setSearch] = useState(query.search ?? '');
    const firstRender = useRef(true);

    const applyQuery = (changes: TableQuery) => {
        const next = { ...query, page: undefined, ...changes };
        const cleaned = Object.fromEntries(
            Object.entries(next).filter(
                ([, value]) => value !== undefined && value !== '',
            ),
        );

        router.get(url.split('?')[0], cleaned, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    useEffect(() => {
        if (firstRender.current) {
            firstRender.current = false;

            return;
        }

        const timeout = setTimeout(() => applyQuery({ search }), 300);

        return () => clearTimeout(timeout);
        // Only the search text should trigger this.
    }, [search]); // eslint-disable-line react-hooks/exhaustive-deps

    const table = useReactTable({
        data: paginator.data,
        columns,
        getRowId,
        getCoreRowModel: getCoreRowModel(),
        manualPagination: true,
        manualSorting: true,
        enableSorting: false,
    });

    const toggleSort = (column: string) => {
        if (query.sort !== column) {
            applyQuery({ sort: column, direction: 'asc' });
        } else if (query.direction === 'asc') {
            applyQuery({ sort: column, direction: 'desc' });
        } else {
            applyQuery({ sort: undefined, direction: undefined });
        }
    };

    const SortIcon = ({ column }: { column: string }) => {
        if (query.sort !== column) {
            return <ArrowUpDown className="size-3.5 opacity-50" />;
        }

        return query.direction === 'desc' ? (
            <ArrowDown className="size-3.5" />
        ) : (
            <ArrowUp className="size-3.5" />
        );
    };

    return (
        <div className="space-y-4">
            {(searchable || toolbar) && (
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
                    {searchable && (
                        <div className="relative sm:max-w-xs sm:flex-1">
                            <Search className="absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                type="search"
                                value={search}
                                onChange={(event) =>
                                    setSearch(event.target.value)
                                }
                                placeholder={t('Search…')}
                                aria-label={t('Search')}
                                className="pl-8"
                            />
                        </div>
                    )}
                    {toolbar?.(applyQuery)}
                </div>
            )}

            <div className="overflow-hidden rounded-lg border">
                <Table>
                    <TableHeader>
                        {table.getHeaderGroups().map((group) => (
                            <TableRow key={group.id}>
                                {group.headers.map((header) => (
                                    <TableHead key={header.id}>
                                        {header.isPlaceholder ? null : header
                                              .column.columnDef
                                              .enableSorting ? (
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                className="-ml-2 h-8"
                                                onClick={() =>
                                                    toggleSort(header.column.id)
                                                }
                                            >
                                                {flexRender(
                                                    header.column.columnDef
                                                        .header,
                                                    header.getContext(),
                                                )}
                                                <SortIcon
                                                    column={header.column.id}
                                                />
                                            </Button>
                                        ) : (
                                            flexRender(
                                                header.column.columnDef.header,
                                                header.getContext(),
                                            )
                                        )}
                                    </TableHead>
                                ))}
                            </TableRow>
                        ))}
                    </TableHeader>
                    <TableBody>
                        {table.getRowModel().rows.length > 0 ? (
                            table.getRowModel().rows.map((row) => (
                                <TableRow key={row.id}>
                                    {row.getVisibleCells().map((cell) => (
                                        <TableCell key={cell.id}>
                                            {flexRender(
                                                cell.column.columnDef.cell,
                                                cell.getContext(),
                                            )}
                                        </TableCell>
                                    ))}
                                </TableRow>
                            ))
                        ) : (
                            <TableRow>
                                <TableCell
                                    colSpan={columns.length}
                                    className="h-32 text-center text-muted-foreground"
                                >
                                    {empty ?? t('No results.')}
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
            </div>

            {paginator.total > 0 && (
                <div className="flex flex-col items-center justify-between gap-2 text-sm text-muted-foreground sm:flex-row">
                    <span>
                        {t('Showing :from–:to of :total', {
                            from: formatNumber(paginator.from ?? 0),
                            to: formatNumber(paginator.to ?? 0),
                            total: formatNumber(paginator.total),
                        })}
                    </span>
                    <div className="flex items-center gap-2">
                        <span>
                            {t('Page :page of :pages', {
                                page: paginator.current_page,
                                pages: paginator.last_page,
                            })}
                        </span>
                        <Button
                            variant="outline"
                            size="icon"
                            aria-label={t('Previous page')}
                            disabled={paginator.current_page <= 1}
                            onClick={() =>
                                applyQuery({
                                    ...query,
                                    page: paginator.current_page - 1,
                                })
                            }
                        >
                            <ChevronLeft />
                        </Button>
                        <Button
                            variant="outline"
                            size="icon"
                            aria-label={t('Next page')}
                            disabled={
                                paginator.current_page >= paginator.last_page
                            }
                            onClick={() =>
                                applyQuery({
                                    ...query,
                                    page: paginator.current_page + 1,
                                })
                            }
                        >
                            <ChevronRight />
                        </Button>
                    </div>
                </div>
            )}
        </div>
    );
}
