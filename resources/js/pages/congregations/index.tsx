import { Head, Link } from '@inertiajs/react';
import { Eye, Pencil } from 'lucide-react';
import { useTranslation } from 'react-i18next';

import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { edit } from '@/routes/congregation';
import { index } from '@/routes/congregations';

type CongregationListItem = {
    id: string;
    name: string;
    slug: string;
    congregation_number?: string;
    isPersonal?: boolean;
    role: string;
    roleLabel: string;
    isCurrent: boolean;
};

type Props = {
    teams: CongregationListItem[];
};

export default function CongregationsIndex({ teams }: Props) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('Församlingar')} />

            <h1 className="sr-only">{t('Församlingar')}</h1>

            <div className="flex flex-col space-y-6">
                <div className="flex items-center justify-between">
                    <Heading
                        variant="small"
                        title={t('Församlingar')}
                        description={t(
                            'Hantera dina församlingar och medlemskap',
                        )}
                    />
                </div>

                <div className="space-y-3">
                    {teams.map((congregation) => (
                        <div
                            key={congregation.id}
                            data-test="congregation-row"
                            className="flex items-center justify-between rounded-lg border p-4"
                        >
                            <div className="flex items-center gap-4">
                                <div>
                                    <div className="flex items-center gap-2">
                                        <span className="font-medium">
                                            {congregation.name}
                                        </span>
                                        {congregation.congregation_number ? (
                                            <Badge variant="secondary">
                                                #
                                                {
                                                    congregation.congregation_number
                                                }
                                            </Badge>
                                        ) : null}
                                    </div>
                                    <span className="text-sm text-muted-foreground">
                                        {congregation.roleLabel}
                                    </span>
                                </div>
                            </div>

                            <TooltipProvider>
                                <div className="flex items-center gap-2">
                                    {congregation.role === 'member' ? (
                                        <Tooltip>
                                            <TooltipTrigger asChild>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    data-test="congregation-view-button"
                                                    asChild
                                                >
                                                    <Link
                                                        href={edit(
                                                            congregation.slug,
                                                        )}
                                                    >
                                                        <Eye className="h-4 w-4" />
                                                    </Link>
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent>
                                                <p>{t('Visa församling')}</p>
                                            </TooltipContent>
                                        </Tooltip>
                                    ) : (
                                        <Tooltip>
                                            <TooltipTrigger asChild>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    data-test="congregation-edit-button"
                                                    asChild
                                                >
                                                    <Link
                                                        href={edit(
                                                            congregation.slug,
                                                        )}
                                                    >
                                                        <Pencil className="h-4 w-4" />
                                                    </Link>
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent>
                                                <p>
                                                    {t('Redigera församling')}
                                                </p>
                                            </TooltipContent>
                                        </Tooltip>
                                    )}
                                </div>
                            </TooltipProvider>
                        </div>
                    ))}

                    {teams.length === 0 ? (
                        <p className="py-8 text-center text-muted-foreground">
                            {t('Du tillhör inga församlingar ännu.')}
                        </p>
                    ) : null}
                </div>
            </div>
        </>
    );
}

CongregationsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Församlingar',
            href: index(),
        },
    ],
};
