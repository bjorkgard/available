import { Form, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';

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

const LOCALE_LABELS: Record<string, string> = {
    sv: 'Svenska',
    en: 'English',
};

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
    const { t } = useTranslation();
    const { currentCongregation, supportedLocales } = usePage().props;

    const congregationLocale = currentCongregation?.locale ?? 'sv';

    const [selectedRole, setSelectedRole] =
        useState<CongregationRole>('member');
    const [selectedLocale, setSelectedLocale] = useState(congregationLocale);

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
            setSelectedLocale(congregationLocale);
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
                                <DialogTitle>
                                    {t('Bjud in en medlem')}
                                </DialogTitle>
                                <DialogDescription>
                                    {t(
                                        'Skicka en inbjudan att gå med i denna församling.',
                                    )}
                                </DialogDescription>
                            </DialogHeader>

                            <div className="grid gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="invite-name">
                                        {t('Namn')}
                                    </Label>
                                    <Input
                                        id="invite-name"
                                        name="name"
                                        type="text"
                                        data-test="invite-name"
                                        placeholder={t('Förnamn Efternamn')}
                                        required
                                    />
                                    <InputError message={errors.name} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="invite-email">
                                        {t('E-postadress')}
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
                                    <Label htmlFor="invite-role">
                                        {t('Roll')}
                                    </Label>
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
                                            <SelectValue
                                                placeholder={t('Välj en roll')}
                                            />
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

                                <div className="grid gap-2">
                                    <Label htmlFor="invite-locale">
                                        {t('Språk för inbjudan')}
                                    </Label>
                                    <Select
                                        name="locale"
                                        value={selectedLocale}
                                        onValueChange={setSelectedLocale}
                                    >
                                        <SelectTrigger
                                            id="invite-locale"
                                            data-test="invite-locale"
                                            className="w-full"
                                        >
                                            <SelectValue
                                                placeholder={t('Välj språk')}
                                            />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {supportedLocales.map((loc) => (
                                                <SelectItem
                                                    key={loc}
                                                    value={loc}
                                                >
                                                    {LOCALE_LABELS[loc] ?? loc}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.locale} />
                                </div>
                            </div>

                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button variant="secondary">
                                        {t('Avbryt')}
                                    </Button>
                                </DialogClose>

                                <Button
                                    type="submit"
                                    data-test="invite-submit"
                                    disabled={processing}
                                >
                                    {t('Skicka inbjudan')}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
