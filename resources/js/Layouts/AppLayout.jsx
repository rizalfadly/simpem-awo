import { Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Flash, cx } from '@/Components/ui';
import { useCan } from '@/lib/can';

const NAV = [
    { href: '/dashboard', label: 'Dashboard' },
    { href: '/users', label: 'Akun Pengguna', perm: 'users.view' },
    { href: '/roles', label: 'Role & Permission', perm: 'roles.view' },
    { href: '/audit-logs', label: 'Audit Log', perm: 'audit.view' },
];

export default function AppLayout({ children }) {
    const { props, url } = usePage();
    const auth = props.auth;
    const can = useCan();
    const [open, setOpen] = useState(false);
    const user = auth?.user;

    const isActive = (href) => url === href || url.startsWith(href + '/') || url.startsWith(href + '?');

    return (
        <div className="min-h-screen lg:flex">
            {/* Bar atas (mobile) */}
            <div className="flex items-center justify-between border-b border-[#DDE2E9] bg-white px-4 py-3 lg:hidden">
                <span className="text-lg font-extrabold tracking-tight">SIMPEM</span>
                <button
                    type="button"
                    onClick={() => setOpen(!open)}
                    aria-expanded={open}
                    aria-controls="sidebar"
                    className="rounded-md border border-[#CBD2DC] px-3 py-1.5 text-sm font-semibold"
                >
                    Menu
                </button>
            </div>

            <aside
                id="sidebar"
                className={cx(
                    'w-full flex-col border-r border-[#DDE2E9] bg-white lg:sticky lg:top-0 lg:flex lg:h-screen lg:w-64 lg:shrink-0',
                    open ? 'flex' : 'hidden',
                )}
            >
                <div className="hidden px-6 pb-4 pt-7 lg:block">
                    <div className="text-2xl font-extrabold leading-none tracking-tight text-[#16202E]">SIMPEM</div>
                    <div className="mt-1 text-xs font-medium text-[#5B6779]">Arek Wetan Organizer</div>
                </div>

                <nav className="flex-1 px-3 py-2" aria-label="Menu utama">
                    {NAV.filter((n) => !n.perm || can(n.perm)).map((n) => (
                        <Link
                            key={n.href}
                            href={n.href}
                            onClick={() => setOpen(false)}
                            aria-current={isActive(n.href) ? 'page' : undefined}
                            className={cx(
                                'mb-0.5 block rounded-md border-l-[3px] px-3 py-2 text-sm font-semibold',
                                isActive(n.href)
                                    ? 'border-[#B3202A] bg-[#F2F4F7] text-[#16202E]'
                                    : 'border-transparent text-[#4A5568] hover:bg-[#F2F4F7]',
                            )}
                        >
                            {n.label}
                        </Link>
                    ))}
                </nav>

                <div className="border-t border-[#DDE2E9] p-4">
                    <div className="text-sm font-bold leading-tight">{user?.name}</div>
                    <div className="mt-0.5 text-xs tabular-nums text-[#5B6779]">{user?.nomor_anggota}</div>
                    <div className="mt-1 text-xs text-[#5B6779]">{user?.roles?.join(', ') || 'Tanpa peran'}</div>
                    <div className="mt-3 flex gap-2">
                        <Link href="/profile" className="rounded-md border border-[#CBD2DC] px-3 py-1.5 text-xs font-semibold hover:bg-[#F2F4F7]">
                            Profil
                        </Link>
                        <button
                            type="button"
                            onClick={() => router.post('/logout')}
                            className="rounded-md border border-[#CBD2DC] px-3 py-1.5 text-xs font-semibold hover:bg-[#F2F4F7]"
                        >
                            Keluar
                        </button>
                    </div>
                </div>
            </aside>

            <main className="min-w-0 flex-1 px-4 py-8 sm:px-8 lg:px-10">
                <div className="mx-auto max-w-6xl">
                    <Flash />
                    {children}
                </div>
            </main>
        </div>
    );
}
