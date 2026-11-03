import { Check, ChevronsUpDown, Plus } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { useBlogRoute } from '@/hooks/use-blog-route';
import { useTranslation } from '@/hooks/use-translation';
import { http, HttpError } from '@/lib/http';
import { store } from '@/routes/tags';
import type { Option } from '@/types';

type Props = {
    options: Option[];
    value: string[];
    onChange: (value: string[]) => void;
    canCreate: boolean;
    disabled?: boolean;
};

export function TagPicker({
    options: initial,
    value,
    onChange,
    canCreate,
    disabled = false,
}: Props) {
    const { t } = useTranslation();
    const args = useBlogRoute();
    const [open, setOpen] = useState(false);
    const [search, setSearch] = useState('');
    const [options, setOptions] = useState(initial);

    const toggle = (id: string) =>
        onChange(
            value.includes(id)
                ? value.filter((item) => item !== id)
                : [...value, id],
        );

    const create = async () => {
        try {
            const tag = await http<Option>(store(args).url, {
                method: 'POST',
                json: { name: search.trim() },
            });
            setOptions((current) => [...current, tag]);
            onChange([...value, tag.id]);
            setSearch('');
        } catch (error) {
            toast.error(
                error instanceof HttpError
                    ? (error.first() ?? '')
                    : t('Could not create the tag.'),
            );
        }
    };

    const exact = options.some(
        (option) => option.name.toLowerCase() === search.trim().toLowerCase(),
    );

    return (
        <div className="grid gap-2">
            <Popover open={open} onOpenChange={setOpen}>
                <PopoverTrigger asChild>
                    <Button
                        type="button"
                        variant="outline"
                        className="w-full justify-between font-normal"
                        disabled={disabled}
                    >
                        {value.length === 0
                            ? t('Choose tags')
                            : t(':count selected', { count: value.length })}
                        <ChevronsUpDown className="opacity-50" />
                    </Button>
                </PopoverTrigger>
                <PopoverContent className="w-72 p-0" align="start">
                    <Command>
                        <CommandInput
                            value={search}
                            onValueChange={setSearch}
                            placeholder={t('Search tags…')}
                        />
                        <CommandList>
                            <CommandEmpty>{t('No tags found.')}</CommandEmpty>
                            <CommandGroup>
                                {options.map((option) => (
                                    <CommandItem
                                        key={option.id}
                                        value={option.name}
                                        onSelect={() => toggle(option.id)}
                                    >
                                        <Check
                                            className={
                                                value.includes(option.id)
                                                    ? 'opacity-100'
                                                    : 'opacity-0'
                                            }
                                        />
                                        {option.name}
                                    </CommandItem>
                                ))}
                            </CommandGroup>
                            {canCreate && search.trim() !== '' && !exact && (
                                <CommandGroup>
                                    <CommandItem
                                        value={`create:${search}`}
                                        onSelect={() => void create()}
                                    >
                                        <Plus />
                                        {t('Create ":name"', {
                                            name: search.trim(),
                                        })}
                                    </CommandItem>
                                </CommandGroup>
                            )}
                        </CommandList>
                    </Command>
                </PopoverContent>
            </Popover>
            {value.length > 0 && (
                <div className="flex flex-wrap gap-1">
                    {value.map((id) => (
                        <Badge key={id} variant="secondary">
                            {options.find((option) => option.id === id)?.name ??
                                '…'}
                        </Badge>
                    ))}
                </div>
            )}
        </div>
    );
}
