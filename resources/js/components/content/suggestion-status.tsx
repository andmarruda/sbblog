import { Badge } from '@/components/ui/badge';
import { useTranslation } from '@/hooks/use-translation';

export function SuggestionStatusBadge({
    status,
}: {
    status: 'pending' | 'accepted' | 'rejected' | 'superseded';
}) {
    const { t } = useTranslation();

    const labels = {
        pending: t('Pending'),
        accepted: t('Accepted'),
        rejected: t('Rejected'),
        superseded: t('Outdated'),
    };

    return (
        <Badge variant={status === 'pending' ? 'default' : 'outline'}>
            {labels[status]}
        </Badge>
    );
}
