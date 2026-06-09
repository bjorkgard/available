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
import type { KingdomHall } from '@/types';

type Props = {
    kingdomHall: KingdomHall;
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

export default function AddressEditModal({
    kingdomHall,
    open,
    onOpenChange,
}: Props) {
    const { currentCongregation } = usePage<{
        currentCongregation: { slug: string };
    }>().props;
    const congregationSlug = currentCongregation?.slug ?? '';

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <Form
                    key={String(open)}
                    action={`/${congregationSlug}/kingdom-hall`}
                    method="put"
                    className="space-y-6"
                    onSuccess={() => {
                        toast.success('Address updated successfully.');
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
                                <DialogTitle>Edit address</DialogTitle>
                                <DialogDescription>
                                    Update the Kingdom Hall address details.
                                </DialogDescription>
                            </DialogHeader>

                            <div className="grid gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="address-street_address">
                                        Street address
                                    </Label>
                                    <Input
                                        id="address-street_address"
                                        name="street_address"
                                        type="text"
                                        defaultValue={
                                            kingdomHall.street_address
                                        }
                                        maxLength={255}
                                        required
                                    />
                                    <InputError
                                        message={errors.street_address}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="address-zip_code">
                                        Zip code
                                    </Label>
                                    <Input
                                        id="address-zip_code"
                                        name="zip_code"
                                        type="text"
                                        defaultValue={kingdomHall.zip_code}
                                        maxLength={20}
                                        required
                                    />
                                    <InputError message={errors.zip_code} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="address-city">City</Label>
                                    <Input
                                        id="address-city"
                                        name="city"
                                        type="text"
                                        defaultValue={kingdomHall.city}
                                        maxLength={100}
                                        required
                                    />
                                    <InputError message={errors.city} />
                                </div>
                            </div>

                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button variant="secondary">Cancel</Button>
                                </DialogClose>

                                <Button
                                    type="submit"
                                    disabled={processing}
                                >
                                    Save changes
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
