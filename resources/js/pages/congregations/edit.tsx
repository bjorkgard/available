import { Form, Head, usePage } from '@inertiajs/react';
import { useMemo } from 'react';
import { useTranslation } from 'react-i18next';

import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { edit, update, updateColor } from '@/routes/congregation';
import type { Congregation } from '@/types';

type CongregationPermissions = {
    canUpdateTeam: boolean;
    canDeleteTeam: boolean;
};

const LOCALE_LABELS: Record<string, string> = {
    sv: 'Svenska',
    en: 'English',
};

type Props = {
    team: Congregation & { slug: string; locale: string };
    permissions: CongregationPermissions;
};

export default function CongregationEdit({ team, permissions }: Props) {
    const { t } = useTranslation();
    const { supportedLocales } = usePage().props;

    const pageTitle = useMemo(
        () =>
            permissions.canUpdateTeam
                ? `${t('Redigera')} ${team.name}`
                : team.name,
        [permissions.canUpdateTeam, team.name, t],
    );

    return (
        <>
            <Head title={pageTitle} />

            <div className="mx-auto w-full max-w-2xl px-4 py-6">
                <div className="flex flex-col space-y-8">
                    <Heading
                        title={pageTitle}
                        description={
                            permissions.canUpdateTeam
                                ? t(
                                      'Uppdatera ditt församlingsnamn och inställningar',
                                  )
                                : undefined
                        }
                    />

                    {permissions.canUpdateTeam ? (
                        <Card>
                            <CardHeader>
                                <CardTitle>
                                    {t('Församlingsinställningar')}
                                </CardTitle>
                                <CardDescription>
                                    {t(
                                        'Uppdatera ditt församlingsnamn och inställningar',
                                    )}
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Form
                                    {...update.form(team.slug)}
                                    className="space-y-6"
                                >
                                    {({ errors, processing }) => (
                                        <>
                                            <div className="grid gap-2">
                                                <Label htmlFor="name">
                                                    {t('Församlingsnamn')}
                                                </Label>
                                                <Input
                                                    id="name"
                                                    name="name"
                                                    data-test="congregation-name-input"
                                                    defaultValue={team.name}
                                                    required
                                                />
                                                <InputError
                                                    message={errors.name}
                                                />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor="congregation_number">
                                                    {t('Församlingsnummer')}
                                                </Label>
                                                <Input
                                                    id="congregation_number"
                                                    name="congregation_number"
                                                    data-test="congregation-number-input"
                                                    defaultValue={
                                                        team.congregation_number
                                                    }
                                                    required
                                                />
                                                <p className="text-sm text-muted-foreground">
                                                    {t(
                                                        'Bara siffror och versaler (A–Z), max 20 tecken',
                                                    )}
                                                </p>
                                                <InputError
                                                    message={
                                                        errors.congregation_number
                                                    }
                                                />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor="locale">
                                                    {t('Språk')}
                                                </Label>
                                                <Select
                                                    name="locale"
                                                    defaultValue={team.locale}
                                                >
                                                    <SelectTrigger
                                                        id="locale"
                                                        data-test="congregation-locale-select"
                                                        className="w-full"
                                                    >
                                                        <SelectValue
                                                            placeholder={t(
                                                                'Välj språk',
                                                            )}
                                                        />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {supportedLocales.map(
                                                            (loc) => (
                                                                <SelectItem
                                                                    key={loc}
                                                                    value={loc}
                                                                >
                                                                    {LOCALE_LABELS[
                                                                        loc
                                                                    ] ?? loc}
                                                                </SelectItem>
                                                            ),
                                                        )}
                                                    </SelectContent>
                                                </Select>
                                                <p className="text-sm text-muted-foreground">
                                                    {t(
                                                        'Standardspråk för församlingsnotifikationer och nya medlemmar',
                                                    )}
                                                </p>
                                                <InputError
                                                    message={errors.locale}
                                                />
                                            </div>

                                            <div className="flex items-center gap-4">
                                                <Button
                                                    type="submit"
                                                    data-test="congregation-save-button"
                                                    disabled={processing}
                                                >
                                                    {t('Spara')}
                                                </Button>
                                            </div>
                                        </>
                                    )}
                                </Form>
                            </CardContent>
                        </Card>
                    ) : null}

                    {permissions.canUpdateTeam ? (
                        <Card>
                            <CardHeader>
                                <CardTitle>{t('Församlingsfärg')}</CardTitle>
                                <CardDescription>
                                    {t('Välj en färg för din församling')}
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Form
                                    {...updateColor.form(team.slug)}
                                    className="space-y-6"
                                >
                                    {({ errors, processing }) => (
                                        <>
                                            <div className="grid gap-2">
                                                <Label htmlFor="color">
                                                    {t('Färg')}
                                                </Label>
                                                <div className="flex items-center gap-3">
                                                    <Input
                                                        id="color"
                                                        name="color"
                                                        data-test="congregation-color-input"
                                                        defaultValue={
                                                            team.color ?? ''
                                                        }
                                                        placeholder="#3B82F6"
                                                        className="max-w-40 font-mono uppercase"
                                                        type="color"
                                                    />
                                                </div>
                                                <p className="text-sm text-muted-foreground">
                                                    {t(
                                                        'Välj en färg och klicka på Spara',
                                                    )}
                                                </p>
                                                <InputError
                                                    message={errors.color}
                                                />
                                            </div>

                                            <div className="flex items-center gap-4">
                                                <Button
                                                    type="submit"
                                                    data-test="congregation-color-save-button"
                                                    disabled={processing}
                                                >
                                                    {t('Spara färg')}
                                                </Button>
                                            </div>
                                        </>
                                    )}
                                </Form>
                            </CardContent>
                        </Card>
                    ) : null}

                    {permissions.canDeleteTeam ? (
                        <Card>
                            <CardHeader>
                                <CardTitle>{t('Ta bort församling')}</CardTitle>
                                <CardDescription>
                                    {t('Ta bort din församling permanent')}
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10">
                                    <div className="relative space-y-0.5 text-red-600 dark:text-red-100">
                                        <p className="font-medium">
                                            {t('Varning')}
                                        </p>
                                        <p className="text-sm">
                                            {t(
                                                'Var försiktig, detta kan inte ångras.',
                                            )}
                                        </p>
                                    </div>
                                    <Button
                                        variant="destructive"
                                        data-test="delete-congregation-button"
                                    >
                                        {t('Ta bort församling')}
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    ) : null}
                </div>
            </div>
        </>
    );
}

CongregationEdit.layout = (props: {
    team: { name: string; slug: string };
}) => ({
    breadcrumbs: [
        {
            title: props.team.name,
            href: edit(props.team.slug),
        },
    ],
});
