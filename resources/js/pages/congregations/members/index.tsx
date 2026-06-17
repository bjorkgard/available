import { Head, router } from '@inertiajs/react';
import { Clock, Trash2, UserPlus } from 'lucide-react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';

import Heading from '@/components/heading';
import InviteMemberDialog from '@/components/invite-member-dialog';
import RoleSelect from '@/components/role-select';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatLastSeen, formatTimeLeft } from '@/lib/format-utils';
import type {
    Congregation,
    CongregationInvitation,
    CongregationRole,
    Membership,
} from '@/types';

type Props = {
    members: Membership[];
    pendingInvitations: CongregationInvitation[];
    congregation: Congregation;
    viewerRole: CongregationRole;
};

const roleBadgeVariant: Record<
    CongregationRole,
    'default' | 'secondary' | 'outline'
> = {
    superadmin: 'default',
    admin: 'secondary',
    member: 'outline',
};

const roleLabel: Record<CongregationRole, string> = {
    superadmin: 'Superadmin',
    admin: 'Admin',
    member: 'Medlem',
};

function canManage(viewerRole: CongregationRole): boolean {
    return viewerRole === 'superadmin' || viewerRole === 'admin';
}

export default function MembersIndex({
    members,
    pendingInvitations,
    congregation,
    viewerRole,
}: Props) {
    const { t } = useTranslation();
    const [inviteOpen, setInviteOpen] = useState(false);

    const handleRoleChange = (
        membership: Membership,
        newRole: CongregationRole,
    ) => {
        router.put(
            `/${congregation.slug}/members/${membership.id}`,
            { role: newRole },
            { preserveScroll: true },
        );
    };

    const handleRemove = (membership: Membership) => {
        if (
            !confirm(
                t('Ta bort {{name}} från församlingen?', {
                    name: membership.user?.name ?? t('denna medlem'),
                }),
            )
        ) {
            return;
        }

        router.delete(`/${congregation.slug}/members/${membership.id}`, {
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title={t('Medlemmar')} />

            <h1 className="sr-only">{t('Medlemmar')}</h1>

            <div className="mx-auto flex w-full max-w-4xl flex-col space-y-6 px-4 py-6">
                <div className="flex items-center justify-between">
                    <Heading
                        variant="small"
                        title={t('Medlemmar')}
                        description={t(
                            'Hantera församlingsmedlemmar och roller',
                        )}
                    />

                    {canManage(viewerRole) && (
                        <Button
                            data-test="invite-member-button"
                            onClick={() => setInviteOpen(true)}
                        >
                            <UserPlus /> {t('Bjud in')}
                        </Button>
                    )}
                </div>

                <div className="space-y-3">
                    {members.map((membership) => (
                        <div
                            key={membership.id}
                            data-test="member-row"
                            className="flex flex-wrap items-center gap-3 rounded-lg border p-4 sm:grid sm:grid-cols-[1fr_10rem_12rem] sm:gap-4"
                        >
                            <div className="flex min-w-0 flex-1 flex-col gap-0.5">
                                <span className="font-medium">
                                    {membership.user?.name}
                                </span>
                                <span className="truncate text-sm text-muted-foreground">
                                    {membership.user?.email}
                                </span>
                            </div>

                            <div className="w-full text-sm text-muted-foreground sm:w-auto sm:justify-self-start">
                                {formatLastSeen(membership.last_active_at, t)}
                            </div>

                            <div className="flex w-full items-center gap-3 sm:w-auto sm:justify-end">
                                {canManage(viewerRole) ? (
                                    <>
                                        <RoleSelect
                                            value={membership.role}
                                            onChange={(newRole) =>
                                                handleRoleChange(
                                                    membership,
                                                    newRole,
                                                )
                                            }
                                            viewerRole={viewerRole}
                                        />
                                        <Button
                                            variant="destructive"
                                            size="sm"
                                            data-test="remove-member-button"
                                            onClick={() =>
                                                handleRemove(membership)
                                            }
                                        >
                                            {t('Ta bort')}
                                        </Button>
                                    </>
                                ) : (
                                    <Badge
                                        variant={
                                            roleBadgeVariant[membership.role]
                                        }
                                    >
                                        {t(roleLabel[membership.role])}
                                    </Badge>
                                )}
                            </div>
                        </div>
                    ))}

                    {members.length === 0 && (
                        <div className="flex flex-col items-center justify-center rounded-lg border border-dashed py-12">
                            <p className="text-sm text-muted-foreground">
                                {t('Inga medlemmar ännu.')}
                            </p>
                            {canManage(viewerRole) && (
                                <Button
                                    variant="outline"
                                    size="sm"
                                    className="mt-3"
                                    onClick={() => setInviteOpen(true)}
                                >
                                    <UserPlus className="size-3.5" />{' '}
                                    {t('Bjud in din första medlem')}
                                </Button>
                            )}
                        </div>
                    )}
                </div>

                {canManage(viewerRole) && pendingInvitations.length > 0 && (
                    <div className="space-y-3">
                        <h2 className="text-sm font-medium text-muted-foreground">
                            <Clock className="mr-1.5 inline-block h-3.5 w-3.5" />
                            {t('Väntande inbjudningar')}
                        </h2>

                        <div className="space-y-2">
                            {pendingInvitations.map((invitation) => (
                                <div
                                    key={invitation.id}
                                    className="flex items-center justify-between rounded-md border border-dashed px-4 py-3"
                                >
                                    <div className="flex flex-col gap-0.5">
                                        <span className="text-sm font-medium text-muted-foreground">
                                            {invitation.name}
                                        </span>
                                        <span className="text-xs text-muted-foreground/70">
                                            {invitation.email}
                                        </span>
                                        <span className="text-xs text-muted-foreground/50">
                                            {t('Skickad')}{' '}
                                            {new Date(
                                                invitation.created_at,
                                            ).toLocaleDateString(undefined, {
                                                month: 'short',
                                                day: 'numeric',
                                            })}
                                            {invitation.expires_at && (
                                                <>
                                                    {' · '}
                                                    {formatTimeLeft(
                                                        invitation.expires_at,
                                                        t,
                                                    )}
                                                </>
                                            )}
                                        </span>
                                    </div>

                                    <div className="flex items-center gap-2">
                                        <Badge
                                            variant="outline"
                                            className="text-xs"
                                        >
                                            {t(roleLabel[invitation.role])}
                                        </Badge>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            className="h-7 w-7 text-muted-foreground hover:text-destructive"
                                            onClick={() =>
                                                router.delete(
                                                    `/${congregation.slug}/members/invitations/${invitation.code}`,
                                                    { preserveScroll: true },
                                                )
                                            }
                                        >
                                            <Trash2 className="h-3.5 w-3.5" />
                                            <span className="sr-only">
                                                {t('Avbryt inbjudan')}
                                            </span>
                                        </Button>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                )}
            </div>

            <InviteMemberDialog
                congregationSlug={congregation.slug}
                viewerRole={viewerRole}
                open={inviteOpen}
                onOpenChange={setInviteOpen}
            />
        </>
    );
}

MembersIndex.layout = (props: { congregation?: { slug: string } }) => ({
    breadcrumbs: [
        {
            title: 'Medlemmar',
            href: props.congregation
                ? `/${props.congregation.slug}/members`
                : '#',
        },
    ],
});
