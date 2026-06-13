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

            <div className="mx-auto w-full max-w-4xl px-4 py-6">
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
                                            <div className="grid gap-6 sm:grid-cols-2">
                                                <div className="grid gap-2">
                                                    <Label htmlFor="name">
                                                        {t('Församlingsnamn')}
                                                    </Label>
                                                    <Input
                                                        id="name"
                                                        name="name"
                                                        data-test="congregation-name-input"
                                                        defaultValue={
                                                            team.name
                                                        }
                                                        required
                                                    />
                                                    <InputError
                                                        message={errors.name}
                                                    />
                                                </div>

                                                <div className="grid gap-2">
                                                    <Label htmlFor="congregation_number">
                                                        {t(
                                                            'Församlingsnummer',
                                                        )}
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
                                                    <InputError
                                                        message={
                                                            errors.congregation_number
                                                        }
                                                    />
                                                </div>
                                            </div>

                                            <div className="grid gap-2 sm:max-w-[calc(50%-0.75rem)]">
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

                                            <div className="flex items-center gap-4 pt-2">
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
                                    {t(
                                        'Färgen visas i kalendern för att särskilja era bokningar',
                                    )}
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Form
                                    {...updateColor.form(team.slug)}
                                    className="space-y-6"
                                >
                                    {({ errors, processing }) => (
                                        <>
                                            <div className="flex items-end gap-4">
                                                <div className="grid gap-2">
                                                    <Label htmlFor="color">
                                                        {t('Färg')}
                                                    </Label>
                                                    <Input
                                                        id="color"
                                                        name="color"
                                                        data-test="congregation-color-input"
                                                        defaultValue={
                                                            team.color ?? ''
                                                        }
                                                        placeholder="#3B82F6"
                                                        className="max-w-40 p-0 font-mono uppercase"
                                                        type="color"
                                                    />
                                                    <p className="text-sm text-muted-foreground">
                                                        {t(
                                                            'Klicka på färgrutan för att välja en ny färg',
                                                        )}
                                                    </p>
                                                    <InputError
                                                        message={errors.color}
                                                    />
                                                </div>
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
                        <Card className="border-red-100 dark:border-red-200/10">
                            <CardHeader>
                                <CardTitle className="text-red-600 dark:text-red-400">
                                    {t('Ta bort församling')}
                                </CardTitle>
                                <CardDescription>
                                    {t(
                                        'Var försiktig, detta kan inte ångras.',
                                    )}
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Button
                                    variant="destructive"
                                    data-test="delete-congregation-button"
                                >
                                    {t('Ta bort församling')}
                                </Button>
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
