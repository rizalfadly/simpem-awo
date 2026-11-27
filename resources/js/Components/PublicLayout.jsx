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
                                    Media Sosial
                                </p>
                                <ul className="mt-3.5 space-y-2 text-sm text-[#94A3B8]">
                                    <li className="pt-1">
                                    <div className="flex flex-wrap items-center gap-2">
                                        {site?.whatsapp_url && (
                                            <a
                                                href={site.whatsapp_url}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                aria-label="WhatsApp"
                                                title={site.whatsapp ? `WhatsApp ${site.whatsapp}` : 'WhatsApp'}
                                                className="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-white/15 bg-white/[0.04] text-[#9BA7B4] transition hover:border-[#36D8E8]/50 hover:bg-[#36D8E8]/10 hover:text-[#36D8E8]"
                                            >
                                                <svg
                                                    viewBox="0 0 24 24"
                                                    fill="currentColor"
                                                    aria-hidden="true"
                                                    className="h-4 w-4"
                                                >
                                                    <path d="M12.03 2a9.82 9.82 0 0 0-8.43 14.8L2.25 22l5.33-1.4A9.84 9.84 0 1 0 12.03 2Zm5.72 13.94c-.24.68-1.4 1.3-1.92 1.38-.5.08-1.14.12-1.84-.1-.42-.14-.97-.31-1.68-.62-2.96-1.28-4.9-4.27-5.05-4.47-.15-.2-1.2-1.6-1.2-3.05 0-1.45.76-2.16 1.03-2.46.27-.3.6-.37.8-.37.2 0 .4 0 .58.01.18.01.43-.07.67.51.24.6.82 2.01.9 2.16.07.15.12.32.02.52-.1.2-.15.32-.3.5-.15.18-.31.4-.45.54-.15.15-.3.31-.13.6.17.3.76 1.25 1.63 2.02 1.12 1 2.07 1.31 2.36 1.46.3.15.47.12.64-.07.17-.2.74-.86.94-1.16.2-.3.4-.25.67-.15.27.1 1.72.81 2.02.96.3.15.5.22.57.34.08.12.08.7-.16 1.38Z" />
                                                </svg>
                                            </a>
                                        )}
                                        {site?.instagram && (
                                            <a
                                                href={`https://instagram.com/${site.instagram.replace(/^@/, '')}`}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                aria-label="Instagram"
                                                title={`@${site.instagram.replace(/^@/, '')}`}
                                                className="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-white/15 bg-white/[0.04] text-[#9BA7B4] transition hover:border-[#36D8E8]/50 hover:bg-[#36D8E8]/10 hover:text-[#36D8E8]"
                                            >
                                                <svg
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    strokeWidth="2"
                                                    strokeLinecap="round"
                                                    strokeLinejoin="round"
                                                    aria-hidden="true"
                                                    className="h-4 w-4"
                                                >
                                                    <rect width="20" height="20" x="2" y="2" rx="5" ry="5" />
                                                    <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z" />
                                                    <line x1="17.5" x2="17.51" y1="6.5" y2="6.5" />
                                                </svg>
                                            </a>
                                        )}
                                        {site?.tiktok && (
                                            <a
                                                href={
                                                    site.tiktok.startsWith('http')
                                                        ? site.tiktok
                                                        : `https://www.tiktok.com/@${site.tiktok.replace(/^@/, '')}`
                                                }
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                aria-label="TikTok"
                                                title={`@${site.tiktok.replace(/^https?:\/\/(www\.)?tiktok\.com\/@?|^@/, '')}`}
                                                className="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-white/15 bg-white/[0.04] text-[#9BA7B4] transition hover:border-[#36D8E8]/50 hover:bg-[#36D8E8]/10 hover:text-[#36D8E8]"
                                            >
                                                <svg
                                                    viewBox="0 0 24 24"
                                                    fill="currentColor"
                                                    aria-hidden="true"
                                                    className="h-4 w-4"
                                                >
                                                    <path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1-.1z" />
                                                </svg>
                                            </a>
                                        )}
                                        {site?.youtube && (
                                            <a
                                                href={site.youtube}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                aria-label="YouTube"
                                                title="YouTube"
                                                className="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-white/15 bg-white/[0.04] text-[#9BA7B4] transition hover:border-[#36D8E8]/50 hover:bg-[#36D8E8]/10 hover:text-[#36D8E8]"
                                            >
                                                <svg
                                                    viewBox="0 0 24 24"
                                                    fill="currentColor"
                                                    aria-hidden="true"
                                                    className="h-4 w-4"
                                                >
                                                    <path d="M23.5 6.2a3 3 0 0 0-2.1-2.1C19.5 3.6 12 3.6 12 3.6s-7.5 0-9.4.5A3 3 0 0 0 .5 6.2 31.4 31.4 0 0 0 0 12a31.4 31.4 0 0 0 .5 5.8 3 3 0 0 0 2.1 2.1c1.9.5 9.4.5 9.4.5s7.5 0 9.4-.5a3 3 0 0 0 2.1-2.1A31.4 31.4 0 0 0 24 12a31.4 31.4 0 0 0-.5-5.8ZM9.6 15.6V8.4l6.3 3.6-6.3 3.6Z" />
                                                </svg>
                                            </a>
                                        )}
                                    </div>
                                </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    {/* Baris Info Kontak Tambahan Jika Terisi di Site */}
                    {(site?.address || site?.email || site?.phone || site?.maps_url) && (
                        <div className="mt-10 flex flex-wrap items-center justify-between gap-x-6 gap-y-2 border-t border-white/10 pt-5 text-xs text-[#9BA7B4]">
                            <div className="flex flex-wrap items-center gap-3">
                                {site?.address && (
                                    site?.maps_url ? (
                                        <a
                                            href={site.maps_url}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="inline-flex items-center gap-1.5 transition hover:text-[#36D8E8]"
                                        >
                                            <span>{site.address}</span>
                                            <span className="font-bold text-[#36D8E8]">↗</span>
                                        </a>
                                    ) : (
                                        <span>{site.address}</span>
                                    )
                                )}
                                {!site?.address && site?.maps_url && (
                                    <a
                                        href={site.maps_url}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="inline-flex items-center gap-1 font-semibold text-[#36D8E8] transition hover:underline"
                                    >
                                        <span>Lihat Lokasi di Peta</span>
                                        <span>↗</span>
                                    </a>
                                )}
                            </div>

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