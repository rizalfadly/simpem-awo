import { Head, Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { cx } from '@/Components/ui';

const NAV = [
    ['/', 'Beranda'],
    ['/tentang', 'Tentang'],
    ['/struktur-pengurus', 'Struktur'],
    ['/kegiatan', 'Kegiatan'],
    ['/berita', 'Berita'],
    ['/galeri', 'Galeri'],
    ['/dokumen', 'Dokumen'],
    ['/mitra', 'Mitra'],
    ['/kontak', 'Kontak'],
];

export default function PublicLayout({ title, children }) {
    const { props, url } = usePage();
    const { site, meta, auth, flash } = props;
    const [open, setOpen] = useState(false);
    const [quickEmail, setQuickEmail] = useState('');
    const [subscribed, setSubscribed] = useState(false);

    const active = (href) => (href === '/' ? url === '/' : url.startsWith(href));
    const siteName = site?.name || 'Arek Wetan Organizer';

    return (
        <div className="flex min-h-screen flex-col bg-[#F4F6F9] text-[#16202E] selection:bg-[#36D8E8] selection:text-[#08090B]">
            <Head title={title ? `${title} | ${siteName}` : siteName}>
                {meta?.description && <meta name="description" content={meta.description} />}
            </Head>

            {/* Navbar Gelap Beraksen Cyan */}
            <header className="sticky top-0 z-30 border-b border-white/10 bg-[#08090B]/95 text-white backdrop-blur-md">
                <div className="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3 sm:px-6">
                    <Link href="/" className="group flex items-center gap-3">
                        {site?.logo ? (
                            <img
                                src={site.logo}
                                alt=""
                                className="h-9 w-9 rounded-lg border border-white/15 object-cover"
                            />
                        ) : (
                            <span className="flex h-9 w-9 items-center justify-center rounded-lg border border-[#36D8E8]/40 bg-[#36D8E8]/10 text-sm font-black text-[#36D8E8]">
                                {siteName.slice(0, 1)}
                            </span>
                        )}
                        <div className="min-w-0">
                            <span className="block truncate text-base font-extrabold leading-tight tracking-tight text-white transition group-hover:text-[#36D8E8]">
                                {siteName}
                            </span>
                        </div>
                    </Link>

                    <nav aria-label="Menu utama" className="hidden items-center gap-1 lg:flex">
                        {NAV.map(([href, label]) => {
                            const isCurrent = active(href);
                            return (
                                <Link
                                    key={href}
                                    href={href}
                                    aria-current={isCurrent ? 'page' : undefined}
                                    className={cx(
                                        'rounded-lg px-3 py-2 text-sm font-semibold transition-colors',
                                        isCurrent
                                            ? 'bg-white/10 text-[#36D8E8] shadow-[inset_0_-2px_0_#36D8E8]'
                                            : 'text-[#9BA7B4] hover:bg-white/[0.06] hover:text-white',
                                    )}
                                >
                                    {label}
                                </Link>
                            );
                        })}
                    </nav>

                    <div className="flex items-center gap-2">
                        <Link
                            href={auth?.user ? '/dashboard' : '/login'}
                            className="rounded-lg bg-[#36D8E8] px-4 py-2 text-xs font-extrabold tracking-wide text-[#08090B] shadow-[0_0_18px_rgba(54,216,232,0.28)] transition hover:bg-[#67E8F9] active:scale-[0.98] sm:text-sm"
                        >
                            {auth?.user ? 'Buka aplikasi' : 'Masuk anggota'}
                        </Link>
                        <button
                            type="button"
                            onClick={() => setOpen(!open)}
                            aria-expanded={open}
                            aria-controls="public-menu"
                            className="rounded-lg border border-white/15 bg-white/5 px-3 py-2 text-xs font-bold text-white hover:bg-white/10 lg:hidden"
                        >
                            {open ? 'Tutup' : 'Menu'}
                        </button>
                    </div>
                </div>

                {/* Drawer Menu Mobile */}
                {open && (
                    <nav
                        id="public-menu"
                        aria-label="Menu seluler"
                        className="border-t border-white/10 bg-[#0B0D10] px-4 py-3 lg:hidden"
                    >
                        <div className="space-y-1">
                            {NAV.map(([href, label]) => {
                                const isCurrent = active(href);
                                return (
                                    <Link
                                        key={href}
                                        href={href}
                                        onClick={() => setOpen(false)}
                                        className={cx(
                                            'block rounded-lg px-3 py-2.5 text-sm font-semibold transition-colors',
                                            isCurrent
                                                ? 'bg-white/10 text-[#36D8E8]'
                                                : 'text-[#9BA7B4] hover:bg-white/[0.06] hover:text-white',
                                        )}
                                    >
                                        {label}
                                    </Link>
                                );
                            })}
                        </div>
                    </nav>
                )}
            </header>

            <main className="flex-1">
                {flash?.success && (
                    <div role="status" className="mx-auto mt-6 max-w-6xl px-4 sm:px-6">
                        <p className="rounded-xl border border-[#B9DFC7] bg-[#EDF8F1] px-4 py-3 text-sm font-medium text-[#1E6B3C]">
                            {flash.success}
                        </p>
                    </div>
                )}
                {children}
            </main>

            {/* Footer Gelap-Cyan Editorial (Wetan Dispatch) + Kontak & Sosial */}
            <footer className="relative mt-16 overflow-hidden border-t border-white/10 bg-[#08090B] text-white">
                <div className="awo-hero-grid pointer-events-none absolute inset-0" aria-hidden="true" />
                <div
                    className="pointer-events-none absolute -bottom-24 right-10 h-72 w-72 rounded-full bg-[#36D8E8]/10 blur-3xl"
                    aria-hidden="true"
                />

                <div className="relative mx-auto max-w-6xl px-4 py-12 sm:px-6 sm:py-14">
                    <div className="grid gap-10 lg:grid-cols-12">
                        {/* Kolom Kiri: Brand & Form Email */}
                        <div className="space-y-4 lg:col-span-5">
                            <div className="inline-flex items-center gap-2 rounded-full border border-[#36D8E8]/35 bg-[#36D8E8]/10 px-3 py-1 text-[10px] font-black tracking-widest text-[#36D8E8] uppercase">
                                <span className="h-1.5 w-1.5 rounded-full bg-[#36D8E8]" />
                                <span>Arek Wetan Widget</span>
                            </div>

                            <h2 className="text-2xl font-black tracking-tight text-white">
                                {siteName}
                            </h2>

                            <p className="max-w-md text-sm leading-relaxed text-[#94A3B8]">
                                {site?.tagline ||
                                    'Ruang kolaborasi, event kreatif, arsip kegiatan, dan jejaring UMKM anak muda yang gerak bareng.'}
                            </p>

                            <form
                                onSubmit={(e) => {
                                    e.preventDefault();
                                    if (!quickEmail.trim()) return;
                                    setSubscribed(true);
                                    setQuickEmail('');
                                }}
                                className="flex max-w-md gap-2 pt-1"
                            >
                                <input
                                    type="email"
                                    value={quickEmail}
                                    onChange={(e) => {
                                        setSubscribed(false);
                                        setQuickEmail(e.target.value);
                                    }}
                                    placeholder="Email kamu buat info event & update..."
                                    aria-label="Email buat info event & update"
                                    className="w-full rounded-xl border border-white/15 bg-white/[0.04] px-3.5 py-2 text-xs font-medium text-white placeholder:text-[#7A8B8C] focus:border-[#36D8E8] focus:outline-none"
                                />
                                <button
                                    type="submit"
                                    className="shrink-0 rounded-xl bg-[#36D8E8] px-4 py-2 text-xs font-black text-[#08090B] shadow-[0_0_16px_rgba(54,216,232,0.28)] transition hover:bg-[#67E8F9]"
                                >
                                    Join
                                </button>
                            </form>

                            {subscribed && (
                                <p className="text-xs font-bold text-[#36D8E8]">
                                    Sip! Email kamu sudah tercatat untuk info update berikutnya.
                                </p>
                            )}
                        </div>

                        {/* Kolom Kanan: Eksplor, Arsip & Relasi, Akses + Kontak & Sosial */}
                        <div className="grid grid-cols-2 gap-8 sm:grid-cols-3 lg:col-span-7">
                            <div>
                                <p className="text-xs font-black tracking-widest text-[#36D8E8] uppercase">
                                    Eksplor
                                </p>
                                <ul className="mt-3.5 space-y-2 text-sm text-[#94A3B8]">
                                    <li><Link href="/tentang" className="transition hover:text-white">Tentang</Link></li>
                                    <li><Link href="/struktur-pengurus" className="transition hover:text-white">Struktur</Link></li>
                                    <li><Link href="/kegiatan" className="transition hover:text-white">Kegiatan</Link></li>
                                    <li><Link href="/berita" className="transition hover:text-white">Berita</Link></li>
                                </ul>
                            </div>

                            <div>
                                <p className="text-xs font-black tracking-widest text-[#36D8E8] uppercase">
                                    Arsip &amp; Relasi
                                </p>
                                <ul className="mt-3.5 space-y-2 text-sm text-[#94A3B8]">
                                    <li><Link href="/galeri" className="transition hover:text-white">Galeri</Link></li>
                                    <li><Link href="/dokumen" className="transition hover:text-white">Dokumen</Link></li>
                                    <li><Link href="/mitra" className="transition hover:text-white">Mitra &amp; UMKM</Link></li>
                                    <li><Link href="/kontak" className="transition hover:text-white">Kontak</Link></li>
                                </ul>
                            </div>

                            <div className="col-span-2 sm:col-span-1">
                                <p className="text-xs font-black tracking-widest text-[#36D8E8] uppercase">
                                    Akses
                                </p>
                                <ul className="mt-3.5 space-y-2 text-sm text-[#94A3B8]">
                                    <li>
                                        <Link
                                            href={auth?.user ? '/dashboard' : '/login'}
                                            className="font-bold text-white transition hover:text-[#36D8E8]"
                                        >
                                            {auth?.user ? 'Buka SIMPEM →' : 'Masuk anggota →'}
                                        </Link>
                                    </li>
                                    {site?.whatsapp_url && (
                                        <li>
                                            <a
                                                href={site.whatsapp_url}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="transition hover:text-[#36D8E8]"
                                            >
                                                WhatsApp {site.whatsapp || ''}
                                            </a>
                                        </li>
                                    )}
                                    {site?.instagram && (
                                        <li>
                                            <a
                                                href={`https://instagram.com/${site.instagram}`}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="transition hover:text-[#36D8E8]"
                                            >
                                                Instagram @{site.instagram}
                                            </a>
                                        </li>
                                    )}
                                    {site?.facebook && (
                                        <li>
                                            <a
                                                href={site.facebook}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="transition hover:text-[#36D8E8]"
                                            >
                                                Facebook
                                            </a>
                                        </li>
                                    )}
                                    {site?.youtube && (
                                        <li>
                                            <a
                                                href={site.youtube}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="transition hover:text-[#36D8E8]"
                                            >
                                                YouTube
                                            </a>
                                        </li>
                                    )}
                                </ul>
                            </div>
                        </div>
                    </div>

                    {/* Baris Info Kontak Tambahan Jika Terisi di Site */}
                    {(site?.address || site?.email || site?.phone) && (
                        <div className="mt-10 flex flex-wrap items-center justify-between gap-x-6 gap-y-2 border-t border-white/10 pt-5 text-xs text-[#9BA7B4]">
                            {site?.address && <span>{site.address}</span>}
                            <div className="flex flex-wrap items-center gap-4 tabular-nums">
                                {site?.email && (
                                    <a href={`mailto:${site.email}`} className="transition hover:text-[#36D8E8]">
                                        {site.email}
                                    </a>
                                )}
                                {site?.phone && <span>{site.phone}</span>}
                            </div>
                        </div>
                    )}

                    {/* Bottom Bar */}
                    <div className="mt-6 flex flex-wrap items-center justify-between gap-4 border-t border-white/10 pt-6 text-xs text-[#7A8B8C]">
                        <p>© {new Date().getFullYear()} {siteName}. All rights reserved.</p>
                        <p className="font-semibold text-[#94A3B8]">Built for young movers &amp; local energy.</p>
                    </div>
                </div>
            </footer>
        </div>
    );
}

export function Section({ title, action, children, className }) {
    return (
        <section className={cx('mx-auto max-w-6xl px-4 py-10 sm:px-6', className)}>
            {(title || action) && (
                <div className="mb-6 flex items-center justify-between gap-4">
                    {title && (
                        <div className="flex items-center gap-3">
                            <span className="h-6 w-1 rounded-full bg-[#36D8E8]" aria-hidden="true" />
                            <h2 className="text-2xl font-extrabold tracking-tight text-[#08090B]">{title}</h2>
                        </div>
                    )}
                    {action}
                </div>
            )}
            {children}
        </section>
    );
}

export const phaseLabel = { upcoming: 'Akan datang', running: 'Sedang berlangsung', done: 'Selesai' };
export const phaseTone = { upcoming: 'amber', running: 'green', done: 'neutral' };