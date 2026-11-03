import type { Editor } from '@tiptap/react';
import { useEditorState } from '@tiptap/react';
import {
    AlignCenter,
    AlignLeft,
    AlignRight,
    Bold,
    Code,
    Heading2,
    Heading3,
    Heading4,
    ImagePlus,
    Italic,
    Link2,
    List,
    ListOrdered,
    Minus,
    Quote,
    Redo2,
    SquareCode,
    Strikethrough,
    Subscript,
    Superscript,
    Table,
    Underline,
    Undo2,
    Clapperboard,
    type LucideIcon,
} from 'lucide-react';
import { useState } from 'react';
import { MediaPicker } from '@/components/content/media-picker';
import { parseEmbedUrl } from '@/components/editor/embed-url';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { Separator } from '@/components/ui/separator';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useTranslation } from '@/hooks/use-translation';
import type { MediaItem } from '@/types';

type Props = {
    editor: Editor;
    onImage: (media: MediaItem) => void;
};

function ToolButton({
    icon: Icon,
    label,
    active = false,
    disabled = false,
    onClick,
}: {
    icon: LucideIcon;
    label: string;
    active?: boolean;
    disabled?: boolean;
    onClick: () => void;
}) {
    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <Button
                    type="button"
                    variant={active ? 'secondary' : 'ghost'}
                    size="icon"
                    className="size-8"
                    aria-label={label}
                    aria-pressed={active}
                    disabled={disabled}
                    onMouseDown={(event) => event.preventDefault()}
                    onClick={onClick}
                >
                    <Icon className="size-4" />
                </Button>
            </TooltipTrigger>
            <TooltipContent>{label}</TooltipContent>
        </Tooltip>
    );
}

function LinkButton({ editor }: { editor: Editor }) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);
    const [href, setHref] = useState('');

    const apply = () => {
        const value = href.trim();

        if (value === '') {
            editor.chain().focus().extendMarkRange('link').unsetLink().run();
        } else if (/^(https?:\/\/|mailto:|\/|#)/i.test(value)) {
            editor
                .chain()
                .focus()
                .extendMarkRange('link')
                .setLink({ href: value })
                .run();
        } else {
            editor
                .chain()
                .focus()
                .extendMarkRange('link')
                .setLink({ href: `https://${value}` })
                .run();
        }

        setOpen(false);
    };

    return (
        <Popover
            open={open}
            onOpenChange={(value) => {
                setOpen(value);
                setHref(
                    (editor.getAttributes('link').href as string | undefined) ??
                        '',
                );
            }}
        >
            <PopoverTrigger asChild>
                <Button
                    type="button"
                    variant={editor.isActive('link') ? 'secondary' : 'ghost'}
                    size="icon"
                    className="size-8"
                    aria-label={t('Link')}
                >
                    <Link2 className="size-4" />
                </Button>
            </PopoverTrigger>
            <PopoverContent className="w-80">
                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        apply();
                    }}
                    className="flex gap-2"
                >
                    <Input
                        value={href}
                        onChange={(event) => setHref(event.target.value)}
                        placeholder="https://"
                        autoFocus
                    />
                    <Button type="submit" size="sm">
                        {t('Apply')}
                    </Button>
                </form>
                <p className="mt-2 text-xs text-muted-foreground">
                    {t('Leave empty to remove the link.')}
                </p>
            </PopoverContent>
        </Popover>
    );
}

function EmbedButton({ editor }: { editor: Editor }) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);
    const [url, setUrl] = useState('');
    const [error, setError] = useState(false);

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    className="size-8"
                    aria-label={t('Video')}
                >
                    <Clapperboard className="size-4" />
                </Button>
            </PopoverTrigger>
            <PopoverContent className="w-80">
                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        const embed = parseEmbedUrl(url);

                        if (!embed) {
                            setError(true);

                            return;
                        }

                        editor.chain().focus().insertEmbed(embed).run();
                        setUrl('');
                        setError(false);
                        setOpen(false);
                    }}
                    className="grid gap-2"
                >
                    <div className="flex gap-2">
                        <Input
                            value={url}
                            onChange={(event) => setUrl(event.target.value)}
                            placeholder="https://youtube.com/watch?v=…"
                            autoFocus
                            aria-invalid={error || undefined}
                        />
                        <Button type="submit" size="sm">
                            {t('Insert')}
                        </Button>
                    </div>
                    <p
                        className={
                            error
                                ? 'text-xs text-destructive'
                                : 'text-xs text-muted-foreground'
                        }
                    >
                        {error
                            ? t('Paste a YouTube or Vimeo link.')
                            : t('YouTube or Vimeo link.')}
                    </p>
                </form>
            </PopoverContent>
        </Popover>
    );
}

