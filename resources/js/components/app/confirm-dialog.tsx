import { useState, type ReactNode } from 'react';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Input } from '@/components/ui/input';
import { useTranslation } from '@/hooks/use-translation';

type Props = {
    /** The element that opens the dialog. */
    trigger: ReactNode;
    /** Already translated. */
    title: string;
    description?: string;
    confirmLabel?: string;
    destructive?: boolean;
    /** When set, the user must type this text to confirm (e.g. a slug). */
    confirmText?: string;
    processing?: boolean;
    onConfirm: () => void;
};

export function ConfirmDialog({
    trigger,
    title,
    description,
    confirmLabel,
    destructive = false,
    confirmText,
    processing = false,
    onConfirm,
}: Props) {
    const { t } = useTranslation();
    const [typed, setTyped] = useState('');
    const blocked =
        processing || (confirmText !== undefined && typed !== confirmText);

    return (
        <AlertDialog onOpenChange={() => setTyped('')}>
            <AlertDialogTrigger asChild>{trigger}</AlertDialogTrigger>
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>{title}</AlertDialogTitle>
                    {description && (
                        <AlertDialogDescription>
                            {description}
                        </AlertDialogDescription>
                    )}
                </AlertDialogHeader>
                {confirmText !== undefined && (
                    <div className="space-y-2">
                        <p className="text-sm text-muted-foreground">
                            {t('Type :text to confirm.', { text: confirmText })}
                        </p>
                        <Input
                            value={typed}
                            onChange={(event) => setTyped(event.target.value)}
                            autoComplete="off"
                        />
                    </div>
                )}
                <AlertDialogFooter>
                    <AlertDialogCancel>{t('Cancel')}</AlertDialogCancel>
                    <AlertDialogAction
                        variant={destructive ? 'destructive' : 'default'}
                        disabled={blocked}
                        onClick={onConfirm}
                    >
                        {confirmLabel ?? t('Confirm')}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}
