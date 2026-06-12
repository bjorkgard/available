<?php

namespace App\Http\Controllers\Congregations;

use App\Actions\Bookings\CreateBooking;
use App\Actions\Bookings\DeleteBooking;
use App\Actions\Bookings\RescheduleBooking;
use App\Actions\Bookings\UpdateBooking;
use App\Enums\DeleteScope;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Congregation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    use AuthorizesRequests;

    /**
     * Return bookings for the Kingdom Hall within the given date range.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
        ]);

        $congregation = $request->user()->currentCongregation;
        $kingdomHallId = $congregation->kingdom_hall_id;

        $from = Carbon::parse($request->query('from'))->startOfDay();
        $to = Carbon::parse($request->query('to'))->endOfDay();

        $bookings = Booking::query()
            ->whereHas('congregation', function ($query) use ($kingdomHallId) {
                $query->where('kingdom_hall_id', $kingdomHallId);
            })
            ->where('starts_at', '<', $to)
            ->where('ends_at', '>', $from)
            ->with(['congregation', 'user', 'rooms', 'recurrencePattern'])
            ->orderBy('starts_at')
            ->get();

        $user = $request->user();

        $data = $bookings->map(function (Booking $booking) use ($user) {
            return $this->formatBooking($booking, $user);
        });

        return response()->json(['data' => $data]);
    }

    /**
     * Create a new booking.
     */
    public function store(Request $request, string $currentCongregation, CreateBooking $createBooking): JsonResponse
    {
        $user = $request->user();
        $congregation = $user->currentCongregation;

        // Superadmins can book for other congregations in the same Kingdom Hall
        if ($request->has('congregation_id') && $request->input('congregation_id') !== $congregation->id) {
            $targetCongregation = Congregation::find($request->input('congregation_id'));

            if ($targetCongregation && $targetCongregation->kingdom_hall_id === $congregation->kingdom_hall_id) {
                $congregation = $targetCongregation;
            }
        }

        $this->authorize('create', [Booking::class, $congregation]);

        // Merge separate date/time fields into starts_at/ends_at if needed
        $data = $this->mergeBookingData($request);

        $bookings = $createBooking->handle(
            $request->user(),
            $congregation,
            $data,
        );

        $user = $request->user();
        $bookings->each(fn (Booking $b) => $b->load(['congregation', 'user', 'rooms', 'recurrencePattern']));

        $data = $bookings->map(function (Booking $booking) use ($user) {
            return $this->formatBooking($booking, $user);
        });

        return response()->json(['data' => $data], 201);
    }

    /**
     * Return a single booking with details.
     */
    public function show(Request $request, string $currentCongregation, Booking $booking): JsonResponse
    {
        $this->authorize('view', $booking);

        $booking->load(['congregation', 'user', 'rooms', 'recurrencePattern']);

        $data = $this->formatBooking($booking, $request->user());

        return response()->json(['data' => $data]);
    }

    /**
     * Update a booking.
     */
    public function update(Request $request, string $currentCongregation, Booking $booking, UpdateBooking $updateBooking): JsonResponse
    {
        $this->authorize('update', $booking);

        $bookings = $updateBooking->handle(
            $request->user(),
            $booking,
            $request->only(['name', 'starts_at', 'ends_at', 'room_ids', 'scope']),
        );

        $user = $request->user();
        $bookings->each(fn (Booking $b) => $b->load(['congregation', 'user', 'rooms', 'recurrencePattern']));

        $data = $bookings->map(function (Booking $booking) use ($user) {
            return $this->formatBooking($booking, $user);
        });

        return response()->json(['data' => $data]);
    }

    /**
     * Reschedule a booking (drag-and-drop).
     */
    public function reschedule(Request $request, string $currentCongregation, Booking $booking, RescheduleBooking $rescheduleBooking): JsonResponse
    {
        $this->authorize('update', $booking);

        $request->validate([
            'starts_at' => ['required', 'date'],
            'scope' => ['required', 'string', Rule::in(['this_only', 'this_and_future'])],
        ]);

        $newStartsAt = Carbon::parse($request->input('starts_at'));

        $bookings = $rescheduleBooking->handle(
            $request->user(),
            $booking,
            $newStartsAt,
            $request->input('scope'),
        );

        $user = $request->user();
        $bookings->each(fn (Booking $b) => $b->load(['congregation', 'user', 'rooms', 'recurrencePattern']));

        $data = $bookings->map(function (Booking $booking) use ($user) {
            return $this->formatBooking($booking, $user);
        });

        return response()->json(['data' => $data]);
    }

    /**
     * Delete a booking.
     */
    public function destroy(Request $request, string $currentCongregation, Booking $booking, DeleteBooking $deleteBooking): JsonResponse
    {
        $this->authorize('delete', $booking);

        $request->validate([
            'scope' => ['required', 'string', Rule::in(['this_only', 'all_future', 'all'])],
        ]);

        $scope = DeleteScope::from($request->input('scope'));

        $deleteBooking->handle($request->user(), $booking, $scope);

        return response()->json([], 204);
    }

    /**
     * Format a booking into the BookingResource shape.
     *
     * @return array<string, mixed>
     */
    private function formatBooking(Booking $booking, User $user): array
    {
        $recurrencePattern = $booking->recurrencePattern;
        $recurrenceSummary = null;

        if ($recurrencePattern) {
            $frequency = ucfirst($recurrencePattern->frequency->value);
            $recurrenceSummary = $recurrencePattern->end_date
                ? "{$frequency} until {$recurrencePattern->end_date->format('Y-m-d')}"
                : ($recurrencePattern->end_count
                    ? "{$frequency} ({$recurrencePattern->end_count} times)"
                    : $frequency);
        }

        return [
            'id' => $booking->id,
            'name' => $booking->name,
            'starts_at' => $booking->starts_at->toIso8601String(),
            'ends_at' => $booking->ends_at->toIso8601String(),
            'congregation_id' => $booking->congregation_id,
            'congregation_color' => $booking->congregation->color ?? null,
            'congregation_name' => $booking->congregation->name,
            'user_id' => $booking->user_id,
            'user_name' => $booking->user?->name ?? __('Deleted user'),
            'rooms' => $booking->rooms->map(fn ($room) => [
                'id' => $room->id,
                'name' => $room->name,
            ])->values()->all(),
            'recurrence_pattern_id' => $booking->recurrence_pattern_id,
            'recurrence_summary' => $recurrenceSummary,
            'is_exception' => $booking->is_exception,
            'can_edit' => $user->can('update', $booking),
            'can_delete' => $user->can('delete', $booking),
        ];
    }

    /**
     * Merge separate date/time fields from the form into starts_at/ends_at format.
     *
     * The BookingDialog sends: start_date, start_time, end_date, end_time, room_ids[]
     * The CreateBooking action expects: starts_at, ends_at, room_ids
     *
     * @return array<string, mixed>
     */
    private function mergeBookingData(Request $request): array
    {
        $data = $request->only(['name', 'starts_at', 'ends_at', 'room_ids', 'recurrence']);

        // If the form sends separate date/time fields, combine them
        if (! isset($data['starts_at']) && $request->has('start_date')) {
            $data['starts_at'] = $request->input('start_date').' '.$request->input('start_time', '00:00').':00';
        }

        if (! isset($data['ends_at']) && $request->has('end_date')) {
            $data['ends_at'] = $request->input('end_date').' '.$request->input('end_time', '00:00').':00';
        }

        // Build recurrence data from form fields if not already structured
        if (! isset($data['recurrence']) && $request->has('is_recurring') && $request->input('is_recurring')) {
            $recurrence = [
                'frequency' => $request->input('recurrence_frequency', 'weekly'),
            ];

            if ($request->input('recurrence_end_type') === 'date' && $request->has('recurrence_end_date')) {
                $recurrence['end_date'] = $request->input('recurrence_end_date');
            } elseif ($request->input('recurrence_end_type') === 'count' && $request->has('recurrence_end_count')) {
                $recurrence['end_count'] = (int) $request->input('recurrence_end_count');
            }

            $data['recurrence'] = $recurrence;
        }

        return $data;
    }
}
