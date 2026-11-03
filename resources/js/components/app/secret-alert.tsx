import { Copy, KeyRound } from 'lucide-react';
import { toast } from 'sonner';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/hooks/use-translation';

/**
 * A secret shown once (tokens, client secrets), with a copy button.
 */
export function SecretAlert({
    label,
    value,
}: {
    label: string;
    value: string;
}) {
    const { t } = useTranslation();

    return (
        <Alert>
            <KeyRound />
            <AlertDescription className="space-y-2">
                <p>{label}</p>
                <div className="flex gap-2">
                    <code className="flex-1 overflow-x-auto rounded bg-muted px-2 py-1 text-xs">
                        {value}
                    </code>
                    <Button
                        size="sm"
                        variant="outline"
                        onClick={() => {
                            void navigator.clipboard.writeText(value);
                            toast.success(t('Copied.'));
                        }}
                    >
                        <Copy />
                        {t('Copy')}
                    </Button>
                </div>
            </AlertDescription>
        </Alert>
    );
}
