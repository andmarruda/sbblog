import { CircleCheck } from 'lucide-react';
import { Alert, AlertDescription } from '@/components/ui/alert';

export function StatusMessage({ message }: { message?: string | null }) {
    if (!message) {
        return null;
    }

    return (
        <Alert>
            <CircleCheck />
            <AlertDescription>{message}</AlertDescription>
        </Alert>
    );
}
