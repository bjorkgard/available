import { Head, router } from '@inertiajs/react';
import { UserPlus } from 'lucide-react';
import { useState } from 'react';

import Heading from '@/components/heading';
import InviteMemberDialog from '@/components/invite-member-dialog';
import RoleSelect from '@/components/role-select';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { Congregation, CongregationRole, Membership } from '@/types';

type Props = {
    members: Membership[];
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
    member: 'Member',
};

function canManage(viewerRole: CongregationRole): boolean {
    return viewerRole === 'superadmin' || viewerRole === 'admin';
}

export default function MembersIndex({
    members,
    congregation,
    viewerRole,
}: Props) {
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
                `Remove ${membership.user?.name ?? 'this member'} from the congregation?`,
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
            <Head title="Members" />

            <h1 className="sr-only">Members</h1>

            <div className="mx-auto w-full max-w-2xl px-4 py-6 flex flex-col space-y-6">
                <div className="flex items-center justify-between">
                    <Heading
                        variant="small"
                        title="Members"
                        description="Manage congregation members and roles"
                    />

                    {canManage(viewerRole) && (
                        <Button
                            data-test="invite-member-button"
                            onClick={() => setInviteOpen(true)}
                        >
                            <UserPlus /> Invite
                        </Button>
                    )}
                </div>

                <div className="space-y-3">
                    {members.map((membership) => (
                        <div
                            key={membership.id}
                            data-test="member-row"
                            className="flex items-center justify-between rounded-lg border p-4"
                        >
                            <div className="flex flex-col gap-0.5">
                                <span className="font-medium">
                                    {membership.user?.name}
                                </span>
                                <span className="text-sm text-muted-foreground">
                                    {membership.user?.email}
                                </span>
                            </div>

                            <div className="flex items-center gap-3">
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
                                            Remove
                                        </Button>
                                    </>
                                ) : (
                                    <Badge
                                        variant={
                                            roleBadgeVariant[membership.role]
                                        }
                                    >
                                        {roleLabel[membership.role]}
                                    </Badge>
                                )}
                            </div>
                        </div>
                    ))}

                    {members.length === 0 && (
                        <p className="py-8 text-center text-muted-foreground">
                            No members yet.
                        </p>
                    )}
                </div>
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
            title: 'Members',
            href: props.congregation
                ? `/${props.congregation.slug}/members`
                : '#',
        },
    ],
});