export function EditorToolbar({ editor, onImage }: Props) {
    const { t } = useTranslation();

    // Re-render on selection changes so active states stay correct.
    const state = useEditorState({
        editor,
        selector: ({ editor: current }) => ({
            bold: current.isActive('bold'),
            italic: current.isActive('italic'),
            underline: current.isActive('underline'),
            strike: current.isActive('strike'),
            code: current.isActive('code'),
            subscript: current.isActive('subscript'),
            superscript: current.isActive('superscript'),
            h2: current.isActive('heading', { level: 2 }),
            h3: current.isActive('heading', { level: 3 }),
            h4: current.isActive('heading', { level: 4 }),
            bulletList: current.isActive('bulletList'),
            orderedList: current.isActive('orderedList'),
            blockquote: current.isActive('blockquote'),
            codeBlock: current.isActive('codeBlock'),
            left: current.isActive({ textAlign: 'left' }),
            center: current.isActive({ textAlign: 'center' }),
            right: current.isActive({ textAlign: 'right' }),
            canUndo: current.can().undo(),
            canRedo: current.can().redo(),
        }),
    });

    const chain = () => editor.chain().focus();

    return (
        <div className="sticky top-0 z-10 flex flex-wrap items-center gap-0.5 border-b bg-background/95 p-1 backdrop-blur">
            <ToolButton
                icon={Undo2}
                label={t('Undo')}
                disabled={!state.canUndo}
                onClick={() => chain().undo().run()}
            />
            <ToolButton
                icon={Redo2}
                label={t('Redo')}
                disabled={!state.canRedo}
                onClick={() => chain().redo().run()}
            />
            <Separator
                orientation="vertical"
                className="mx-1 data-[orientation=vertical]:h-5"
            />
            <ToolButton
                icon={Heading2}
                label={t('Heading')}
                active={state.h2}
                onClick={() => chain().toggleHeading({ level: 2 }).run()}
            />
            <ToolButton
                icon={Heading3}
                label={t('Subheading')}
                active={state.h3}
                onClick={() => chain().toggleHeading({ level: 3 }).run()}
            />
            <ToolButton
                icon={Heading4}
                label={t('Small heading')}
                active={state.h4}
                onClick={() => chain().toggleHeading({ level: 4 }).run()}
            />
            <Separator
                orientation="vertical"
                className="mx-1 data-[orientation=vertical]:h-5"
            />
            <ToolButton
                icon={Bold}
                label={t('Bold')}
                active={state.bold}
                onClick={() => chain().toggleBold().run()}
            />
            <ToolButton
                icon={Italic}
                label={t('Italic')}
                active={state.italic}
                onClick={() => chain().toggleItalic().run()}
            />
            <ToolButton
                icon={Underline}
                label={t('Underline')}
                active={state.underline}
                onClick={() => chain().toggleUnderline().run()}
            />
            <ToolButton
                icon={Strikethrough}
                label={t('Strikethrough')}
                active={state.strike}
                onClick={() => chain().toggleStrike().run()}
            />
            <ToolButton
                icon={Code}
                label={t('Inline code')}
                active={state.code}
                onClick={() => chain().toggleCode().run()}
            />
            <ToolButton
                icon={Subscript}
                label={t('Subscript')}
                active={state.subscript}
                onClick={() => chain().toggleSubscript().run()}
            />
            <ToolButton
                icon={Superscript}
                label={t('Superscript')}
                active={state.superscript}
                onClick={() => chain().toggleSuperscript().run()}
            />
            <LinkButton editor={editor} />
            <Separator
                orientation="vertical"
                className="mx-1 data-[orientation=vertical]:h-5"
            />
            <ToolButton
                icon={List}
                label={t('Bulleted list')}
                active={state.bulletList}
                onClick={() => chain().toggleBulletList().run()}
            />
            <ToolButton
                icon={ListOrdered}
                label={t('Numbered list')}
                active={state.orderedList}
                onClick={() => chain().toggleOrderedList().run()}
            />
            <ToolButton
                icon={Quote}
                label={t('Quote')}
                active={state.blockquote}
                onClick={() => chain().toggleBlockquote().run()}
            />
            <ToolButton
                icon={SquareCode}
                label={t('Code block')}
                active={state.codeBlock}
                onClick={() => chain().toggleCodeBlock().run()}
            />
            <ToolButton
                icon={Minus}
                label={t('Divider')}
                onClick={() => chain().setHorizontalRule().run()}
            />
            <Separator
                orientation="vertical"
                className="mx-1 data-[orientation=vertical]:h-5"
            />
            <ToolButton
                icon={AlignLeft}
                label={t('Align left')}
                active={state.left}
                onClick={() => chain().setTextAlign('left').run()}
            />
            <ToolButton
                icon={AlignCenter}
                label={t('Center')}
                active={state.center}
                onClick={() => chain().setTextAlign('center').run()}
            />
            <ToolButton
                icon={AlignRight}
                label={t('Align right')}
                active={state.right}
                onClick={() => chain().setTextAlign('right').run()}
            />
            <Separator
                orientation="vertical"
                className="mx-1 data-[orientation=vertical]:h-5"
            />
            <MediaPicker
                trigger={
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        className="size-8"
                        aria-label={t('Image')}
                    >
                        <ImagePlus className="size-4" />
                    </Button>
                }
                onSelect={onImage}
            />
            <EmbedButton editor={editor} />
            <ToolButton
                icon={Table}
                label={t('Table')}
                onClick={() =>
                    chain()
                        .insertTable({ rows: 3, cols: 3, withHeaderRow: true })
                        .run()
                }
            />
        </div>
    );
}
