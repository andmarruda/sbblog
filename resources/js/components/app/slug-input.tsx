import { useEffect, useRef, useState, type ComponentProps } from 'react';
import { Input } from '@/components/ui/input';
import { slugify } from '@/lib/slug';

type Props = Omit<ComponentProps<typeof Input>, 'value' | 'onChange'> & {
    value: string;
    onChange: (value: string) => void;
    /** Text the slug follows until the user edits the slug by hand (e.g. the title). */
    source?: string;
};

export function SlugInput({ value, onChange, source, ...props }: Props) {
    // An existing slug is never rewritten from the source.
    const [touched, setTouched] = useState(value !== '');
    // Parents usually pass an inline callback; keep the latest without re-running the effect.
    const onChangeRef = useRef(onChange);
    onChangeRef.current = onChange;

    useEffect(() => {
        if (!touched && source !== undefined) {
            onChangeRef.current(slugify(source));
        }
    }, [source, touched]);

    return (
        <Input
            {...props}
            value={value}
            onChange={(event) => {
                setTouched(true);
                onChange(event.target.value);
            }}
            onBlur={() => onChange(slugify(value))}
            spellCheck={false}
            autoCapitalize="off"
        />
    );
}
