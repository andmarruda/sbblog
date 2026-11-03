import { mergeAttributes, Node } from '@tiptap/core';
import {
    NodeViewWrapper,
    ReactNodeViewRenderer,
    type ReactNodeViewProps,
} from '@tiptap/react';
import { embedSrc, type EmbedProvider } from '@/components/editor/embed-url';
import { cn } from '@/lib/utils';

declare module '@tiptap/core' {
    interface Commands<ReturnType> {
        videoEmbed: {
            insertEmbed: (attrs: {
                provider: EmbedProvider;
                videoId: string;
            }) => ReturnType;
        };
    }
}

function EmbedView({ node, selected }: ReactNodeViewProps) {
    return (
        <NodeViewWrapper
            className={cn(
                'not-prose my-6',
                selected && 'rounded-md ring-2 ring-ring ring-offset-2',
            )}
        >
            <div className="aspect-video overflow-hidden rounded-md bg-muted">
                {/* Pointer events off so clicks select the node instead of playing the video. */}
                <iframe
                    src={embedSrc(
                        node.attrs.provider as EmbedProvider,
                        node.attrs.videoId as string,
                    )}
                    className="pointer-events-none size-full"
                    loading="lazy"
                    title={`${node.attrs.provider} ${node.attrs.videoId}`}
                />
            </div>
        </NodeViewWrapper>
    );
}

/**
 * A YouTube or Vimeo video, stored as provider + video ID only.
 */
export const VideoEmbed = Node.create({
    name: 'embed',
    group: 'block',
    atom: true,
    draggable: true,

    addAttributes() {
        return {
            provider: { default: 'youtube' },
            videoId: { default: null },
        };
    },

    parseHTML() {
        return [{ tag: 'div[data-embed]' }];
    },

    renderHTML({ HTMLAttributes }) {
        return [
            'div',
            mergeAttributes({ 'data-embed': HTMLAttributes.provider }),
        ];
    },

    addNodeView() {
        return ReactNodeViewRenderer(EmbedView);
    },

    addCommands() {
        return {
            insertEmbed:
                (attrs) =>
                ({ commands }) =>
                    commands.insertContent([
                        { type: this.name, attrs },
                        { type: 'paragraph' },
                    ]),
        };
    },
});
