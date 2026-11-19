import { Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Flash, cx } from '@/Components/ui';
import { useCan } from '@/lib/can';

export default function AppLayout({ children }) {
    const { props, url } = usePage();
    const auth = props.auth;
    const can = useCan();
    const [open, setOpen] = useState(false);
    const user = auth?.user;

    const groups = [
        {
            label: null,
            items: [
                { href: '/dashboard', label: 'Dashboard' },
                user?.member_id && { href: '/kartu-saya', label: 'Kartu Saya', activeIf: url === `/members/${user.member_id}/card` },
            ],
        },
        {
            label: 'Organisasi',
            items: [
                { href: '/struktur', label: 'Struktur Pengurus' },
                can('members.view') && { href: '/members', label: 'Anggota' },
                can('organization.view') && { href: '/organisasi', label: 'Divisi, Jabatan, Periode' },
            ],
        },
        {
            label: 'Sistem',
            items: [
                can('users.view') && { href: '/users', label: 'Akun Pengguna' },
                can('roles.view') && { href: '/roles', label: 'Role & Permission' },
                can('audit.view') && { href: '/audit-logs', label: 'Audit Log' },
            ],
        },
    ]
        .map((g) => ({ ...g, items: g.items.filter(Boolean) }))
        .filter((g) => g.items.length > 0);

    const isActive = (n) =>
        n.activeIf ?? (url === n.href || url.startsWith(n.href + '/') || url.startsWith(n.href + '?'));

    return (
        <div className="min-h-screen lg:flex">
            <div className="flex items-center justify-between border-b border-[#DDE2E9] bg-white px-4 py-3 lg:hidden print:hidden">
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
                    'w-full flex-col border-r border-[#DDE2E9] bg-white lg:sticky lg:top-0 lg:flex lg:h-screen lg:w-64 lg:shrink-0 print:hidden',
                    open ? 'flex' : 'hidden',
                )}
            >
                <div className="hidden px-6 pb-4 pt-7 lg:block">
                    <div className="text-2xl font-extrabold leading-none tracking-tight text-[#16202E]">SIMPEM</div>
                    <div className="mt-1 text-xs font-medium text-[#5B6779]">Arek Wetan Organizer</div>
                </div>

                <nav className="flex-1 overflow-y-auto px-3 py-2" aria-label="Menu utama">
                    {groups.map((g, gi) => (
                        <div key={gi} className={gi > 0 ? 'mt-4' : ''}>
                            {g.label && <div className="mb-1 px-3 text-xs font-semibold text-[#8A94A4]">{g.label}</div>}
                            {g.items.map((n) => (
                                <Link
                                    key={n.href}
                                    href={n.href}
                                    onClick={() => setOpen(false)}
                                    aria-current={isActive(n) ? 'page' : undefined}
                                    className={cx(
                                        'mb-0.5 block rounded-md border-l-[3px] px-3 py-2 text-sm font-semibold',
                                        isActive(n)
                                            ? 'border-[#B3202A] bg-[#F2F4F7] text-[#16202E]'
                                            : 'border-transparent text-[#4A5568] hover:bg-[#F2F4F7]',
                                    )}
                                >
                                    {n.label}
                                </Link>
                            ))}
                        </div>
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

            <main className="min-w-0 flex-1 px-4 py-8 sm:px-8 lg:px-10 print:p-0">
                <div className="mx-auto max-w-6xl">
                    <div className="print:hidden">
                        <Flash />
                    </div>
                    {children}
                </div>
            </main>
        </div>
    );
}
