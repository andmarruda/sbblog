import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { AppSidebar } from '@/components/app/app-sidebar';
import { Breadcrumbs } from '@/components/app/breadcrumbs';
import { FlashToaster } from '@/components/app/flash-toaster';
import { Separator } from '@/components/ui/separator';
import {
    SidebarInset,
    SidebarProvider,
    SidebarTrigger,
} from '@/components/ui/sidebar';
import { TooltipProvider } from '@/components/ui/tooltip';
import type { BreadcrumbItem } from '@/types';

type Props = {
    children: ReactNode;
    breadcrumbs?: BreadcrumbItem[];
};

export default function ConsoleLayout({ children, breadcrumbs = [] }: Props) {
    const { sidebarOpen } = usePage().props;

    return (
        <TooltipProvider delayDuration={0}>
            <SidebarProvider defaultOpen={sidebarOpen}>
                <AppSidebar />
                <SidebarInset className="min-w-0">
                    <header className="flex h-14 shrink-0 items-center gap-2 border-b px-4">
                        <SidebarTrigger className="-ml-1" />
                        <Separator
                            orientation="vertical"
                            className="mr-2 data-[orientation=vertical]:h-4"
                        />
                        <Breadcrumbs items={breadcrumbs} />
                    </header>
                    <main className="flex min-w-0 flex-1 flex-col gap-6 p-4 md:p-6">
                        {children}
                    </main>
                </SidebarInset>
                <FlashToaster />
            </SidebarProvider>
        </TooltipProvider>
    );
}
