import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

export default function SetupWizard() {
    return (
        <>
            <Head title="Set up your Kingdom Hall" />
            <Form
                action="/setup"
                method="post"
                disableWhileProcessing
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <div className="grid gap-6">
                        <div className="grid gap-2">
                            <Label htmlFor="street_address">
                                Street address
                            </Label>
                            <Input
                                id="street_address"
                                type="text"
                                required
                                autoFocus
                                tabIndex={1}
                                autoComplete="street-address"
                                name="street_address"
                                placeholder="123 Main Street"
                            />
                            <InputError message={errors.street_address} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="zip_code">Zip code</Label>
                            <Input
                                id="zip_code"
                                type="text"
                                required
                                tabIndex={2}
                                autoComplete="postal-code"
                                name="zip_code"
                                placeholder="12345"
                            />
                            <InputError message={errors.zip_code} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="city">City</Label>
                            <Input
                                id="city"
                                type="text"
                                required
                                tabIndex={3}
                                autoComplete="address-level2"
                                name="city"
                                placeholder="City"
                            />
                            <InputError message={errors.city} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="number_of_rooms">
                                Number of rooms
                            </Label>
                            <Input
                                id="number_of_rooms"
                                type="number"
                                required
                                tabIndex={4}
                                name="number_of_rooms"
                                min={1}
                                max={50}
                                placeholder="1"
                            />
                            <InputError message={errors.number_of_rooms} />
                        </div>

                        <Button
                            type="submit"
                            className="mt-2 w-full"
                            tabIndex={5}
                        >
                            {processing && <Spinner />}
                            Complete setup
                        </Button>
                    </div>
                )}
            </Form>
        </>
    );
}

SetupWizard.layout = {
    title: 'Set up your Kingdom Hall',
    description:
        'Configure the physical location where your congregation meets.',
};
