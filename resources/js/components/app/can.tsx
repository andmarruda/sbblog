import type { ReactNode } from 'react';
import { useCan } from '@/hooks/use-can';

type Props = {
    /** All of these permission keys are required. */
    permission: string | string[];
    children: ReactNode;
    fallback?: ReactNode;
};

export function Can({ permission, children, fallback = null }: Props) {
    const can = useCan();
    const permissions = Array.isArray(permission) ? permission : [permission];

    return <>{can(...permissions) ? children : fallback}</>;
}
