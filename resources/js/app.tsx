import { createInertiaApp } from '@inertiajs/react';
import { initializeTheme } from '@/hooks/use-appearance';
import AuthLayout from '@/layouts/auth-layout';
import ConsoleLayout from '@/layouts/console-layout';

const appName = import.meta.env.VITE_APP_NAME || 'SBBlog';

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    // Pages pick their layout by folder; a page can still set its own `layout`.
    layout: (name) => {
        if (name.startsWith('auth/')) {
            return AuthLayout;
        }

        if (name === 'welcome' || name.startsWith('errors/')) {
            return null;
        }

        return ConsoleLayout;
    },
    progress: {
        color: '#4B5563',
    },
});

initializeTheme();
