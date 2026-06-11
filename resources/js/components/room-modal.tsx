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
                            isEditing ? 'Rum uppdaterat.' : 'Rum skapat.',
                        );
                        onOpenChange(false);
                    }}
                    onError={() => {
                        toast.error('Något gick fel. Försök igen.');
                    }}
                >
                    {({ errors, processing }) => (
                        <>
                            <DialogHeader>
                                <DialogTitle>
                                    {isEditing
                                        ? 'Redigera rum'
                                        : 'Lägg till rum'}
                                </DialogTitle>
                                <DialogDescription>
                                    {isEditing
                                        ? `Byt namn på rummet i ${kingdomHall.street_address}.`
                                        : `Lägg till ett nytt rum i ${kingdomHall.street_address}.`}
                                </DialogDescription>
                            </DialogHeader>

                            <div className="grid gap-2">
                                <Label htmlFor="room-name">Namn</Label>
                                <Input
                                    id="room-name"
                                    name="name"
                                    type="text"
                                    defaultValue={room?.name ?? ''}
                                    placeholder="t.ex. Stora salen"
                                    required
                                    maxLength={255}
                                />
                                <InputError message={errors.name} />
                            </div>

                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button variant="secondary">Avbryt</Button>
                                </DialogClose>

                                <Button type="submit" disabled={processing}>
                                    {isEditing ? 'Spara' : 'Lägg till rum'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
