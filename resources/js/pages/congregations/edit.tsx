import { Form, Head } from '@inertiajs/react';
import { useMemo } from 'react';

import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit, update, updateColor } from '@/routes/congregation';
import type { Congregation } from '@/types';

type CongregationPermissions = {
    canUpdateTeam: boolean;
    canDeleteTeam: boolean;
};

type Props = {
    team: Congregation & { slug: string };
    permissions: CongregationPermissions;
};

export default function CongregationEdit({ team, permissions }: Props) {
    const pageTitle = useMemo(
        () =>
            permissions.canUpdateTeam
                ? `Edit ${team.name}`
                : `View ${team.name}`,
        [permissions.canUpdateTeam, team.name],
    );

    return (
        <>
            <Head title={pageTitle} />

            <h1 className="sr-only">{pageTitle}</h1>

            <div className="mx-auto w-full max-w-2xl px-4 py-6">
                <div className="flex flex-col space-y-10">
                    <div className="space-y-6">
                        {permissions.canUpdateTeam ? (
                            <>
                                <Heading
                                    variant="small"
                                    title="Congregation settings"
                                    description="Update your congregation name and settings"
                                />

                                <Form
                                    {...update.form(team.slug)}
                                    className="space-y-6"
                                >
                                    {({ errors, processing }) => (
                                        <>
                                            <div className="grid gap-2">
                                                <Label htmlFor="name">
                                                    Congregation name
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
                                                    Congregation number
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
                                                    Only digits and uppercase
                                                    letters (A–Z), max 20
                                                    characters
                                                </p>
                                                <InputError
                                                    message={
                                                        errors.congregation_number
                                                    }
                                                />
                                            </div>

                                            <div className="flex items-center gap-4">
                                                <Button
                                                    type="submit"
                                                    data-test="congregation-save-button"
                                                    disabled={processing}
                                                >
                                                    Save
                                                </Button>
                                            </div>
                                        </>
                                    )}
                                </Form>
                            </>
                        ) : (
                            <Heading variant="small" title={team.name} />
                        )}
                    </div>

                    {permissions.canUpdateTeam ? (
                        <div className="space-y-6">
                            <Heading
                                variant="small"
                                title="Congregation color"
                                description="Choose a color to identify your congregation"
                            />

                            <Form
                                {...updateColor.form(team.slug)}
                                className="space-y-6"
                            >
                                {({ errors, processing }) => (
                                    <>
                                        <div className="grid gap-2">
                                            <Label htmlFor="color">Color</Label>
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
                                                Select a color and click on Save
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
                                                Save color
                                            </Button>
                                        </div>
                                    </>
                                )}
                            </Form>
                        </div>
                    ) : null}

                    {permissions.canDeleteTeam ? (
                        <div className="space-y-6">
                            <Heading
                                variant="small"
                                title="Delete congregation"
                                description="Permanently delete your congregation"
                            />
                            <div className="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10">
                                <div className="relative space-y-0.5 text-red-600 dark:text-red-100">
                                    <p className="font-medium">Warning</p>
                                    <p className="text-sm">
                                        Please proceed with caution, this cannot
                                        be undone.
                                    </p>
                                </div>
                                <Button
                                    variant="destructive"
                                    data-test="delete-congregation-button"
                                >
                                    Delete congregation
                                </Button>
                            </div>
                        </div>
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
