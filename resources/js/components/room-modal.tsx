import { Form, usePage } from '@inertiajs/react';
import { toast } from 'sonner';

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
import type { KingdomHall, Room } from '@/types';

type Props = {
    kingdomHall: KingdomHall;
    room?: Room;
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

export default function RoomModal({
    kingdomHall,
    room,
    open,
    onOpenChange,
}: Props) {
    const { currentCongregation } = usePage<{
        currentCongregation: { slug: string };
    }>().props;
    const congregationSlug = currentCongregation.slug;

    const isEditing = !!room;
    const action = isEditing
        ? `/${congregationSlug}/kingdom-hall/rooms/${room.id}`
        : `/${congregationSlug}/kingdom-hall/rooms`;
    const method = isEditing ? 'put' : 'post';

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <Form
                    key={String(open) + (room?.id ?? 'new')}
                    action={action}
                    method={method}
                    className="space-y-6"
                    onSuccess={() => {
                        toast.success(
                            isEditing
                                ? 'Room updated.'
                                : 'Room created.',
                        );
                        onOpenChange(false);
                    }}
                    onError={() => {
                        toast.error(
                            'Something went wrong. Please try again.',
                        );
                    }}
                >
                    {({ errors, processing }) => (
                        <>
                            <DialogHeader>
                                <DialogTitle>
                                    {isEditing
                                        ? 'Edit room'
                                        : 'Add room'}
                                </DialogTitle>
                                <DialogDescription>
                                    {isEditing
                                        ? `Rename the room in ${kingdomHall.street_address}.`
                                        : `Add a new room to ${kingdomHall.street_address}.`}
                                </DialogDescription>
                            </DialogHeader>

                            <div className="grid gap-2">
                                <Label htmlFor="room-name">Name</Label>
                                <Input
                                    id="room-name"
                                    name="name"
                                    type="text"
                                    defaultValue={room?.name ?? ''}
                                    placeholder="e.g. Main hall"
                                    required
                                    maxLength={255}
                                />
                                <InputError message={errors.name} />
                            </div>

                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button variant="secondary">
                                        Cancel
                                    </Button>
                                </DialogClose>

                                <Button
                                    type="submit"
                                    disabled={processing}
                                >
                                    {isEditing ? 'Save' : 'Add room'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
