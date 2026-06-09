import type { Auth } from '@/types/auth';
import type { Congregation } from '@/types/congregations';

declare module 'react' {
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            currentCongregation: Congregation | null;
            currentCongregationRole: 'superadmin' | 'admin' | 'member' | null;
            congregations: Congregation[];
            [key: string]: unknown;
        };
    }
}
