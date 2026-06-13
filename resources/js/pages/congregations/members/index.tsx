import { Head, router } from '@inertiajs/react';
import { Clock, Trash2, UserPlus } from 'lucide-react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';

import Heading from '@/components/heading';
import InviteMemberDialog from '@/components/invite-member-dialog';
import RoleSelect from '@/components/role-select';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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

function formatTimeLeft(
    expiresAt: string,
    t: (key: string, options?: Record<string, unknown>) => string,
): string {
    const now = Date.now();
    const expires = new Date(expiresAt).getTime();
    const diff = expires - now;

    if (diff <= 0) {
        return t('utgången');
    }

    const hours = Math.floor(diff / (1000 * 60 * 60));
    const days = Math.floor(hours / 24);

    if (days > 0) {
        return t('utgår om {{days}}d {{hours}}h', { days, hours: hours % 24 });
    }

    if (hours > 0) {
        return t('utgår om {{hours}}h', { hours });
    }

    const minutes = Math.floor(diff / (1000 * 60));

    return t('utgår om {{minutes}}m', { minutes });
}

function formatLastSeen(
    lastActiveAt: string | null,
    t: (key: string, options?: Record<string, unknown>) => string,
): string {
    if (!lastActiveAt) {
        return t('Aldrig');
    }

    const now = Date.now();
    const active = new Date(lastActiveAt).getTime();
    const diff = now - active;

    const minutes = Math.floor(diff / (1000 * 60));
    const hours = Math.floor(diff / (1000 * 60 * 60));
    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
    const weeks = Math.floor(days / 7);
    const months = Math.floor(days / 30);

    if (minutes < 5) {
        return t('Online nu');
    }

    if (minutes < 60) {
        return t('{{minutes}} min sedan', { minutes });
    }

    if (hours < 24) {
        return t('{{hours}} tim sedan', { hours });
    }

    if (days < 7) {
        return t('{{days}} dagar sedan', { days });
    }

    if (weeks < 5) {
        return t('{{weeks}} veckor sedan', { weeks });
    }

    return t('{{months}} månader sedan', { months });
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
                            className="grid grid-cols-[1fr_10rem_12rem] items-center gap-4 rounded-lg border p-4"
                        >
                            <div className="flex flex-col gap-0.5">
                                <span className="font-medium">
                                    {membership.user?.name}
                                </span>
                                <span className="text-sm text-muted-foreground">
                                    {membership.user?.email}
                                </span>
                            </div>

                            <div className="justify-self-start text-sm text-muted-foreground">
                                {formatLastSeen(
                                    membership.last_active_at,
                                    t,
                                )}
                            </div>

                            <div className="flex items-center justify-end gap-3">
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
                        <p className="py-8 text-center text-muted-foreground">
                            {t('Inga medlemmar ännu.')}
                        </p>
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
