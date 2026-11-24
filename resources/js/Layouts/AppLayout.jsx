import { Link, router, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { Flash, cx } from '@/Components/ui';
import { useCan } from '@/lib/can';

function BrandLogo({ className = '' }) {
    return (
        <img
            src="/images/logo.png"
            alt="Logo Arek Wetan Organizer"
            className={cx('object-contain', className)}
            draggable="false"
        />
    );
}

function Avatar({ user, className = 'h-9 w-9' }) {
    const [failed, setFailed] = useState(false);
    const src = user?.photo_url;
    const initial = (user?.name || '?').trim().charAt(0).toUpperCase();

    // Reset status error kalau foto berubah (misal habis ganti foto di profil)
    useEffect(() => {
        setFailed(false);
    }, [src]);

    if (src && !failed) {
        return (
            <img
                src={src}
                alt={user?.name ? `Foto ${user.name}` : 'Foto profil'}
                onError={() => setFailed(true)}
                className={cx('shrink-0 rounded-full bg-[#282c34] object-cover ring-2 ring-[#3dd3d8]/60', className)}
            />
        );
    }

    return (
        <span
            aria-hidden="true"
            className={cx('flex shrink-0 items-center justify-center rounded-full bg-[#3dd3d8] text-sm font-bold text-[#20232a]', className)}
        >
            {initial}
        </span>
    );
}

function Chevron({ open, className = '' }) {
    return (
        <svg
            viewBox="0 0 20 20"
            fill="currentColor"
            aria-hidden="true"
            className={cx('h-4 w-4 shrink-0 transition-transform duration-200', open && 'rotate-180', className)}
        >
            <path
                fillRule="evenodd"
                d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                clipRule="evenodd"
            />
        </svg>
    );
}

function UserMenu({ user, onNavigate }) {
    const [open, setOpen] = useState(false);
    const [confirming, setConfirming] = useState(false);
    const ref = useRef(null);

    const close = () => {
        setOpen(false);
        setConfirming(false);
    };

    // Tutup saat klik di luar / tekan Esc
    useEffect(() => {
        if (!open) return;
        const onDown = (e) => {
            if (ref.current && !ref.current.contains(e.target)) close();
        };
        const onKey = (e) => e.key === 'Escape' && close();
        document.addEventListener('mousedown', onDown);
        document.addEventListener('touchstart', onDown);
        document.addEventListener('keydown', onKey);
        return () => {
            document.removeEventListener('mousedown', onDown);
            document.removeEventListener('touchstart', onDown);
            document.removeEventListener('keydown', onKey);
        };
    }, [open]);

    return (
        <div ref={ref} className="relative border-t border-[#3a3f4b] p-3 pb-[max(0.75rem,env(safe-area-inset-bottom))]">
            {open && (
                <div
                    role="menu"
                    className="absolute inset-x-3 bottom-full mb-2 overflow-hidden rounded-lg border border-[#3a3f4b] bg-[#282c34] shadow-xl shadow-black/40"
                >
                    {!confirming ? (
                        <>
                            <Link
                                href="/profile"
                                role="menuitem"
                                onClick={() => {
                                    close();
                                    onNavigate?.();
                                }}
                                className="block min-h-[44px] px-4 py-3 text-sm font-semibold text-[#e6edf3] hover:bg-white/5"
                            >
                                Profil
                            </Link>
                            <button
                                type="button"
                                role="menuitem"
                                onClick={() => setConfirming(true)}
                                className="block min-h-[44px] w-full border-t border-[#3a3f4b] px-4 py-3 text-left text-sm font-semibold text-[#ff7b84] hover:bg-[#ff7b84]/10"
                            >
                                Keluar
                            </button>
                        </>
                    ) : (
                        <div className="p-3">
                            <p className="text-sm font-semibold text-[#e6edf3]">Yakin ingin keluar?</p>
                            <div className="mt-3 flex gap-2">
                                <button
                                    type="button"
                                    onClick={() => router.post('/logout')}
                                    className="min-h-[40px] flex-1 rounded-md bg-[#B3202A] px-3 text-sm font-semibold text-white hover:bg-[#c92a35]"
                                >
                                    Ya, keluar
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setConfirming(false)}
                                    className="min-h-[40px] flex-1 rounded-md border border-[#3a3f4b] px-3 text-sm font-semibold text-[#e6edf3] hover:bg-white/5"
                                >
                                    Batal
                                </button>
                            </div>
                        </div>
                    )}
                </div>
            )}

            <button
                type="button"
                onClick={() => (open ? close() : setOpen(true))}
                aria-haspopup="menu"
                aria-expanded={open}
                className="flex min-h-[48px] w-full items-center gap-3 rounded-md px-2 py-2 text-left hover:bg-white/5"
            >
                <Avatar user={user} className="h-9 w-9" />
                <span className="min-w-0 flex-1">
                    <span className="block truncate text-sm font-bold leading-tight text-white">{user?.name}</span>
                    <span className="block truncate text-xs text-[#9aa4b2]">
                        {user?.nomor_anggota ? <span className="tabular-nums">{user.nomor_anggota} · </span> : null}
                        {user?.roles?.join(', ') || 'Tanpa peran'}
                    </span>
                </span>
                <Chevron open={!open} className="text-[#9aa4b2]" />
            </button>
        </div>
    );
}

