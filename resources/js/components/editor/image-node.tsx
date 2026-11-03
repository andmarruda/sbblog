import { mergeAttributes, Node } from '@tiptap/core';
import {
    NodeViewWrapper,
    ReactNodeViewRenderer,
    type ReactNodeViewProps,
} from '@tiptap/react';
import { ImageOff } from 'lucide-react';
import { useMediaUrl } from '@/components/editor/media-context';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';

declare module '@tiptap/core' {
    interface Commands<ReturnType> {
        mediaImage: {
            insertImage: (attrs: {
                mediaId: string;
                alt?: string | null;
            }) => ReturnType;
        };
    }
}

function ImageView({
    node,
    updateAttributes,
    selected,
    editor,
}: ReactNodeViewProps) {
    const { t } = useTranslation();
    const mediaId = node.attrs.mediaId as string;
    const media = useMediaUrl(mediaId);
    const editable = editor.isEditable;

    return (
        <NodeViewWrapper
            as="figure"
            className={cn(
                'not-prose my-6 space-y-2',
                selected && 'rounded-md ring-2 ring-ring ring-offset-2',
            )}
        >
            {media ? (
                <img
                    src={media.url}
                    alt={(node.attrs.alt as string | null) ?? media.alt ?? ''}
                    className="w-full rounded-md"
                    draggable={false}
                />
            ) : (
                <div className="flex aspect-video items-center justify-center rounded-md bg-muted text-muted-foreground">
                    <ImageOff className="size-6" />
                </div>
            )}
            {editable ? (
                <div className="grid gap-1 sm:grid-cols-2">
                    <input
                        value={(node.attrs.caption as string | null) ?? ''}
                        onChange={(event) =>
                            updateAttributes({
                                caption: event.target.value || null,
                            })
                        }
                        placeholder={t('Caption (optional)')}
                        className="rounded-md border bg-transparent px-2 py-1 text-sm"
                    />
                    <input
                        value={(node.attrs.alt as string | null) ?? ''}
                        onChange={(event) =>
                            updateAttributes({
                                alt: event.target.value || null,
                            })
                        }
                        placeholder={t('Description for screen readers')}
                        className="rounded-md border bg-transparent px-2 py-1 text-sm"
                    />
                </div>
            ) : (
                node.attrs.caption && (
                    <figcaption className="text-center text-sm text-muted-foreground">
                        {node.attrs.caption as string}
                    </figcaption>
                )
            )}
        </NodeViewWrapper>
    );
}

/**
 * An image from the media library. Stores only `mediaId`, `alt` and `caption`
 * (see resources/schemas/body.schema.json).
 */
export const MediaImage = Node.create({
    name: 'image',
    group: 'block',
    atom: true,
    draggable: true,

    addAttributes() {
        return {
            mediaId: { default: null },
            alt: { default: null },
            caption: { default: null },
        };
    },

    parseHTML() {
        return [
            {
                tag: 'figure[data-media-id]',
                getAttrs: (element) => ({
                    mediaId: element.getAttribute('data-media-id'),
                }),
            },
        ];
    },

    renderHTML({ HTMLAttributes }) {
        return [
            'figure',
            mergeAttributes({ 'data-media-id': HTMLAttributes.mediaId }),
        ];
    },

    addNodeView() {
        return ReactNodeViewRenderer(ImageView);
    },

    addCommands() {
        return {
            insertImage:
                (attrs) =>
                ({ commands }) =>
                    // A paragraph after the image keeps the cursor out of it, so the next
                    // insert doesn't replace the selected image.
                    commands.insertContent([
                        {
                            type: this.name,
                            attrs: {
                                mediaId: attrs.mediaId,
                                alt: attrs.alt ?? null,
                                caption: null,
                            },
                        },
                        { type: 'paragraph' },
                    ]),
        };
    },
});
