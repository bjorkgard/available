import type { User } from './auth';

export type CongregationRole = 'superadmin' | 'admin' | 'member';

export type Congregation = {
    id: string;
    name: string;
    slug: string;
    congregation_number: string;
    kingdom_hall_id: string | null;
    color: string | null;
    locale: string;
    kingdom_hall?: KingdomHall;
};

export type KingdomHall = {
    id: string;
    street_address: string;
    zip_code: string;
    city: string;
    number_of_rooms: number;
    rooms?: Room[];
    congregations?: Congregation[];
};

export type Room = {
    id: string;
    kingdom_hall_id: string;
    name: string;
    sort_order: number;
};

export type Membership = {
    id: string;
    user_id: string;
    congregation_id: string;
    role: CongregationRole;
    user?: User;
    created_at: string;
    updated_at: string;
};

export type CongregationInvitation = {
    id: string;
    congregation_id: string;
    name: string;
    email: string;
    role: CongregationRole;
    code: string;
    expires_at: string;
    accepted_at: string | null;
    created_at: string;
};

export type RoleOption = {
    value: CongregationRole;
    label: string;
};
