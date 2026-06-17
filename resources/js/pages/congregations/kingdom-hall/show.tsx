import { Head, usePage } from '@inertiajs/react';
import {
    Building2,
    Church,
    DoorOpen,
    PencilIcon,
    PlusIcon,
    Trash2Icon,
} from 'lucide-react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';

import AddCongregationModal from '@/components/add-congregation-modal';
import AddressEditModal from '@/components/address-edit-modal';
import DeleteConfirmationDialog from '@/components/delete-confirmation-dialog';
import Heading from '@/components/heading';
import RoomModal from '@/components/room-modal';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import type { Congregation, KingdomHall, Room } from '@/types';

type RoomWithBookings = Room & { has_future_bookings: boolean };

type Props = {
    kingdomHall: KingdomHall & {
        rooms: RoomWithBookings[];
        congregations: Congregation[];
    };
    canManage: boolean;
};

export default function KingdomHallShow({ kingdomHall, canManage }: Props) {
    const { t } = useTranslation();
    const page = usePage();
    const currentCongregation = page.props.currentCongregation as
        | { slug: string }
        | null
        | undefined;
    const congregationSlug = currentCongregation?.slug ?? '';

    const [addressModalOpen, setAddressModalOpen] = useState(false);
    const [roomModalOpen, setRoomModalOpen] = useState(false);
    const [editingRoom, setEditingRoom] = useState<
        RoomWithBookings | undefined
    >();
    const [deletingRoom, setDeletingRoom] = useState<
        RoomWithBookings | undefined
    >();
    const [congregationModalOpen, setCongregationModalOpen] = useState(false);
    const [deletingCongregation, setDeletingCongregation] = useState<
        Congregation | undefined
    >();

    return (
        <TooltipProvider>
            <Head title={t('Rikets sal')} />

            <div className="mx-auto flex w-full max-w-4xl flex-col space-y-8 px-4 py-6">
                <Heading
                    title={t('Rikets sal')}
                    description={`${kingdomHall.street_address}, ${kingdomHall.zip_code} ${kingdomHall.city}`}
                />

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between">
                        <CardTitle className="flex items-center gap-2">
                            <Building2 className="h-5 w-5" />
                            {t('Adress')}
                        </CardTitle>
                        {canManage && (
                            <Tooltip>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        onClick={() =>
                                            setAddressModalOpen(true)
                                        }
                                    >
                                        <PencilIcon className="size-4" />
                                        <span className="sr-only">
                                            {t('Redigera adress')}
                                        </span>
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    {t('Redigera adress')}
                                </TooltipContent>
                            </Tooltip>
                        )}
                    </CardHeader>
                    <CardContent>
                        <dl className="grid gap-3 sm:grid-cols-3">
                            <div>
                                <dt className="text-sm font-medium text-muted-foreground">
                                    {t('Gatuadress')}
                                </dt>
                                <dd className="mt-1">
                                    {kingdomHall.street_address}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-muted-foreground">
                                    {t('Postnummer')}
                                </dt>
                                <dd className="mt-1">{kingdomHall.zip_code}</dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-muted-foreground">
                                    {t('Ort')}
                                </dt>
                                <dd className="mt-1">{kingdomHall.city}</dd>
                            </div>
                        </dl>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between">
                        <div>
                            <CardTitle className="flex items-center gap-2">
                                <DoorOpen className="h-5 w-5" />
                                {t('Rum')}
                            </CardTitle>
                            <CardDescription>
                                {t('{{count}} rum tillgängliga', {
                                    count: kingdomHall.rooms.length,
                                })}
                            </CardDescription>
                        </div>
                        {canManage && (
                            <Tooltip>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        onClick={() => {
                                            setEditingRoom(undefined);
                                            setRoomModalOpen(true);
                                        }}
                                    >
                                        <PlusIcon className="size-4" />
                                        <span className="sr-only">
                                            {t('Lägg till rum')}
                                        </span>
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    {t('Lägg till rum')}
                                </TooltipContent>
                            </Tooltip>
                        )}
                    </CardHeader>
                    <CardContent>
                        <ul className="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                            {(kingdomHall.rooms as RoomWithBookings[]).map(
                                (room) => (
                                    <li
                                        key={room.id}
                                        className="flex items-center justify-between rounded-lg border px-4 py-2 text-sm"
                                    >
                                        <span>{room.name}</span>
                                        {canManage && (
                                            <div className="flex items-center gap-1">
                                                <Tooltip>
                                                    <TooltipTrigger asChild>
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            className="size-7"
                                                            onClick={() => {
                                                                setEditingRoom(
                                                                    room,
                                                                );
                                                                setRoomModalOpen(
                                                                    true,
                                                                );
                                                            }}
                                                        >
                                                            <PencilIcon className="size-3.5" />
                                                            <span className="sr-only">
                                                                {t('Redigera')}{' '}
                                                                {room.name}
                                                            </span>
                                                        </Button>
                                                    </TooltipTrigger>
                                                    <TooltipContent>
                                                        {t('Redigera')}
                                                    </TooltipContent>
                                                </Tooltip>
                                                <Tooltip>
                                                    <TooltipTrigger asChild>
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            className="size-7"
                                                            onClick={() =>
                                                                setDeletingRoom(
                                                                    room,
                                                                )
                                                            }
                                                        >
                                                            <Trash2Icon className="size-3.5" />
                                                            <span className="sr-only">
                                                                {t('Ta bort')}{' '}
                                                                {room.name}
                                                            </span>
                                                        </Button>
                                                    </TooltipTrigger>
                                                    <TooltipContent>
                                                        {t('Ta bort')}
                                                    </TooltipContent>
                                                </Tooltip>
                                            </div>
                                        )}
                                    </li>
                                ),
                            )}
                        </ul>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between">
                        <div>
                            <CardTitle className="flex items-center gap-2">
                                <Church className="h-5 w-5" />
                                {t('Församlingar')}
                            </CardTitle>
                            <CardDescription>
                                {t(
                                    '{{count}} församlingar i denna rikets sal',
                                    { count: kingdomHall.congregations.length },
                                )}
                            </CardDescription>
                        </div>
                        {canManage && (
                            <Tooltip>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        onClick={() =>
                                            setCongregationModalOpen(true)
                                        }
                                    >
                                        <PlusIcon className="size-4" />
                                        <span className="sr-only">
                                            {t('Lägg till församling')}
                                        </span>
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    {t('Lägg till församling')}
                                </TooltipContent>
                            </Tooltip>
                        )}
                    </CardHeader>
                    <CardContent>
                        <ul className="space-y-2">
                            {kingdomHall.congregations.map((congregation) => (
                                <li
                                    key={congregation.id}
                                    className="flex items-center justify-between rounded-lg border px-4 py-3"
                                >
                                    <div className="flex items-center gap-3">
                                        <span
                                            className="size-3 shrink-0 rounded-full"
                                            style={{
                                                backgroundColor:
                                                    congregation.color ??
                                                    '#94A3B8',
                                            }}
                                        />
                                        <div>
                                            <div className="font-medium">
                                                {congregation.name}
                                            </div>
                                            <div className="text-sm text-muted-foreground">
                                                #
                                                {
                                                    congregation.congregation_number
                                                }
                                            </div>
                                        </div>
                                    </div>
                                    {canManage && (
                                        <Tooltip>
                                            <TooltipTrigger asChild>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="size-7"
                                                    onClick={() =>
                                                        setDeletingCongregation(
                                                            congregation,
                                                        )
                                                    }
                                                >
                                                    <Trash2Icon className="size-3.5" />
                                                    <span className="sr-only">
                                                        {t('Ta bort')}{' '}
                                                        {congregation.name}
                                                    </span>
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent>
                                                {t('Ta bort')}
                                            </TooltipContent>
                                        </Tooltip>
                                    )}
                                </li>
                            ))}
                        </ul>
                    </CardContent>
                </Card>
            </div>

            {canManage && (
                <>
                    <AddressEditModal
                        kingdomHall={kingdomHall}
                        open={addressModalOpen}
                        onOpenChange={setAddressModalOpen}
                    />
                    <RoomModal
                        kingdomHall={kingdomHall}
                        room={editingRoom}
                        open={roomModalOpen}
                        onOpenChange={setRoomModalOpen}
                    />
                    <AddCongregationModal
                        open={congregationModalOpen}
                        onOpenChange={setCongregationModalOpen}
                    />
                    <DeleteConfirmationDialog
                        open={!!deletingRoom}
                        onOpenChange={(open) => {
                            if (!open) {
                                setDeletingRoom(undefined);
                            }
                        }}
                        title={t('Ta bort rum')}
                        description={t(
                            'Är du säker på att du vill ta bort "{{name}}"? Denna åtgärd kan inte ångras.',
                            { name: deletingRoom?.name },
                        )}
                        action={`/${congregationSlug}/kingdom-hall/rooms/${deletingRoom?.id}`}
                        warning={
                            deletingRoom?.has_future_bookings
                                ? t(
                                      'Detta rum har framtida bokningar som kommer att tas bort.',
                                  )
                                : undefined
                        }
                    />
                    <DeleteConfirmationDialog
                        open={!!deletingCongregation}
                        onOpenChange={(open) => {
                            if (!open) {
                                setDeletingCongregation(undefined);
                            }
                        }}
                        title={t('Ta bort församling')}
                        description={t(
                            'Detta kommer permanent ta bort "{{name}}" och ta bort alla dess medlemmar och väntande inbjudningar.',
                            { name: deletingCongregation?.name },
                        )}
                        action={`/${congregationSlug}/kingdom-hall/congregations/${deletingCongregation?.slug}`}
                        confirmationInput={
                            deletingCongregation
                                ? {
                                      label: t(
                                          'Skriv "{{value}}" för att bekräfta',
                                          {
                                              value: deletingCongregation.congregation_number,
                                          },
                                      ),
                                      expectedValue:
                                          deletingCongregation.congregation_number,
                                  }
                                : undefined
                        }
                    />
                </>
            )}
        </TooltipProvider>
    );
}

KingdomHallShow.layout = () => ({
    breadcrumbs: [
        {
            title: 'Rikets sal',
            href: '',
        },
    ],
});
