import { Form, Head, router } from '@inertiajs/react';
import { ChevronDown, Mail, UserPlus, X } from 'lucide-react';
import { useMemo, useState } from 'react';

import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import InviteMemberDialog from '@/components/invite-member-dialog';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useInitials } from '@/hooks/use-initials';
import { edit, index, update } from '@/routes/congregations';
import { update as updateMember } from '@/routes/members';
import type { Congregation, RoleOption } from '@/types';

type CongregationPermissions = {
    canUpdateTeam: boolean;
    canDeleteTeam: boolean;
    canCreateInvitation: boolean;
    canUpdateMember: boolean;
    canRemoveMember: boolean;
    canCancelInvitation: boolean;
};

type MemberItem = {
    id: string;
    membership_id: string;
    name: string;
    email: string;
    avatar?: string;
    role: string;
    role_label: string;
};

type InvitationItem = {
    code: string;
    email: string;
    role: string;
    role_label: string;
    created_at: string;
};

type Props = {
    team: Congregation & { slug: string };
    members: MemberItem[];
    invitations: InvitationItem[];
    permissions: CongregationPermissions;
    availableRoles: RoleOption[];
};

export default function CongregationEdit({
    team,
    members,
    invitations,
    permissions,
    availableRoles,
}: Props) {
    const getInitials = useInitials();

    const [inviteDialogOpen, setInviteDialogOpen] = useState(false);

    const pageTitle = useMemo(
        () =>
            permissions.canUpdateTeam
                ? `Edit ${team.name}`
                : `View ${team.name}`,
        [permissions.canUpdateTeam, team.name],
    );

    const updateMemberRole = (member: MemberItem, newRole: string) => {
        router.visit(
            updateMember.url({
                current_congregation: team.slug,
                member: member.membership_id,
            }),
            {
                method: 'put',
                data: { role: newRole },
                preserveScroll: true,
            },
        );
    };

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

                    <div className="space-y-6">
                        <div className="flex items-center justify-between">
                            <Heading
                                variant="small"
                                title="Members"
                                description={
                                    permissions.canCreateInvitation
                                        ? 'Manage who belongs to this congregation'
                                        : ''
                                }
                            />

                            {permissions.canCreateInvitation ? (
                                <Button
                                    data-test="invite-member-button"
                                    onClick={() => setInviteDialogOpen(true)}
                                >
                                    <UserPlus /> Invite member
                                </Button>
                            ) : null}
                        </div>

                        <div className="space-y-3">
                            {members.map((member) => (
                                <div
                                    key={member.id}
                                    data-test="member-row"
                                    className="flex items-center justify-between rounded-lg border p-4"
                                >
                                    <div className="flex items-center gap-4">
                                        <Avatar className="h-10 w-10">
                                            {member.avatar ? (
                                                <AvatarImage
                                                    src={member.avatar}
                                                    alt={member.name}
                                                />
                                            ) : null}
                                            <AvatarFallback>
                                                {getInitials(member.name)}
                                            </AvatarFallback>
                                        </Avatar>
                                        <div>
                                            <div className="font-medium">
                                                {member.name}
                                            </div>
                                            <div className="text-sm text-muted-foreground">
                                                {member.email}
                                            </div>
                                        </div>
                                    </div>

                                    <div className="flex items-center gap-2">
                                        {permissions.canUpdateMember ? (
                                            <DropdownMenu>
                                                <DropdownMenuTrigger asChild>
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        data-test="member-role-trigger"
                                                    >
                                                        {member.role_label}
                                                        <ChevronDown className="ml-2 h-4 w-4 opacity-50" />
                                                    </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent>
                                                    {availableRoles.map(
                                                        (role) => (
                                                            <DropdownMenuItem
                                                                key={role.value}
                                                                data-test="member-role-option"
                                                                onSelect={() =>
                                                                    updateMemberRole(
                                                                        member,
                                                                        role.value,
                                                                    )
                                                                }
                                                            >
                                                                {role.label}
                                                            </DropdownMenuItem>
                                                        ),
                                                    )}
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        ) : (
                                            <Badge variant="secondary">
                                                {member.role_label}
                                            </Badge>
                                        )}

                                        {permissions.canRemoveMember ? (
                                            <TooltipProvider>
                                                <Tooltip>
                                                    <TooltipTrigger asChild>
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            data-test="member-remove-button"
                                                        >
                                                            <X className="h-4 w-4" />
                                                        </Button>
                                                    </TooltipTrigger>
                                                    <TooltipContent>
                                                        <p>Remove member</p>
                                                    </TooltipContent>
                                                </Tooltip>
                                            </TooltipProvider>
                                        ) : null}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>

                    {invitations.length > 0 ? (
                        <div className="space-y-6">
                            <Heading
                                variant="small"
                                title="Pending invitations"
                                description="Invitations that haven't been accepted yet"
                            />

                            <div className="space-y-3">
                                {invitations.map((invitation) => (
                                    <div
                                        key={invitation.code}
                                        data-test="invitation-row"
                                        className="flex items-center justify-between rounded-lg border p-4"
                                    >
                                        <div className="flex items-center gap-4">
                                            <div className="flex h-10 w-10 items-center justify-center rounded-full bg-muted">
                                                <Mail className="h-5 w-5 text-muted-foreground" />
                                            </div>
                                            <div>
                                                <div className="font-medium">
                                                    {invitation.email}
                                                </div>
                                                <div className="text-sm text-muted-foreground">
                                                    {invitation.role_label}
                                                </div>
                                            </div>
                                        </div>

                                        {permissions.canCancelInvitation ? (
                                            <TooltipProvider>
                                                <Tooltip>
                                                    <TooltipTrigger asChild>
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            data-test="invitation-cancel-button"
                                                        >
                                                            <X className="h-4 w-4" />
                                                        </Button>
                                                    </TooltipTrigger>
                                                    <TooltipContent>
                                                        <p>
                                                            Cancel invitation
                                                        </p>
                                                    </TooltipContent>
                                                </Tooltip>
                                            </TooltipProvider>
                                        ) : null}
                                    </div>
                                ))}
                            </div>
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

            {permissions.canCreateInvitation ? (
                <InviteMemberDialog
                    congregationSlug={team.slug}
                    viewerRole={permissions.canUpdateTeam ? 'admin' : 'member'}
                    open={inviteDialogOpen}
                    onOpenChange={setInviteDialogOpen}
                />
            ) : null}
        </>
    );
}

CongregationEdit.layout = (props: {
    team: { name: string; slug: string };
}) => ({
    breadcrumbs: [
        {
            title: 'Congregations',
            href: index(),
        },
        {
            title: props.team.name,
            href: edit(props.team.slug),
        },
    ],
});
