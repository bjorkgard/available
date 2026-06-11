import { Form } from '@inertiajs/react';
import { useMemo, useState } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { CongregationRole, RoleOption } from '@/types';

type Props = {
    congregationSlug: string;
    viewerRole: CongregationRole;
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

const allRoleOptions: RoleOption[] = [
    { value: 'member', label: 'Medlem' },
    { value: 'admin', label: 'Admin' },
    { value: 'superadmin', label: 'Superadmin' },
];

export default function InviteMemberDialog({
    congregationSlug,
    viewerRole,
    open,
    onOpenChange,
}: Props) {
    const [selectedRole, setSelectedRole] =
        useState<CongregationRole>('member');

    const availableRoles = useMemo<RoleOption[]>(() => {
        if (viewerRole === 'superadmin') {
            return allRoleOptions;
        }

        // Admin can only assign member or admin roles
        return allRoleOptions.filter(
            (role) => role.value === 'member' || role.value === 'admin',
        );
    }, [viewerRole]);

    const handleOpenChange = (nextOpen: boolean) => {
        onOpenChange(nextOpen);

        if (!nextOpen) {
            setSelectedRole('member');
        }
    };

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent>
                <Form
                    key={String(open)}
                    action={`/${congregationSlug}/members/invite`}
                    method="post"
                    className="space-y-6"
                    onSuccess={() => onOpenChange(false)}
                >
                    {({ errors, processing }) => (
                        <>
                            <DialogHeader>
                                <DialogTitle>Bjud in en medlem</DialogTitle>
                                <DialogDescription>
                                    Skicka en inbjudan att gå med i denna
                                    församling.
                                </DialogDescription>
                            </DialogHeader>

                            <div className="grid gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="invite-name">Namn</Label>
                                    <Input
                                        id="invite-name"
                                        name="name"
                                        type="text"
                                        data-test="invite-name"
                                        placeholder="Förnamn Efternamn"
                                        required
                                    />
                                    <InputError message={errors.name} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="invite-email">
                                        E-postadress
                                    </Label>
                                    <Input
                                        id="invite-email"
                                        name="email"
                                        type="email"
                                        data-test="invite-email"
                                        placeholder="kollega@example.com"
                                        required
                                    />
                                    <InputError message={errors.email} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="invite-role">Roll</Label>
                                    <Select
                                        name="role"
                                        value={selectedRole}
                                        onValueChange={(value) =>
                                            setSelectedRole(
                                                value as CongregationRole,
                                            )
                                        }
                                    >
                                        <SelectTrigger
                                            id="invite-role"
                                            data-test="invite-role"
                                            className="w-full"
                                        >
                                            <SelectValue placeholder="Välj en roll" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {availableRoles.map((role) => (
                                                <SelectItem
                                                    key={role.value}
                                                    value={role.value}
                                                >
                                                    {role.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.role} />
                                </div>
                            </div>

                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button variant="secondary">Avbryt</Button>
                                </DialogClose>

                                <Button
                                    type="submit"
                                    data-test="invite-submit"
                                    disabled={processing}
                                >
                                    Skicka inbjudan
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
