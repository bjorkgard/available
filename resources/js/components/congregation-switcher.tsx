import { router, usePage } from '@inertiajs/react';
import { Check, ChevronsUpDown, Church } from 'lucide-react';

import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useIsMobile } from '@/hooks/use-mobile';
import type { Congregation } from '@/types';

type CongregationSwitcherProps = {
    inHeader?: boolean;
};

export function CongregationSwitcher({
    inHeader = false,
}: CongregationSwitcherProps) {
    const page = usePage();
    const isMobile = useIsMobile();
    const currentCongregation = page.props
        .currentCongregation as Congregation | null;
    const congregations =
        (page.props.congregations as Congregation[] | undefined) ?? [];

    const switchCongregation = (congregation: Congregation) => {
        if (currentCongregation?.id === congregation.id) {
            return;
        }

        const previousSlug = currentCongregation?.slug;

        router.visit(`/${congregation.slug}/dashboard`, {
            onFinish: () => {
                if (!previousSlug || typeof window === 'undefined') {
                    router.reload();

                    return;
                }

                const currentUrl = `${window.location.pathname}${window.location.search}${window.location.hash}`;
                const segment = `/${previousSlug}`;

                if (currentUrl.includes(segment)) {
                    router.visit(
                        currentUrl.replace(segment, `/${congregation.slug}`),
                        {
                            replace: true,
                        },
                    );

                    return;
                }

                router.reload();
            },
        });
    };

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="ghost"
                    data-test="congregation-switcher-trigger"
                    className={
                        inHeader
                            ? 'h-8 gap-1 px-2'
                            : 'w-full justify-start px-2 has-[>svg]:px-2 data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground'
                    }
                >
                    <Church
                        className={
                            inHeader
                                ? 'hidden'
                                : 'hidden size-4 shrink-0 group-data-[collapsible=icon]:block'
                        }
                    />
                    <div
                        className={
                            inHeader
                                ? 'grid flex-1 text-left text-sm leading-tight'
                                : 'grid flex-1 text-left text-sm leading-tight group-data-[collapsible=icon]:hidden'
                        }
                    >
                        <span
                            className={
                                inHeader
                                    ? 'max-w-[120px] truncate font-medium'
                                    : 'truncate font-semibold'
                            }
                        >
                            {currentCongregation?.name ?? 'Select congregation'}
                        </span>
                    </div>
                    <ChevronsUpDown
                        className={
                            inHeader
                                ? 'size-4 opacity-50'
                                : 'ml-auto group-data-[collapsible=icon]:hidden'
                        }
                    />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent
                className={
                    inHeader
                        ? 'w-56'
                        : 'w-(--radix-dropdown-menu-trigger-width) min-w-56 rounded-lg'
                }
                side={inHeader ? undefined : isMobile ? 'bottom' : 'right'}
                align={inHeader ? 'end' : 'start'}
                sideOffset={inHeader ? undefined : 4}
            >
                <DropdownMenuLabel className="text-xs text-muted-foreground">
                    Congregations
                </DropdownMenuLabel>
                {congregations.map((congregation) => (
                    <DropdownMenuItem
                        key={congregation.id}
                        data-test="congregation-switcher-item"
                        className={
                            inHeader
                                ? 'cursor-pointer gap-2'
                                : 'cursor-pointer gap-2 p-2'
                        }
                        onSelect={() => switchCongregation(congregation)}
                    >
                        <span
                            className="size-3 shrink-0 rounded-full"
                            style={{
                                backgroundColor:
                                    congregation.color ?? '#94A3B8',
                            }}
                        />
                        {congregation.name}
                        {currentCongregation?.id === congregation.id && (
                            <Check
                                className={
                                    inHeader
                                        ? 'ml-auto size-4'
                                        : 'ml-auto h-4 w-4'
                                }
                            />
                        )}
                    </DropdownMenuItem>
                ))}
                {congregations.length === 0 && (
                    <DropdownMenuItem
                        disabled
                        className="text-muted-foreground"
                    >
                        No congregations
                    </DropdownMenuItem>
                )}
                <DropdownMenuSeparator />
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
