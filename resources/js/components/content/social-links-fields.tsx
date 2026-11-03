import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export type SocialNetwork = { id: string; key: string; name: string };

type Props = {
    networks: SocialNetwork[];
    value: Record<string, string>;
    onChange: (value: Record<string, string>) => void;
    errors?: Record<string, string>;
    /** Prefix of the error keys, e.g. "social_links". */
    errorKey: string;
};

export function SocialLinksFields({
    networks,
    value,
    onChange,
    errors = {},
    errorKey,
}: Props) {
    return (
        <div className="grid gap-3">
            {networks.map((network) => (
                <div
                    key={network.id}
                    className="grid gap-1.5 sm:grid-cols-[8rem_1fr] sm:items-center"
                >
                    <Label htmlFor={`social-${network.id}`}>
                        {network.name}
                    </Label>
                    <div>
                        <Input
                            id={`social-${network.id}`}
                            type="url"
                            placeholder="https://"
                            value={value[network.id] ?? ''}
                            onChange={(event) =>
                                onChange({
                                    ...value,
                                    [network.id]: event.target.value,
                                })
                            }
                            aria-invalid={
                                errors[`${errorKey}.${network.id}`]
                                    ? true
                                    : undefined
                            }
                        />
                        {errors[`${errorKey}.${network.id}`] && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors[`${errorKey}.${network.id}`]}
                            </p>
                        )}
                    </div>
                </div>
            ))}
        </div>
    );
}
