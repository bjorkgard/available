import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { CongregationRole } from '@/types';

type RoleSelectProps = {
    value: CongregationRole;
    onChange: (value: CongregationRole) => void;
    viewerRole: CongregationRole;
    disabled?: boolean;
};

const roleLabels: Record<CongregationRole, string> = {
    superadmin: 'Superadmin',
    admin: 'Admin',
    member: 'Member',
};

function getAssignableRoles(viewerRole: CongregationRole): CongregationRole[] {
    switch (viewerRole) {
        case 'superadmin':
            return ['superadmin', 'admin', 'member'];
        case 'admin':
            return ['admin', 'member'];
        case 'member':
            return [];
    }
}

export default function RoleSelect({
    value,
    onChange,
    viewerRole,
    disabled,
}: RoleSelectProps) {
    const assignableRoles = getAssignableRoles(viewerRole);

    if (assignableRoles.length === 0) {
        return null;
    }

    return (
        <Select
            value={value}
            onValueChange={(v) => onChange(v as CongregationRole)}
            disabled={disabled}
        >
            <SelectTrigger className="w-full">
                <SelectValue placeholder="Select a role" />
            </SelectTrigger>
            <SelectContent>
                {assignableRoles.map((role) => (
                    <SelectItem key={role} value={role}>
                        {roleLabels[role]}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}
