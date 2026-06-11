import { usePage } from '@inertiajs/react';
import { format } from 'date-fns';
import { sv } from 'date-fns/locale';
import { CalendarIcon } from 'lucide-react';
import type { FormEvent } from 'react';
import { useMemo, useRef, useState } from 'react';
import { toast } from 'sonner';

import {
    store,
    update,
} from '@/actions/App/Http/Controllers/Congregations/BookingController';
import InputError from '@/components/input-error';
import RecurrenceEditPrompt from '@/components/recurrence-edit-prompt';
import type { RecurrenceEditScope } from '@/components/recurrence-edit-prompt';

import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { Checkbox } from '@/components/ui/checkbox';
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
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { cn } from '@/lib/utils';
import type { Congregation, Room } from '@/types';

type BookingResource = {
    id: string;
    name: string;
    starts_at: string;
    ends_at: string;
    congregation_id: string;
    rooms: { id: string; name: string }[];
    recurrence_pattern_id: string | null;
};

type Props = {
    rooms: Room[];
    congregations?: Congregation[];
    initialDate?: string;
    initialTime?: string;
    booking?: BookingResource;
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

type RecurrenceFrequency = 'daily' | 'weekly' | 'monthly' | 'yearly';
type RecurrenceEndType = 'date' | 'count';

function parseDateTime(isoString: string): { date: string; time: string } {
    const d = new Date(isoString);
    const year = d.getFullYear();
    const month = (d.getMonth() + 1).toString().padStart(2, '0');
    const day = d.getDate().toString().padStart(2, '0');
    const hours = d.getHours().toString().padStart(2, '0');
    const minutes = d.getMinutes().toString().padStart(2, '0');

    return {
        date: `${year}-${month}-${day}`,
        time: `${hours}:${minutes}`,
    };
}

function getDefaultDate(): string {
    const now = new Date();
    const year = now.getFullYear();
    const month = (now.getMonth() + 1).toString().padStart(2, '0');
    const day = now.getDate().toString().padStart(2, '0');

    return `${year}-${month}-${day}`;
}

function getDefaultTime(): string {
    const now = new Date();
    const hours = now.getHours();
    const minutes = Math.ceil(now.getMinutes() / 15) * 15;

    if (minutes === 60) {
        return `${(hours + 1).toString().padStart(2, '0')}:00`;
    }

    return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}`;
}

function addOneHour(time: string): string {
    const [h, m] = time.split(':').map(Number);
    const newH = (h + 1) % 24;

    return `${newH.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}`;
}

export default function BookingDialog({
    rooms,
    congregations,
    initialDate,
    initialTime,
    booking,
    open,
    onOpenChange,
}: Props) {
    const { currentCongregation } = usePage<{
        currentCongregation: { slug: string; id: string };
    }>().props;

    const isEditing = !!booking;

    const defaultStartDate = booking
        ? parseDateTime(booking.starts_at).date
        : (initialDate ?? getDefaultDate());
    const defaultStartTime = booking
        ? parseDateTime(booking.starts_at).time
        : (initialTime ?? getDefaultTime());
    const defaultEndTime = booking
        ? parseDateTime(booking.ends_at).time
        : addOneHour(initialTime ?? getDefaultTime());

    const [selectedDate, setSelectedDate] = useState<Date | undefined>(
        defaultStartDate ? new Date(defaultStartDate + 'T00:00:00') : undefined,
    );

    // Reset selectedDate when the dialog opens with new props
    const prevOpenRef = useRef(false);

    if (open && !prevOpenRef.current) {
        // Dialog just opened — sync the date from props
        const newDate = defaultStartDate
            ? new Date(defaultStartDate + 'T00:00:00')
            : undefined;

        if (newDate?.getTime() !== selectedDate?.getTime()) {
            setSelectedDate(newDate);
        }
    }

    prevOpenRef.current = open;

    const defaultRoomIds = booking ? booking.rooms.map((r) => r.id) : [];
    const defaultCongregationId = booking
        ? booking.congregation_id
        : currentCongregation.id;

    const [selectedRooms, setSelectedRooms] =
        useState<string[]>(defaultRoomIds);
    const [isRecurring, setIsRecurring] = useState(
        !!booking?.recurrence_pattern_id,
    );
    const [frequency, setFrequency] = useState<RecurrenceFrequency>('weekly');
    const [endType, setEndType] = useState<RecurrenceEndType>('date');
    const [congregationId, setCongregationId] = useState(defaultCongregationId);

    // Sync form state when dialog opens with a (different) booking
    const prevBookingIdRef = useRef<string | null>(null);

    if (open && !prevOpenRef.current) {
        prevBookingIdRef.current = booking?.id ?? null;
        setSelectedRooms(defaultRoomIds);
        setCongregationId(defaultCongregationId);
        setIsRecurring(!!booking?.recurrence_pattern_id);
    } else if (
        open &&
        (booking?.id ?? null) !== prevBookingIdRef.current
    ) {
        prevBookingIdRef.current = booking?.id ?? null;
        setSelectedRooms(defaultRoomIds);
        setCongregationId(defaultCongregationId);
        setIsRecurring(!!booking?.recurrence_pattern_id);
    }

    const showCongregationSelector = useMemo(() => {
        return congregations && congregations.length > 1;
    }, [congregations]);

    const congregationSlug = currentCongregation.slug;

    const action = isEditing
        ? update.url({
              current_congregation: congregationSlug,
              booking: booking.id,
          })
        : store.url(congregationSlug);
    const method = isEditing ? 'put' : 'post';

    const [errors, setErrors] = useState<Record<string, string>>({});
    const [processing, setProcessing] = useState(false);
    const [scopePromptOpen, setScopePromptOpen] = useState(false);
    const pendingBodyRef = useRef<Record<string, unknown> | null>(null);
    const formRef = useRef<HTMLFormElement>(null);

    async function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        setProcessing(true);
        setErrors({});

        const formData = new FormData(event.currentTarget);

        // Build the JSON body from form data
        const dateStr = selectedDate
            ? format(selectedDate, 'yyyy-MM-dd')
            : '';
        const body: Record<string, unknown> = {
            name: formData.get('name') as string,
            starts_at: `${dateStr} ${formData.get('start_time')}:00`,
            ends_at: `${dateStr} ${formData.get('end_time')}:00`,
            room_ids: selectedRooms,
            congregation_id: congregationId,
        };

        if (isRecurring) {
            const recurrence: Record<string, unknown> = {
                frequency,
            };

            if (endType === 'date') {
                recurrence.end_date = formData.get(
                    'recurrence_end_date',
                ) as string;
            } else {
                recurrence.end_count = Number(
                    formData.get('recurrence_end_count'),
                );
            }

            body.recurrence = recurrence;
        }

        if (isEditing) {
            // For recurring bookings, show the scope prompt
            if (booking?.recurrence_pattern_id) {
                pendingBodyRef.current = body;
                setScopePromptOpen(true);
                setProcessing(false);

                return;
            }

            body.scope = 'this_only';
        }

        await submitBooking(body);
    }

    async function submitBooking(body: Record<string, unknown>) {
        setProcessing(true);
        setErrors({});

        try {
            const csrfToken =
                document.cookie
                    .split('; ')
                    .find((row) => row.startsWith('XSRF-TOKEN='))
                    ?.split('=')[1] ?? '';

            const response = await fetch(action, {
                method: method === 'put' ? 'PUT' : 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': decodeURIComponent(csrfToken),
                },
                body: JSON.stringify(body),
            });

            if (response.ok) {
                toast.success(
                    isEditing ? 'Booking updated.' : 'Booking created.',
                );
                onOpenChange(false);
            } else if (response.status === 422) {
                const json = await response.json();
                const fieldErrors: Record<string, string> = {};

                if (json.errors) {
                    for (const [key, messages] of Object.entries(json.errors)) {
                        fieldErrors[key] = Array.isArray(messages)
                            ? messages[0]
                            : (messages as string);
                    }
                }

                setErrors(fieldErrors);
                toast.error('Please fix the errors below and try again.');
            } else if (response.status === 403) {
                toast.error(
                    'You do not have permission to perform this action.',
                );
            } else {
                toast.error('Something went wrong. Please try again.');
            }
        } catch {
            toast.error('Network error. Please check your connection.');
        } finally {
            setProcessing(false);
        }
    }

    function handleScopeSelect(scope: RecurrenceEditScope) {
        setScopePromptOpen(false);

        if (pendingBodyRef.current) {
            pendingBodyRef.current.scope = scope;
            submitBooking(pendingBodyRef.current);
            pendingBodyRef.current = null;
        }
    }

    function handleScopeCancel() {
        setScopePromptOpen(false);
        pendingBodyRef.current = null;
    }

    function handleOpenChange(nextOpen: boolean) {
        onOpenChange(nextOpen);

        if (!nextOpen) {
            setSelectedRooms(defaultRoomIds);
            setIsRecurring(!!booking?.recurrence_pattern_id);
            setFrequency('weekly');
            setEndType('date');
            setCongregationId(defaultCongregationId);
        }
    }

    function handleRoomToggle(roomId: string, checked: boolean) {
        setSelectedRooms((prev) =>
            checked ? [...prev, roomId] : prev.filter((id) => id !== roomId),
        );
    }

    return (
        <>
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                <form
                    ref={formRef}
                    className="space-y-6"
                    onSubmit={handleSubmit}
                >
                    <DialogHeader>
                        <DialogTitle>
                            {isEditing ? 'Edit booking' : 'Create booking'}
                        </DialogTitle>
                        <DialogDescription>
                            {isEditing
                                ? 'Update the booking details below.'
                                : 'Fill in the details to reserve a room.'}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4">
                        {/* Booking name */}
                        <div className="grid gap-2">
                            <Label htmlFor="booking-name">Name</Label>
                            <Input
                                id="booking-name"
                                name="name"
                                type="text"
                                defaultValue={booking?.name ?? ''}
                                placeholder="e.g. Service group meeting"
                                required
                                maxLength={255}
                            />
                            <InputError message={errors.name} />
                        </div>

                        {/* Date */}
                        <div className="grid gap-2">
                            <Label>Date</Label>
                            <Popover>
                                <PopoverTrigger asChild>
                                    <Button
                                        variant="outline"
                                        className={cn(
                                            'w-full justify-start text-left font-normal',
                                            !selectedDate &&
                                                'text-muted-foreground',
                                        )}
                                    >
                                        <CalendarIcon className="size-4" />
                                        {selectedDate
                                            ? format(selectedDate, 'PPP', {
                                                  locale: sv,
                                              })
                                            : 'Pick a date'}
                                    </Button>
                                </PopoverTrigger>
                                <PopoverContent
                                    className="w-auto p-0"
                                    align="start"
                                >
                                    <Calendar
                                        mode="single"
                                        selected={selectedDate}
                                        onSelect={setSelectedDate}
                                        locale={sv}
                                        autoFocus
                                    />
                                </PopoverContent>
                            </Popover>
                            <input
                                type="hidden"
                                name="start_date"
                                value={
                                    selectedDate
                                        ? format(selectedDate, 'yyyy-MM-dd')
                                        : ''
                                }
                            />
                            <InputError message={errors.starts_at} />
                        </div>

                        {/* Time range */}
                        <div className="grid grid-cols-2 gap-3">
                            <div className="grid gap-2">
                                <Label htmlFor="booking-start-time">
                                    From
                                </Label>
                                <Input
                                    id="booking-start-time"
                                    name="start_time"
                                    type="time"
                                    step={900}
                                    defaultValue={defaultStartTime}
                                    required
                                />
                                <InputError message={errors.start_time} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="booking-end-time">To</Label>
                                <Input
                                    id="booking-end-time"
                                    name="end_time"
                                    type="time"
                                    step={900}
                                    defaultValue={defaultEndTime}
                                    required
                                />
                                <InputError message={errors.end_time} />
                            </div>
                        </div>

                        {/* Room selection */}
                        <div className="grid gap-2">
                            <Label>Rooms</Label>
                            <div className="grid gap-2 rounded-md border p-3">
                                {rooms.length === 0 && (
                                    <p className="text-sm text-muted-foreground">
                                        No rooms available.
                                    </p>
                                )}
                                {rooms.map((room) => (
                                    <label
                                        key={room.id}
                                        className="flex items-center gap-2"
                                    >
                                        <Checkbox
                                            name="room_ids[]"
                                            value={room.id}
                                            checked={selectedRooms.includes(
                                                room.id,
                                            )}
                                            onCheckedChange={(checked) =>
                                                handleRoomToggle(
                                                    room.id,
                                                    checked === true,
                                                )
                                            }
                                        />
                                        <span className="text-sm">
                                            {room.name}
                                        </span>
                                    </label>
                                ))}
                            </div>
                            <InputError message={errors.room_ids} />
                            <InputError message={errors['room_ids.0']} />
                        </div>

                        {/* Recurrence toggle — only shown when creating, not editing */}
                        {!isEditing && (
                        <div className="grid gap-3">
                            <label className="flex items-center gap-2">
                                <Checkbox
                                    name="is_recurring"
                                    value="1"
                                    checked={isRecurring}
                                    onCheckedChange={(checked) =>
                                        setIsRecurring(checked === true)
                                    }
                                />
                                <span className="text-sm font-medium">
                                    Repeat this booking
                                </span>
                            </label>

                            {isRecurring && (
                                <div className="grid gap-3 rounded-md border p-3">
                                    {/* Frequency */}
                                    <div className="grid gap-2">
                                        <Label htmlFor="booking-frequency">
                                            Frequency
                                        </Label>
                                        <Select
                                            name="recurrence_frequency"
                                            value={frequency}
                                            onValueChange={(v) =>
                                                setFrequency(
                                                    v as RecurrenceFrequency,
                                                )
                                            }
                                        >
                                            <SelectTrigger
                                                id="booking-frequency"
                                                className="w-full"
                                            >
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="daily">
                                                    Daily
                                                </SelectItem>
                                                <SelectItem value="weekly">
                                                    Weekly
                                                </SelectItem>
                                                <SelectItem value="monthly">
                                                    Monthly
                                                </SelectItem>
                                                <SelectItem value="yearly">
                                                    Yearly
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <InputError
                                            message={
                                                errors.recurrence_frequency
                                            }
                                        />
                                    </div>

                                    {/* End condition */}
                                    <div className="grid gap-2">
                                        <Label htmlFor="booking-end-type">
                                            Ends
                                        </Label>
                                        <Select
                                            name="recurrence_end_type"
                                            value={endType}
                                            onValueChange={(v) =>
                                                setEndType(
                                                    v as RecurrenceEndType,
                                                )
                                            }
                                        >
                                            <SelectTrigger
                                                id="booking-end-type"
                                                className="w-full"
                                            >
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="date">
                                                    On a date
                                                </SelectItem>
                                                <SelectItem value="count">
                                                    After N occurrences
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    {endType === 'date' && (
                                        <div className="grid gap-2">
                                            <Label htmlFor="booking-recurrence-end-date">
                                                End date
                                            </Label>
                                            <Input
                                                id="booking-recurrence-end-date"
                                                name="recurrence_end_date"
                                                type="date"
                                                required
                                            />
                                            <InputError
                                                message={
                                                    errors.recurrence_end_date
                                                }
                                            />
                                        </div>
                                    )}

                                    {endType === 'count' && (
                                        <div className="grid gap-2">
                                            <Label htmlFor="booking-recurrence-end-count">
                                                Number of occurrences
                                            </Label>
                                            <Input
                                                id="booking-recurrence-end-count"
                                                name="recurrence_end_count"
                                                type="number"
                                                min={1}
                                                max={365}
                                                placeholder="e.g. 10"
                                                required
                                            />
                                            <InputError
                                                message={
                                                    errors.recurrence_end_count
                                                }
                                            />
                                        </div>
                                    )}
                                </div>
                            )}
                        </div>
                        )}

                        {/* Congregation selector (superadmin only) */}
                        {showCongregationSelector && (
                            <div className="grid gap-2">
                                <Label htmlFor="booking-congregation">
                                    Congregation
                                </Label>
                                <Select
                                    name="congregation_id"
                                    value={congregationId}
                                    onValueChange={setCongregationId}
                                >
                                    <SelectTrigger
                                        id="booking-congregation"
                                        className="w-full"
                                    >
                                        <SelectValue placeholder="Select congregation" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {congregations!.map((cong) => (
                                            <SelectItem
                                                key={cong.id}
                                                value={cong.id}
                                            >
                                                {cong.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.congregation_id} />
                            </div>
                        )}

                        {/* Hidden congregation_id when selector is not shown */}
                        {!showCongregationSelector && (
                            <input
                                type="hidden"
                                name="congregation_id"
                                value={congregationId}
                            />
                        )}
                    </div>

                    <DialogFooter className="gap-2">
                        <DialogClose asChild>
                            <Button variant="secondary">Cancel</Button>
                        </DialogClose>

                        <Button type="submit" disabled={processing}>
                            {isEditing ? 'Save changes' : 'Create booking'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <RecurrenceEditPrompt
            open={scopePromptOpen}
            onSelect={handleScopeSelect}
            onCancel={handleScopeCancel}
        />
        </>
    );
}
