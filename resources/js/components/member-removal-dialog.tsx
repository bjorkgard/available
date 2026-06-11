import { useState } from 'react';

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
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

export type MemberRemovalAction = 'transfer' | 'delete';

export type TargetMember = {
    id: string;
    name: string;
};

type Props = {
    open: boolean;
    memberName: string;
    targetMembers: TargetMember[];
    onConfirm: (action: MemberRemovalAction, transferTo?: string) => void;
    onCancel: () => void;
};

export default function MemberRemovalDialog({
    open,
    memberName,
    targetMembers,
    onConfirm,
    onCancel,
}: Props) {
    const [action, setAction] = useState<MemberRemovalAction>('transfer');
    const [transferTo, setTransferTo] = useState<string>('');

    const handleOpenChange = (nextOpen: boolean) => {
        if (!nextOpen) {
            onCancel();
        }
    };

    const handleConfirm = () => {
        if (action === 'transfer') {
            onConfirm('transfer', transferTo);
        } else {
            onConfirm('delete');
        }
    };

    const isConfirmDisabled = action === 'transfer' && !transferTo;

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Ta bort {memberName}</DialogTitle>
                    <DialogDescription>
                        Den här medlemmen har framtida bokningar. Välj vad som
                        ska hända med dem innan medlemmen tas bort.
                    </DialogDescription>
                </DialogHeader>

                <div className="grid gap-4 py-2">
                    <RadioGroup
                        value={action}
                        onValueChange={(value) =>
                            setAction(value as MemberRemovalAction)
                        }
                        className="grid gap-3"
                    >
                        <div className="flex items-center space-x-2">
                            <RadioGroupItem
                                value="transfer"
                                id="action-transfer"
                            />
                            <Label htmlFor="action-transfer">
                                Överför bokningar till en annan medlem
                            </Label>
                        </div>
                        <div className="flex items-center space-x-2">
                            <RadioGroupItem value="delete" id="action-delete" />
                            <Label htmlFor="action-delete">
                                Ta bort framtida bokningar
                            </Label>
                        </div>
                    </RadioGroup>

                    {action === 'transfer' && (
                        <div className="grid gap-2">
                            <Label htmlFor="transfer-target">
                                Överför till
                            </Label>
                            <Select
                                value={transferTo}
                                onValueChange={setTransferTo}
                            >
                                <SelectTrigger
                                    id="transfer-target"
                                    className="w-full"
                                >
                                    <SelectValue placeholder="Välj en medlem" />
                                </SelectTrigger>
                                <SelectContent>
                                    {targetMembers.map((member) => (
                                        <SelectItem
                                            key={member.id}
                                            value={member.id}
                                        >
                                            {member.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    )}
                </div>

                <DialogFooter className="gap-2">
                    <DialogClose asChild>
                        <Button variant="secondary">Avbryt</Button>
                    </DialogClose>

                    <Button
                        variant="destructive"
                        disabled={isConfirmDisabled}
                        onClick={handleConfirm}
                    >
                        Ta bort medlem
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
