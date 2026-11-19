import { Link, router, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import { Flash, cx } from '@/Components/ui';
import { useCan } from '@/lib/can';

function NavIcon({ name, className = 'h-4 w-4 shrink-0' }) {
    switch (name) {
        case 'dashboard':
            return (
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className={className}>
                    <rect x="3" y="3" width="7" height="9" rx="1.5" />
                    <rect x="14" y="3" width="7" height="5" rx="1.5" />
                    <rect x="14" y="12" width="7" height="9" rx="1.5" />
                    <rect x="3" y="16" width="7" height="5" rx="1.5" />
                </svg>
            );
        case 'card':
            return (
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className={className}>
                    <rect x="2" y="5" width="20" height="14" rx="2" />
                    <line x1="2" y1="10" x2="22" y2="10" />
                </svg>
            );
        case 'users':
            return (
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className={className}>
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                    <circle cx="9" cy="7" r="4" />
                    <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                    <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                </svg>
            );
        case 'member':
            return (
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className={className}>
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                    <circle cx="9" cy="7" r="4" />
                    <polyline points="16 11 18 13 22 9" />
                </svg>
            );
        case 'org':
            return (
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className={className}>
                    <rect x="4" y="2" width="16" height="20" rx="2" />
                    <path d="M9 22v-4h6v4" />
                    <path d="M8 6h.01M16 6h.01M12 6h.01M12 10h.01M12 14h.01M16 10h.01M16 14h.01M8 10h.01M8 14h.01" />
                </svg>
            );
        case 'folder':
            return (
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className={className}>
                    <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z" />
                </svg>
            );
        case 'user-cog':
            return (
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className={className}>
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                    <circle cx="9" cy="7" r="4" />
                    <circle cx="19" cy="11" r="2" />
                </svg>
            );
        case 'shield':
            return (
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className={className}>
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                    <polyline points="9 12 11 14 15 10" />
                </svg>
            );
        case 'audit':
            return (
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className={className}>
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                    <polyline points="14 2 14 8 20 8" />
                    <line x1="16" y1="13" x2="8" y2="13" />
                    <line x1="16" y1="17" x2="8" y2="17" />
                </svg>
            );
        case 'settings':
            return (
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className={className}>
                    <circle cx="12" cy="12" r="3" />
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z" />
                </svg>
            );
        case 'profile':
            return (
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className={className}>
                    <circle cx="12" cy="8" r="4" />
                    <path d="M20 21a8 8 0 0 0-16 0" />
                </svg>
            );
        case 'logout':
            return (
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className={className}>
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                    <polyline points="16 17 21 12 16 7" />
                    <line x1="21" y1="12" x2="9" y2="12" />
                </svg>
            );
        case 'chevron':
            return (
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className={className}>
                    <polyline points="6 9 12 15 18 9" />
                </svg>
            );
        case 'collapse':
            return (
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className={className}>
                    <rect x="3" y="4" width="18" height="16" rx="2" />
                    <line x1="9" y1="4" x2="9" y2="20" />
                    <polyline points="15 10 12.5 12 15 14" />
                </svg>
            );
        default:
            return (
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className={className}>
                    <rect x="3" y="4" width="18" height="16" rx="2" />
                    <line x1="9" y1="4" x2="9" y2="20" />
                    <polyline points="13 10 15.5 12 13 14" />
                </svg>
            );
    }
}

export default function AppLayout({ children }) {
    const { props, url } = usePage();
    const auth = props.auth;
    const can = useCan();
    const [open, setOpen] = useState(false);
    const [collapsed, setCollapsed] = useState(false);
    const user = auth?.user;

    const groups = useMemo(
        () =>
            [
                {
                    key: 'utama',
                    label: null,
                    icon: 'dashboard',
                    dropdown: false,
                    items: [
                        { href: '/dashboard', label: 'Dashboard', icon: 'dashboard' },
                        user?.member_id && {
                            href: '/kartu-saya',
                            label: 'Kartu Saya',
                            icon: 'card',
                            activeIf: url === `/members/${user.member_id}/card`,
                        },
                    ],
                },
                {
                    key: 'organisasi',
                    label: 'Organisasi',
                    icon: 'folder',
                    dropdown: true,
                    items: [
                        { href: '/struktur', label: 'Struktur Pengurus', icon: 'users' },
                        can('members.view') && { href: '/members', label: 'Anggota', icon: 'member' },
                        can('organization.view') && {
                            href: '/organisasi',
                            label: 'Divisi, Jabatan, Periode',
                            icon: 'org',
                        },
                    ],
                },
                {
                    key: 'sistem',
                    label: 'Sistem',
                    icon: 'settings',
                    dropdown: true,
                    items: [
                        can('users.view') && { href: '/users', label: 'Akun Pengguna', icon: 'user-cog' },
                        can('roles.view') && { href: '/roles', label: 'Role & Permission', icon: 'shield' },
                        can('audit.view') && { href: '/audit-logs', label: 'Audit Log', icon: 'audit' },
                    ],
                },
            ]
                .map((g) => ({ ...g, items: g.items.filter(Boolean) }))
                .filter((g) => g.items.length > 0),
        [can, url, user?.member_id],
    );

    const isActive = (n) =>
        n.activeIf ?? (url === n.href || url.startsWith(n.href + '/') || url.startsWith(n.href + '?'));

    const [openGroups, setOpenGroups] = useState({
        organisasi: true,
        sistem: false,
    });

    useEffect(() => {
        setOpenGroups((prev) => {
            const next = { ...prev };
            for (const g of groups) {
                if (g.dropdown && g.items.some((item) => isActive(item))) {
                    next[g.key] = true;
                }
            }
            return next;
        });
    }, [groups, url]);

    const toggleGroup = (key) => {
        if (collapsed) {
            setCollapsed(false);
            setOpenGroups((prev) => ({ ...prev, [key]: true }));
            return;
        }
        setOpenGroups((prev) => ({ ...prev, [key]: !prev[key] }));
    };

    return (
        <div className="min-h-screen bg-[#F4F6F9] text-[#16202E] lg:flex">
            {/* Bar Atas Mobile */}
            <div className="flex items-center justify-between border-b border-white/10 bg-[#08090B] px-4 py-3 text-white lg:hidden print:hidden">
                <Link href="/dashboard" className="min-w-0">
                    <div className="truncate text-sm font-extrabold leading-none tracking-tight text-white">
                        SIMPEM
                    </div>
                    <div className="mt-0.5 truncate text-[11px] font-medium text-[#7A8B8C]">
                        Arek Wetan Organizer
                    </div>
                </Link>
                <button
                    type="button"
                    onClick={() => setOpen(!open)}
                    aria-expanded={open}
                    aria-controls="sidebar"
                    className="rounded-lg border border-white/15 bg-white/5 px-3 py-1.5 text-xs font-semibold text-white hover:bg-white/10"
                >
                    {open ? 'Tutup' : 'Menu'}
                </button>
            </div>

            {/* Sidebar Tunggal: Lebar Normal <-> Minimize */}
            <aside
                id="sidebar"
                className={cx(
                    'w-full shrink-0 flex-col justify-between bg-[#08090B] text-[#E6EDF3] transition-[width] duration-200 ease-out print:hidden lg:sticky lg:top-0 lg:flex lg:h-screen',
                    collapsed ? 'lg:w-[72px]' : 'lg:w-64',
                    open ? 'flex' : 'hidden',
                )}
            >
                <div className="flex min-h-0 flex-1 flex-col">
                    {/* Header Brand + Tombol Minimize/Expand */}
                    <div
                        className={cx(
                            'flex items-center border-b border-white/10 px-4 py-4',
                            collapsed ? 'lg:justify-center lg:px-2' : 'justify-between gap-2',
                        )}
                    >
                        {!collapsed && (
                            <Link href="/dashboard" className="min-w-0">
                                <div className="truncate text-sm font-extrabold leading-none tracking-tight text-white">
                                    SIMPEM
                                </div>
                                <div className="mt-1 truncate text-[11px] font-medium text-[#7A8B8C]">
                                    Arek Wetan Organizer
                                </div>
                            </Link>
                        )}

                        <button
                            type="button"
                            onClick={() => setCollapsed((prev) => !prev)}
                            title={collapsed ? 'Perlebar sidebar' : 'Minimize sidebar'}
                            aria-label={collapsed ? 'Perlebar sidebar' : 'Minimize sidebar'}
                            className="hidden h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-white/10 bg-white/[0.03] text-[#7A8B8C] transition hover:border-[#36D8E8]/40 hover:bg-white/[0.07] hover:text-[#36D8E8] lg:inline-flex"
                        >
                            <NavIcon name={collapsed ? 'expand' : 'collapse'} />
                        </button>
                    </div>

                    {/* Daftar Menu */}
                    <nav className="flex-1 overflow-y-auto px-3 py-3" aria-label="Menu utama">
                        {groups.map((g, gi) => {
                            const isDropdownOpen = !g.dropdown || Boolean(openGroups[g.key]);
                            const hasActiveChild = g.items.some((item) => isActive(item));

                            return (
                                <div key={g.key} className={gi > 0 ? 'mt-4' : ''}>
                                    {g.label && !collapsed && (
                                        <button
                                            type="button"
                                            onClick={() => toggleGroup(g.key)}
                                            aria-expanded={isDropdownOpen}
                                            className="mb-1 flex w-full items-center justify-between rounded-md px-2.5 py-1.5 text-left text-[11px] font-semibold tracking-wider text-[#7A8B8C] uppercase transition hover:bg-white/[0.04] hover:text-white"
                                        >
                                            <span className={cx(hasActiveChild && 'text-[#36D8E8]')}>
                                                {g.label}
                                            </span>
                                            <NavIcon
                                                name="chevron"
                                                className={cx(
                                                    'h-3.5 w-3.5 text-[#7A8B8C] transition-transform duration-200',
                                                    isDropdownOpen ? 'rotate-0' : '-rotate-90',
                                                )}
                                            />
                                        </button>
                                    )}

                                    {g.label && collapsed && (
                                        <button
                                            type="button"
                                            onClick={() => toggleGroup(g.key)}
                                            title={`${g.label} (Klik untuk buka)`}
                                            className={cx(
                                                'mx-auto my-1.5 hidden h-8 w-8 items-center justify-center rounded-lg transition lg:flex',
                                                hasActiveChild
                                                    ? 'bg-[#36D8E8]/10 text-[#36D8E8]'
                                                    : 'text-[#7A8B8C] hover:bg-white/[0.05] hover:text-white',
                                            )}
                                        >
                                            <NavIcon name={g.icon} />
                                        </button>
                                    )}

                                    {isDropdownOpen && (
                                        <div className={cx('space-y-1', g.label && !collapsed && 'pl-1.5')}>
                                            {g.items.map((n) => {
                                                const active = isActive(n);
                                                return (
                                                    <Link
                                                        key={n.href}
                                                        href={n.href}
                                                        title={collapsed ? n.label : undefined}
                                                        onClick={() => setOpen(false)}
                                                        aria-current={active ? 'page' : undefined}
                                                        className={cx(
                                                            'flex items-center rounded-lg py-2 text-[13px] font-medium transition-colors',
                                                            collapsed
                                                                ? 'px-3 lg:justify-center lg:px-0'
                                                                : 'gap-2.5 px-2.5',
                                                            active
                                                                ? 'bg-white/10 font-semibold text-white shadow-[inset_2px_0_0_#36D8E8]'
                                                                : 'text-[#9BA7B4] hover:bg-white/[0.05] hover:text-white',
                                                        )}
                                                    >
                                                        <NavIcon
                                                            name={n.icon}
                                                            className={cx(
                                                                'h-4 w-4 shrink-0',
                                                                active ? 'text-[#36D8E8]' : 'text-[#7A8B8C]',
                                                            )}
                                                        />
                                                        <span className={cx('truncate', collapsed && 'lg:hidden')}>
                                                            {n.label}
                                                        </span>
                                                    </Link>
                                                );
                                            })}
                                        </div>
                                    )}
                                </div>
                            );
                        })}
                    </nav>
                </div>

                {/* Footer Profil & Keluar */}
                <div className="border-t border-white/10 bg-[#060709] p-3">
                    {!collapsed ? (
                        <>
                            <div className="truncate text-xs font-bold text-white">{user?.name}</div>
                            <div className="mt-0.5 flex items-center justify-between gap-2 text-[11px] text-[#7A8B8C]">
                                <span className="tabular-nums">{user?.nomor_anggota}</span>
                                <span className="truncate">{user?.roles?.join(', ') || 'Tanpa peran'}</span>
                            </div>
                            <div className="mt-3 flex gap-2">
                                <Link
                                    href="/profile"
                                    onClick={() => setOpen(false)}
                                    className="flex-1 rounded-lg border border-white/12 bg-white/[0.04] px-2.5 py-1.5 text-center text-xs font-semibold text-[#E6EDF3] transition hover:bg-white/10"
                                >
                                    Profil
                                </Link>
                                <button
                                    type="button"
                                    onClick={() => router.post('/logout')}
                                    className="flex-1 rounded-lg border border-white/12 bg-white/[0.04] px-2.5 py-1.5 text-xs font-semibold text-[#E6EDF3] transition hover:border-rose-400/40 hover:bg-rose-500/10 hover:text-rose-300"
                                >
                                    Keluar
                                </button>
                            </div>
                        </>
                    ) : (
                        <div className="flex flex-col items-center gap-1.5">
                            <Link
                                href="/profile"
                                title={user?.name ? `Profil (${user.name})` : 'Profil'}
                                onClick={() => setOpen(false)}
                                className="flex h-9 w-9 items-center justify-center rounded-lg text-[#9BA7B4] transition hover:bg-white/10 hover:text-white"
                            >
                                <NavIcon name="profile" />
                            </Link>
                            <button
                                type="button"
                                title="Keluar"
                                onClick={() => router.post('/logout')}
                                className="flex h-9 w-9 items-center justify-center rounded-lg text-[#9BA7B4] transition hover:bg-rose-500/10 hover:text-rose-400"
                            >
                                <NavIcon name="logout" />
                            </button>
                        </div>
                    )}
                </div>
            </aside>

            {/* Konten Utama */}
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