export type BookingResource = {
    id: string;
    name: string;
    starts_at: string;
    ends_at: string;
    congregation_id: string;
    congregation_color: string | null;
    congregation_name: string;
    user_id: string;
    user_name: string;
    rooms: { id: string; name: string }[];
    recurrence_pattern_id: string | null;
    recurrence_summary: string | null;
    is_exception: boolean;
    can_edit: boolean;
    can_delete: boolean;
};
