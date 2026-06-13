import { Form, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
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
    const { t } = useTranslation();
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
                            isEditing ? t('Rum uppdaterat.') : t('Rum skapat.'),
                        );
                        onOpenChange(false);
                    }}
                    onError={() => {
                        toast.error(t('Något gick fel. Försök igen.'));
                    }}
                >
                    {({ errors, processing }) => (
                        <>
                            <DialogHeader>
                                <DialogTitle>
                                    {isEditing
                                        ? t('Redigera rum')
                                        : t('Lägg till rum')}
                                </DialogTitle>
                                <DialogDescription>
                                    {isEditing
                                        ? t(
                                              'Byt namn på rummet i {{address}}.',
                                              {
                                                  address:
                                                      kingdomHall.street_address,
                                              },
                                          )
                                        : t(
                                              'Lägg till ett nytt rum i {{address}}.',
                                              {
                                                  address:
                                                      kingdomHall.street_address,
                                              },
                                          )}
                                </DialogDescription>
                            </DialogHeader>

                            <div className="grid gap-2">
                                <Label htmlFor="room-name">{t('Namn')}</Label>
                                <Input
                                    id="room-name"
                                    name="name"
                                    type="text"
                                    defaultValue={room?.name ?? ''}
                                    placeholder={t('t.ex. Stora salen')}
                                    required
                                    maxLength={255}
                                />
                                <InputError message={errors.name} />
                            </div>

                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button variant="secondary">
                                        {t('Avbryt')}
                                    </Button>
                                </DialogClose>

                                <Button type="submit" disabled={processing}>
                                    {isEditing
                                        ? t('Spara')
                                        : t('Lägg till rum')}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
