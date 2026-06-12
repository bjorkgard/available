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

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

export default function AddCongregationModal({ open, onOpenChange }: Props) {
    const { currentCongregation } = usePage().props;
    const congregationSlug = currentCongregation!.slug;

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <Form
                    key={String(open)}
                    action={`/${congregationSlug}/kingdom-hall/congregations`}
                    method="post"
                    className="space-y-6"
                    onSuccess={() => {
                        toast.success('Församling tillagd.');
                        onOpenChange(false);
                    }}
                    onError={() => {
                        toast.error('Åtgärda felen nedan och försök igen.');
                    }}
                >
                    {({ errors, processing }) => (
                        <>
                            <DialogHeader>
                                <DialogTitle>Lägg till församling</DialogTitle>
                                <DialogDescription>
                                    Lägg till en ny församling i Rikets sal. En
                                    inbjudan skickas till den ansvariga
                                    personen.
                                </DialogDescription>
                            </DialogHeader>

                            <div className="grid gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="congregation-name">
                                        Församlingsnamn
                                    </Label>
                                    <Input
                                        id="congregation-name"
                                        name="name"
                                        type="text"
                                        placeholder="Församlingsnamn"
                                        maxLength={255}
                                        required
                                    />
                                    <InputError message={errors.name} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="congregation-number">
                                        Församlingsnummer
                                    </Label>
                                    <Input
                                        id="congregation-number"
                                        name="congregation_number"
                                        type="text"
                                        placeholder="ABC123"
                                        maxLength={20}
                                        required
                                    />
                                    <InputError
                                        message={errors.congregation_number}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="initial-user-name">
                                        Ansvarig persons namn
                                    </Label>
                                    <Input
                                        id="initial-user-name"
                                        name="initial_user_name"
                                        type="text"
                                        placeholder="Förnamn Efternamn"
                                        maxLength={255}
                                        required
                                    />
                                    <InputError
                                        message={errors.initial_user_name}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="initial-user-email">
                                        Ansvarig persons e-post
                                    </Label>
                                    <Input
                                        id="initial-user-email"
                                        name="initial_user_email"
                                        type="email"
                                        placeholder="person@example.com"
                                        maxLength={255}
                                        required
                                    />
                                    <InputError
                                        message={errors.initial_user_email}
                                    />
                                </div>
                            </div>

                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button variant="secondary">Avbryt</Button>
                                </DialogClose>

                                <Button type="submit" disabled={processing}>
                                    Lägg till församling
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
