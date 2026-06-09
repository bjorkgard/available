import { Form, Head, usePage } from '@inertiajs/react';
import { Building2, Church, DoorOpen } from 'lucide-react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Congregation, KingdomHall, Room } from '@/types';

type Props = {
    kingdomHall: KingdomHall & {
        rooms: Room[];
        congregations: Congregation[];
    };
    canManage: boolean;
};

export default function KingdomHallShow({ kingdomHall, canManage }: Props) {
    const page = usePage();
    const currentCongregation = page.props.currentCongregation as
        | { slug: string }
        | null
        | undefined;
    const congregationSlug = currentCongregation?.slug ?? '';

    return (
        <>
            <Head title="Kingdom Hall" />

            <div className="mx-auto flex w-full max-w-4xl flex-col space-y-8 px-4 py-6">
                <Heading
                    title="Kingdom Hall"
                    description={`${kingdomHall.street_address}, ${kingdomHall.zip_code} ${kingdomHall.city}`}
                />

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Building2 className="h-5 w-5" />
                            Address
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <dl className="grid gap-3 sm:grid-cols-3">
                            <div>
                                <dt className="text-sm font-medium text-muted-foreground">
                                    Street address
                                </dt>
                                <dd className="mt-1">
                                    {kingdomHall.street_address}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-muted-foreground">
                                    Zip code
                                </dt>
                                <dd className="mt-1">{kingdomHall.zip_code}</dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-muted-foreground">
                                    City
                                </dt>
                                <dd className="mt-1">{kingdomHall.city}</dd>
                            </div>
                        </dl>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <DoorOpen className="h-5 w-5" />
                            Rooms
                        </CardTitle>
                        <CardDescription>
                            {kingdomHall.rooms.length}{' '}
                            {kingdomHall.rooms.length === 1 ? 'room' : 'rooms'}{' '}
                            available
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <ul className="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                            {kingdomHall.rooms.map((room) => (
                                <li
                                    key={room.id}
                                    className="rounded-lg border px-4 py-2 text-sm"
                                >
                                    {room.name}
                                </li>
                            ))}
                        </ul>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Church className="h-5 w-5" />
                            Congregations
                        </CardTitle>
                        <CardDescription>
                            {kingdomHall.congregations.length}{' '}
                            {kingdomHall.congregations.length === 1
                                ? 'congregation'
                                : 'congregations'}{' '}
                            in this Kingdom Hall
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <ul className="space-y-2">
                            {kingdomHall.congregations.map((congregation) => (
                                <li
                                    key={congregation.id}
                                    className="flex items-center justify-between rounded-lg border px-4 py-3"
                                >
                                    <div>
                                        <div className="font-medium">
                                            {congregation.name}
                                        </div>
                                        <div className="text-sm text-muted-foreground">
                                            #{congregation.congregation_number}
                                        </div>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    </CardContent>
                </Card>

                {canManage && (
                    <>
                        <Card>
                            <CardHeader>
                                <CardTitle>Edit Kingdom Hall</CardTitle>
                                <CardDescription>
                                    Update the Kingdom Hall details
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Form
                                    action={`/${congregationSlug}/kingdom-hall`}
                                    method="put"
                                    className="space-y-4"
                                >
                                    {({ errors, processing }) => (
                                        <>
                                            <div className="grid gap-4 sm:grid-cols-2">
                                                <div className="grid gap-2 sm:col-span-2">
                                                    <Label htmlFor="street_address">
                                                        Street address
                                                    </Label>
                                                    <Input
                                                        id="street_address"
                                                        name="street_address"
                                                        defaultValue={
                                                            kingdomHall.street_address
                                                        }
                                                        required
                                                    />
                                                    <InputError
                                                        message={
                                                            errors.street_address
                                                        }
                                                    />
                                                </div>

                                                <div className="grid gap-2">
                                                    <Label htmlFor="zip_code">
                                                        Zip code
                                                    </Label>
                                                    <Input
                                                        id="zip_code"
                                                        name="zip_code"
                                                        defaultValue={
                                                            kingdomHall.zip_code
                                                        }
                                                        required
                                                    />
                                                    <InputError
                                                        message={
                                                            errors.zip_code
                                                        }
                                                    />
                                                </div>

                                                <div className="grid gap-2">
                                                    <Label htmlFor="city">
                                                        City
                                                    </Label>
                                                    <Input
                                                        id="city"
                                                        name="city"
                                                        defaultValue={
                                                            kingdomHall.city
                                                        }
                                                        required
                                                    />
                                                    <InputError
                                                        message={errors.city}
                                                    />
                                                </div>

                                                <div className="grid gap-2">
                                                    <Label htmlFor="number_of_rooms">
                                                        Number of rooms
                                                    </Label>
                                                    <Input
                                                        id="number_of_rooms"
                                                        name="number_of_rooms"
                                                        type="number"
                                                        min={1}
                                                        max={50}
                                                        defaultValue={
                                                            kingdomHall.number_of_rooms
                                                        }
                                                        required
                                                    />
                                                    <InputError
                                                        message={
                                                            errors.number_of_rooms
                                                        }
                                                    />
                                                </div>
                                            </div>

                                            <Button
                                                type="submit"
                                                disabled={processing}
                                            >
                                                Save changes
                                            </Button>
                                        </>
                                    )}
                                </Form>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Add Congregation</CardTitle>
                                <CardDescription>
                                    Add a new congregation to this Kingdom Hall
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Form
                                    action={`/${congregationSlug}/kingdom-hall/congregations`}
                                    method="post"
                                    className="space-y-4"
                                >
                                    {({ errors, processing }) => (
                                        <>
                                            <div className="grid gap-4 sm:grid-cols-2">
                                                <div className="grid gap-2">
                                                    <Label htmlFor="add_name">
                                                        Congregation name
                                                    </Label>
                                                    <Input
                                                        id="add_name"
                                                        name="name"
                                                        placeholder="Congregation name"
                                                        required
                                                    />
                                                    <InputError
                                                        message={errors.name}
                                                    />
                                                </div>

                                                <div className="grid gap-2">
                                                    <Label htmlFor="add_congregation_number">
                                                        Congregation number
                                                    </Label>
                                                    <Input
                                                        id="add_congregation_number"
                                                        name="congregation_number"
                                                        placeholder="e.g. ABC123"
                                                        required
                                                    />
                                                    <InputError
                                                        message={
                                                            errors.congregation_number
                                                        }
                                                    />
                                                </div>

                                                <div className="grid gap-2">
                                                    <Label htmlFor="add_initial_user_name">
                                                        Initial user name
                                                    </Label>
                                                    <Input
                                                        id="add_initial_user_name"
                                                        name="initial_user_name"
                                                        placeholder="Full name"
                                                        required
                                                    />
                                                    <InputError
                                                        message={
                                                            errors.initial_user_name
                                                        }
                                                    />
                                                </div>

                                                <div className="grid gap-2">
                                                    <Label htmlFor="add_initial_user_email">
                                                        Initial user email
                                                    </Label>
                                                    <Input
                                                        id="add_initial_user_email"
                                                        name="initial_user_email"
                                                        type="email"
                                                        placeholder="email@example.com"
                                                        required
                                                    />
                                                    <InputError
                                                        message={
                                                            errors.initial_user_email
                                                        }
                                                    />
                                                </div>
                                            </div>

                                            <Button
                                                type="submit"
                                                disabled={processing}
                                            >
                                                Add congregation
                                            </Button>
                                        </>
                                    )}
                                </Form>
                            </CardContent>
                        </Card>
                    </>
                )}
            </div>
        </>
    );
}

KingdomHallShow.layout = () => ({
    breadcrumbs: [
        {
            title: 'Kingdom Hall',
            href: '',
        },
    ],
});