export default function AppLayout({ children }) {
    const { props, url } = usePage();
    const auth = props.auth;
    const can = useCan();
    const [open, setOpen] = useState(false);
    const [openGroups, setOpenGroups] = useState({});
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
                { href: '/activities', label: 'Kegiatan' },
                can('members.view') && { href: '/members', label: 'Anggota' },
                can('organization.view') && { href: '/organisasi', label: 'Divisi, Jabatan, Periode' },
            ],
        },
        {
            label: 'Dokumentasi',
            items: [
                { href: '/albums', label: 'Galeri' },
                { href: '/news', label: 'Berita' },
                { href: '/archive', label: 'Arsip Dokumen' },
            ],
        },
        {
            label: 'Website',
            items: [
                can('content.publish') && { href: '/site/content', label: 'Kurasi Konten Publik' },
                can('site.manage') && { href: '/site/settings', label: 'Pengaturan Website' },
                can('site.manage') && { href: '/site/messages', label: 'Pesan Masuk' },
                { href: '/', label: 'Lihat Website', activeIf: false },
            ],
        },
        {
            label: 'Kemitraan',
            items: [
                {
                    href: '/partners',
                    label: 'Mitra & UMKM',
                    activeIf: url === '/partners' || url.startsWith('/partners?') || (url.startsWith('/partners/') && !url.startsWith('/partners/promotions') && !url.startsWith('/partners/partnerships')),
                },
                { href: '/partners/promotions', label: 'Promosi Mitra' },
                can('partners.view') && { href: '/partners/partnerships', label: 'Kerja Sama' },
            ],
        },
        {
            label: 'Inventaris',
            items: [
                {
                    href: '/inventory',
                    label: 'Barang',
                    activeIf: url === '/inventory' || url.startsWith('/inventory?') || (url.startsWith('/inventory/') && !url.startsWith('/inventory/loans')),
                },
                { href: '/inventory/loans', label: 'Peminjaman' },
            ],
        },
        {
            label: 'Keuangan',
            items: [
                { href: '/finance', label: 'Laporan Keuangan', activeIf: url === '/finance' || url.startsWith('/finance/reports') || url.startsWith('/finance?') },
                ['finance.view', 'finance.record', 'finance.review', 'finance.approve', 'finance.publish'].some(can) && { href: '/finance/transactions', label: 'Buku Kas' },
                { href: '/finance/disputes', label: 'Sanggahan' },
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

    const isGroupOpen = (g) => openGroups[g.label] ?? g.items.some(isActive);
    const toggleGroup = (g) => setOpenGroups((s) => ({ ...s, [g.label]: !isGroupOpen(g) }));

    // Tutup drawer saat pindah halaman
    useEffect(() => {
        setOpen(false);
    }, [url]);

    // Esc menutup drawer + kunci scroll body saat drawer terbuka
    useEffect(() => {
        if (!open) return;
        const onKey = (e) => e.key === 'Escape' && setOpen(false);
        document.addEventListener('keydown', onKey);
        const prev = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        return () => {
            document.removeEventListener('keydown', onKey);
            document.body.style.overflow = prev;
        };
    }, [open]);

    return (
        <div
            className="min-h-screen bg-[#eaf6f7] lg:flex print:bg-white"
            style={{
                backgroundImage:
                    'radial-gradient(circle, rgba(61,211,216,0.35) 1px, transparent 1.5px), radial-gradient(ellipse at top right, rgba(61,211,216,0.18), transparent 55%)',
                backgroundSize: '22px 22px, 100% 100%',
                backgroundAttachment: 'fixed',
            }}
        >
            {/* Top bar (mobile & tablet) */}
            <header className="sticky top-0 z-20 flex items-center justify-between border-b border-[#3a3f4b] bg-[#20232a] px-4 py-2 pt-[max(0.5rem,env(safe-area-inset-top))] lg:hidden print:hidden">
                <span className="flex items-center gap-2.5 text-lg font-extrabold tracking-tight text-white">
                    <BrandLogo className="h-9 w-auto" />
                    SIMPEM
                </span>
                <button
                    type="button"
                    onClick={() => setOpen(true)}
                    aria-expanded={open}
                    aria-controls="sidebar"
                    aria-label="Buka menu"
                    className="flex h-11 w-11 items-center justify-center rounded-md border border-[#3a3f4b] text-[#3dd3d8] hover:bg-white/5"
                >
                    <svg viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" aria-hidden="true">
                        <path d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </header>

            {/* Overlay */}
            {open && (
                <div
                    className="fixed inset-0 z-30 bg-black/60 backdrop-blur-[2px] lg:hidden print:hidden"
                    onClick={() => setOpen(false)}
                    aria-hidden="true"
                />
            )}

            {/* Sidebar */}
            <aside
                id="sidebar"
                className={cx(
                    'fixed inset-y-0 left-0 z-40 flex h-dvh w-72 max-w-[85vw] flex-col border-r border-[#3a3f4b] bg-[#20232a] shadow-2xl transition-transform duration-200 ease-out',
                    'lg:sticky lg:top-0 lg:z-auto lg:h-screen lg:w-64 lg:max-w-none lg:shrink-0 lg:translate-x-0 lg:shadow-none print:hidden',
                    open ? 'translate-x-0' : '-translate-x-full max-lg:invisible',
                )}
            >
                <div className="flex items-center justify-between px-6 pb-4 pt-[max(1.5rem,env(safe-area-inset-top))]">
                    <div className="flex items-center gap-3">
                        <BrandLogo className="h-16 w-auto shrink-0" />
                        <div className="text-2xl font-extrabold leading-none tracking-tight text-white">SIMPEM</div>
                    </div>
                    <button
                        type="button"
                        onClick={() => setOpen(false)}
                        aria-label="Tutup menu"
                        className="-mr-3 flex h-11 w-11 items-center justify-center rounded-md text-[#9aa4b2] hover:bg-white/5 lg:hidden"
                    >
                        <svg viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" aria-hidden="true">
                            <path d="M6 6l12 12M18 6L6 18" />
                        </svg>
                    </button>
                </div>

                <nav className="flex-1 overflow-y-auto overscroll-contain px-3 py-2" aria-label="Menu utama">
                    {groups.map((g, gi) => {
                        const expanded = !g.label || isGroupOpen(g);
                        return (
                            <div key={gi} className={gi > 0 ? 'mt-2' : ''}>
                                {g.label && (
                                    <button
                                        type="button"
                                        onClick={() => toggleGroup(g)}
                                        aria-expanded={expanded}
                                        className="flex min-h-[40px] w-full items-center justify-between rounded-md px-3 text-xs font-semibold uppercase tracking-wide text-[#7d8797] hover:bg-white/5 hover:text-[#c9d1d9]"
                                    >
                                        {g.label}
                                        <Chevron open={expanded} />
                                    </button>
                                )}
                                {expanded &&
                                    g.items.map((n) => (
                                        <Link
                                            key={n.href}
                                            href={n.href}
                                            onClick={() => setOpen(false)}
                                            aria-current={isActive(n) ? 'page' : undefined}
                                            className={cx(
                                                'mb-0.5 flex min-h-[44px] items-center rounded-md border-l-[3px] px-3 py-2 text-sm font-semibold transition-colors',
                                                isActive(n)
                                                    ? 'border-[#3dd3d8] bg-[#3dd3d8]/10 text-[#3dd3d8]'
                                                    : 'border-transparent text-[#c9d1d9] hover:bg-white/5 hover:text-white',
                                            )}
                                        >
                                            {n.label}
                                        </Link>
                                    ))}
                            </div>
                        );
                    })}
                </nav>

                <UserMenu user={user} onNavigate={() => setOpen(false)} />
            </aside>

            <main className="min-w-0 flex-1 px-4 py-6 sm:px-6 sm:py-8 lg:px-10 print:p-0">
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