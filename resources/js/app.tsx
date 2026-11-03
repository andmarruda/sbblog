import { createInertiaApp } from '@inertiajs/react';
import { initializeTheme } from '@/hooks/use-appearance';
import AuthLayout from '@/layouts/auth-layout';
import ConsoleLayout from '@/layouts/console-layout';
import BlogSettingsLayout from '@/layouts/blog-settings-layout';
import SettingsLayout from '@/layouts/settings-layout';
import WorkspaceSettingsLayout from '@/layouts/workspace-settings-layout';

const appName = import.meta.env.VITE_APP_NAME || 'SBBlog';

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    // Pages pick their layout by folder; a page can still set its own `layout`.
    layout: (name) => {
        if (
            name.startsWith('auth/') ||
            name.startsWith('invitations/') ||
            name === 'onboarding'
        ) {
            return AuthLayout;
        }

        if (name.startsWith('errors/')) {
            return null;
        }

        if (name.startsWith('settings/')) {
            return [ConsoleLayout, SettingsLayout];
        }

        if (name.startsWith('workspace-settings/')) {
            return [ConsoleLayout, WorkspaceSettingsLayout];
        }

        if (name.startsWith('blog-settings/')) {
            return [ConsoleLayout, BlogSettingsLayout];
        }

        return ConsoleLayout;
    },
    progress: {
        color: '#4B5563',
    },
});

initializeTheme();
