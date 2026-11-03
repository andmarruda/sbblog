import Subscript from '@tiptap/extension-subscript';
import Superscript from '@tiptap/extension-superscript';
import { TableKit } from '@tiptap/extension-table';
import TextAlign from '@tiptap/extension-text-align';
import { Placeholder } from '@tiptap/extensions';
import { EditorContent, useEditor } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import { useState } from 'react';
import { VideoEmbed } from '@/components/editor/embed-node';
import { MediaImage } from '@/components/editor/image-node';
import {
    MediaUrlsContext,
    type MediaUrls,
} from '@/components/editor/media-context';
import { EditorToolbar } from '@/components/editor/toolbar';
import { useTranslation } from '@/hooks/use-translation';
import type { TiptapDocument } from '@/types';

type Props = {
    initialContent: TiptapDocument;
    media: MediaUrls;
    editable: boolean;
    onChange: (document: TiptapDocument) => void;
};

/**
 * The article body editor. Only the nodes and marks allowed by
 * resources/schemas/body.schema.json are enabled.
 */
export function RichTextEditor({
    initialContent,
    media: initialMedia,
    editable,
    onChange,
}: Props) {
    const { t } = useTranslation();
    const [media, setMedia] = useState<MediaUrls>(initialMedia);

    const editor = useEditor({
        editable,
        immediatelyRender: false,
        extensions: [
            StarterKit.configure({
                heading: { levels: [2, 3, 4] },
                link: {
                    openOnClick: false,
                    autolink: true,
                    defaultProtocol: 'https',
                    isAllowedUri: (url) =>
                        /^(https?:\/\/|mailto:|\/|#)/i.test(url),
                },
            }),
            TextAlign.configure({ types: ['heading', 'paragraph'] }),
            Subscript,
            Superscript,
            TableKit.configure({ table: { resizable: false } }),
            Placeholder.configure({ placeholder: t('Start writing…') }),
            MediaImage,
            VideoEmbed,
        ],
        content: initialContent,
        editorProps: {
            attributes: {
                class: 'prose prose-neutral dark:prose-invert max-w-none min-h-[50vh] px-4 py-6 focus:outline-none',
            },
        },
        onUpdate: ({ editor: current }) =>
            onChange(current.getJSON() as TiptapDocument),
    });

    if (!editor) {
        return <div className="min-h-[50vh] rounded-lg border" />;
    }

    return (
        <MediaUrlsContext.Provider value={media}>
            <div className="overflow-hidden rounded-lg border">
                {editable && (
                    <EditorToolbar
                        editor={editor}
                        onImage={(item) => {
                            setMedia((current) => ({
                                ...current,
                                [item.id]: {
                                    url: item.url,
                                    alt: item.alt_text,
                                },
                            }));
                            editor
                                .chain()
                                .focus()
                                .insertImage({
                                    mediaId: item.id,
                                    alt: item.alt_text,
                                })
                                .run();
                        }}
                    />
                )}
                <EditorContent editor={editor} />
            </div>
        </MediaUrlsContext.Provider>
    );
}
