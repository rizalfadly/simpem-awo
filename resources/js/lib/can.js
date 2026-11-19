import { usePage } from '@inertiajs/react';

/** const can = useCan(); can('users.view')  — Super Admin ('*') selalu true. */
export function useCan() {
    const { auth } = usePage().props;
    const perms = auth?.permissions ?? [];
    return (permission) => perms.includes('*') || perms.includes(permission);
}
