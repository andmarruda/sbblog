import { Loader2, Upload } from 'lucide-react';
import { useRef, useState } from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { useBlogRoute } from '@/hooks/use-blog-route';
import { useTranslation } from '@/hooks/use-translation';
import { http, HttpError } from '@/lib/http';
import { store } from '@/routes/media';
import type { MediaItem } from '@/types';

type Props = {
    onUploaded: (media: MediaItem) => void;
    multiple?: boolean;
    variant?: 'default' | 'outline';
};

export const ACCEPTED_IMAGES =
    'image/jpeg,image/png,image/gif,image/webp,image/avif';

export async function uploadMedia(url: string, file: File): Promise<MediaItem> {
    const body = new FormData();
    body.append('file', file);

    return http<MediaItem>(url, { method: 'POST', body });
}

export function UploadButton({
    onUploaded,
    multiple = false,
    variant = 'default',
}: Props) {
    const { t } = useTranslation();
    const args = useBlogRoute();
    const input = useRef<HTMLInputElement>(null);
    const [busy, setBusy] = useState(false);

    const upload = async (files: FileList | null) => {
        if (!files || files.length === 0) {
            return;
        }

        setBusy(true);

        for (const file of Array.from(files)) {
            try {
                onUploaded(await uploadMedia(store(args).url, file));
            } catch (error) {
                toast.error(
                    `${file.name}: ${error instanceof HttpError ? error.first() : t('Upload failed.')}`,
                );
            }
        }

        setBusy(false);

        if (input.current) {
            input.current.value = '';
        }
    };

    return (
        <>
            <input
                ref={input}
                type="file"
                accept={ACCEPTED_IMAGES}
                multiple={multiple}
                className="hidden"
                onChange={(event) => void upload(event.target.files)}
            />
            <Button
                type="button"
                variant={variant}
                disabled={busy}
                onClick={() => input.current?.click()}
            >
                {busy ? <Loader2 className="animate-spin" /> : <Upload />}
                {t('Upload')}
            </Button>
        </>
    );
}
