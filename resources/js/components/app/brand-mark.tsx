import { useId } from 'react';
import { cn } from '@/lib/utils';

/**
 * The SBBlog mark: lines of text and a cursor on an indigo-to-violet tile.
 * Kept in sync with public/favicon.svg.
 */
export function BrandMark({ className }: { className?: string }) {
    const gradient = useId();

    return (
        <svg
            viewBox="0 0 32 32"
            fill="none"
            aria-hidden="true"
            className={cn('size-8 shrink-0', className)}
        >
            <defs>
                <linearGradient
                    id={gradient}
                    x1="0"
                    y1="0"
                    x2="32"
                    y2="32"
                    gradientUnits="userSpaceOnUse"
                >
                    <stop offset="0" stopColor="#818CF8" />
                    <stop offset="1" stopColor="#7C3AED" />
                </linearGradient>
            </defs>
            <rect width="32" height="32" rx="8" fill={`url(#${gradient})`} />
            <rect x="8" y="9" width="16" height="3.5" rx="1.75" fill="#fff" />
            <rect
                x="8"
                y="14.25"
                width="11"
                height="3.5"
                rx="1.75"
                fill="#fff"
                fillOpacity=".85"
            />
            <rect
                x="8"
                y="19.5"
                width="6.5"
                height="3.5"
                rx="1.75"
                fill="#fff"
                fillOpacity=".7"
            />
            <circle cx="21.5" cy="21.25" r="2.25" fill="#FDE68A" />
        </svg>
    );
}
