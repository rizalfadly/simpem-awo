import { Head, Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { cx } from '@/Components/ui';

const NAV = [
    ['/', 'Beranda'],
    ['/tentang', 'Tentang'],
    //['/struktur-pengurus', 'Struktur'],
    //['/kegiatan', 'Kegiatan'],
    //['/berita', 'Berita'],
    ['/galeri', 'Galeri'],
    ['/dokumen', 'Dokumen'],
   // ['/mitra', 'Mitra'],
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
    // Taruh gambar ilustrasi utuh kamu di folder public/images/, misal: public/images/awo-hero-scene.webp
    const artworkBg = '/images/awo-hero-scene.webp';

    return (
        <div className="flex min-h-screen flex-col bg-[#1B1528] text-[#121212] selection:bg-[#FF5C00] selection:text-[#121212]">
            <Head title={title ? `${title} | ${siteName}` : siteName}>
                {meta?.description && <meta name="description" content={meta.description} />}
            </Head>

            {/* Top Bar Floating / Translucent ala Referensi */}
            <header className="sticky top-0 z-30 border-b border-black/15 bg-[#F4F4F6]/80 text-[#121212] backdrop-blur-md">
                <div className="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-2.5 sm:px-6">
                    <Link href="/" className="group flex items-center gap-2.5">
                        {site?.logo ? (
                            <img
                                src={site.logo}
                                alt=""
                                className="awo-brutal-box h-9 w-9 object-cover"
                            />
                        ) : (
                            <span className="awo-brutal-box flex h-9 w-9 items-center justify-center bg-[#FBBF24] font-mono text-sm font-black text-[#121212]">
                                {siteName.slice(0, 1)}
                            </span>
                        )}
                        <span className="truncate font-mono text-xs font-black tracking-wider text-[#121212] uppercase transition group-hover:text-[#FF5C00] sm:text-sm">
                            {siteName}
                        </span>
                    </Link>

                    <nav aria-label="Menu utama" className="hidden items-center gap-4 lg:flex">
                        {NAV.map(([href, label]) => {
                            const isCurrent = active(href);
                            return (
                                <Link
                                    key={href}
                                    href={href}
                                    aria-current={isCurrent ? 'page' : undefined}
                                    className={cx(
                                        'px-2 py-1 font-mono text-xs font-extrabold tracking-wide transition-colors',
                                        isCurrent
                                            ? 'bg-[#FBBF24] text-[#121212] underline decoration-[#121212] decoration-2 underline-offset-4'
                                            : 'text-[#121212]/80 hover:text-[#121212]',
                                    )}
                                >
                                    {label}
                                </Link>
                            );
                        })}
                    </nav>

                    <div className="flex items-center gap-2">
                        {/* Ikon Sosial Ringkas di Kanan Atas ala Referensi */}
                        <div className="hidden items-center gap-1.5 sm:flex">
                            {site?.instagram && (
                                <a
                                    href={`https://instagram.com/${site.instagram.replace(/^@/, '')}`}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    aria-label="Instagram"
                                    title={`@${site.instagram.replace(/^@/, '')}`}
                                    className="awo-brutal-btn flex h-8 w-8 items-center justify-center bg-[#FBBF24] text-[#121212]"
                                >
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true" className="h-3.5 w-3.5">
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
                                    className="awo-brutal-btn flex h-8 w-8 items-center justify-center bg-[#FBBF24] text-[#121212]"
                                >
                                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" className="h-3.5 w-3.5">
                                        <path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1-.1z" />
                                    </svg>
                                </a>
                            )}
                            {site?.whatsapp_url && (
                                <a
                                    href={site.whatsapp_url}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    aria-label="WhatsApp"
                                    title={site.whatsapp ? `WhatsApp ${site.whatsapp}` : 'WhatsApp'}
                                    className="awo-brutal-btn flex h-8 w-8 items-center justify-center bg-[#FBBF24] text-[#121212]"
                                >
                                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" className="h-3.5 w-3.5">
                                        <path d="M12.03 2a9.82 9.82 0 0 0-8.43 14.8L2.25 22l5.33-1.4A9.84 9.84 0 1 0 12.03 2Zm5.72 13.94c-.24.68-1.4 1.3-1.92 1.38-.5.08-1.14.12-1.84-.1-.42-.14-.97-.31-1.68-.62-2.96-1.28-4.9-4.27-5.05-4.47-.15-.2-1.2-1.6-1.2-3.05 0-1.45.76-2.16 1.03-2.46.27-.3.6-.37.8-.37.2 0 .4 0 .58.01.18.01.43-.07.67.51.24.6.82 2.01.9 2.16.07.15.12.32.02.52-.1.2-.15.32-.3.5-.15.18-.31.4-.45.54-.15.15-.3.31-.13.6.17.3.76 1.25 1.63 2.02 1.12 1 2.07 1.31 2.36 1.46.3.15.47.12.64-.07.17-.2.74-.86.94-1.16.2-.3.4-.25.67-.15.27.1 1.72.81 2.02.96.3.15.5.22.57.34.08.12.08.7-.16 1.38Z" />
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
                                    className="awo-brutal-btn flex h-8 w-8 items-center justify-center bg-[#FBBF24] text-[#121212]"
                                >
                                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" className="h-3.5 w-3.5">
                                        <path d="M23.5 6.2a3 3 0 0 0-2.1-2.1C19.5 3.6 12 3.6 12 3.6s-7.5 0-9.4.5A3 3 0 0 0 .5 6.2 31.4 31.4 0 0 0 0 12a31.4 31.4 0 0 0 .5 5.8 3 3 0 0 0 2.1 2.1c1.9.5 9.4.5 9.4.5s7.5 0 9.4-.5a3 3 0 0 0 2.1-2.1A31.4 31.4 0 0 0 24 12a31.4 31.4 0 0 0-.5-5.8ZM9.6 15.6V8.4l6.3 3.6-6.3 3.6Z" />
                                    </svg>
                                </a>
                            )}
                        </div>

                        <Link
                            href={auth?.user ? '/dashboard' : '/login'}
                            className="awo-brutal-btn bg-[#FBBF24] px-3.5 py-1.5 font-mono text-[11px] font-black tracking-wider text-[#121212] uppercase"
                        >
                            {auth?.user ? 'Buka Aplikasi' : 'Masuk Anggota'}
                        </Link>

                        <button
                            type="button"
                            onClick={() => setOpen(!open)}
                            aria-expanded={open}
                            aria-controls="public-menu"
                            className="awo-brutal-btn bg-white px-2.5 py-1.5 font-mono text-[11px] font-black text-[#121212] uppercase lg:hidden"
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
                        className="border-t border-black/15 bg-[#F4F4F6] px-4 py-3 lg:hidden"
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
                                            'block px-3 py-2 font-mono text-xs font-extrabold tracking-wider uppercase transition-colors',
                                            isCurrent
                                                ? 'bg-[#FBBF24] text-[#121212]'
                                                : 'text-[#121212]/80 hover:bg-black/5 hover:text-[#121212]',
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

            {/* Frame Induk Penuh Ilustrasi dari Folder public/ */}
            <main className="relative flex-1 overflow-hidden">
                <img
                    src={artworkBg}
                    onError={(e) => {
                        if (site?.hero && e.currentTarget.src !== site.hero) {
                            e.currentTarget.src = site.hero;
                        } else {
                            e.currentTarget.style.display = 'none';
                        }
                    }}
                    alt=""
                    aria-hidden="true"
                    className="animate-awo-drift pointer-events-none absolute inset-0 z-0 h-full w-full scale-105 object-cover object-center"
                />

                <div className="relative z-10">
                    {flash?.success && (
                        <div role="status" className="mx-auto max-w-6xl px-4 pt-5 sm:px-6">
                            <p className="awo-brutal-box bg-[#FBBF24] px-4 py-3 font-mono text-xs font-black text-[#121212]">
                                {flash.success}
                            </p>
                        </div>
                    )}

                    {children}
                </div>
            </main>

            {/* Footer Induk */}
            <footer className="relative z-10 border-t-2 border-[#121212] bg-[#121212] text-white">
                <div className="mx-auto max-w-6xl px-4 py-12 sm:px-6 sm:py-14">
                    <div className="grid gap-10 lg:grid-cols-12">
                        {/* Kolom Kiri: Brand & Quick Email */}
                        <div className="space-y-4 lg:col-span-5">
                            <div className="inline-flex items-center gap-2 border border-[#FBBF24]/50 bg-[#FBBF24]/15 px-3 py-1 font-mono text-[10px] font-black tracking-widest text-[#FBBF24] uppercase">
                                <span className="h-1.5 w-1.5 bg-[#FBBF24]" />
                                <span>Arek Wetan Dispatch</span>
                            </div>

                            <h2 className="text-2xl font-black tracking-tight text-white uppercase">
                                {siteName}
                            </h2>

                            <p className="max-w-md font-mono text-xs leading-relaxed text-[#9CA3AF] sm:text-sm">
                                {site?.tagline ||
                                    'Ruang kolaborasi, event kreatif, arsip kegiatan, dan jejaring anak muda yang gerak bareng.'}
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
                                    className="w-full border border-white/20 bg-white/[0.06] px-3.5 py-2 font-mono text-xs font-medium text-white placeholder:text-[#7A8B8C] focus:border-[#FBBF24] focus:outline-none"
                                />
                                <button
                                    type="submit"
                                    className="awo-brutal-btn shrink-0 bg-[#FBBF24] px-4 py-2 font-mono text-xs font-black text-[#121212] uppercase"
                                >
                                    Join
                                </button>
                            </form>

                            {subscribed && (
                                <p className="font-mono text-xs font-bold text-[#FBBF24]">
                                    // Email kamu sudah tercatat untuk info update berikutnya.
                                </p>
                            )}
                        </div>

                        {/* Kolom Kanan: Eksplor, Arsip & Relasi, Media Sosial */}
                        <div className="grid grid-cols-2 gap-8 sm:grid-cols-3 lg:col-span-7">
                            <div>
                                <p className="font-mono text-xs font-black tracking-widest text-[#FBBF24] uppercase">
                                    Eksplor
                                </p>
                                <ul className="mt-3.5 space-y-2 text-sm text-[#9CA3AF]">
                                    <li><Link href="/tentang" className="transition hover:text-white">Tentang</Link></li>
                                    <li><Link href="/struktur-pengurus" className="transition hover:text-white">Struktur</Link></li>
                                    <li><Link href="/kegiatan" className="transition hover:text-white">Kegiatan</Link></li>
                                    <li><Link href="/berita" className="transition hover:text-white">Berita</Link></li>
                                </ul>
                            </div>

                            <div>
                                <p className="font-mono text-xs font-black tracking-widest text-[#FBBF24] uppercase">
                                    Arsip &amp; Relasi
                                </p>
                                <ul className="mt-3.5 space-y-2 text-sm text-[#9CA3AF]">
                                    <li><Link href="/galeri" className="transition hover:text-white">Galeri</Link></li>
                                    <li><Link href="/dokumen" className="transition hover:text-white">Dokumen</Link></li>
                                    <li><Link href="/mitra" className="transition hover:text-white">Mitra &amp; UMKM</Link></li>
                                    <li><Link href="/kontak" className="transition hover:text-white">Kontak</Link></li>
                                </ul>
                            </div>

                            <div className="col-span-2 sm:col-span-1">
                                <p className="font-mono text-xs font-black tracking-widest text-[#FBBF24] uppercase">
                                    Media Sosial
                                </p>
                                <div className="mt-4 flex flex-wrap items-center gap-2">
                                    {site?.whatsapp_url && (
                                        <a
                                            href={site.whatsapp_url}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            aria-label="WhatsApp"
                                            title={site.whatsapp ? `WhatsApp ${site.whatsapp}` : 'WhatsApp'}
                                            className="inline-flex h-9 w-9 items-center justify-center border border-white/15 bg-white/[0.04] text-[#9CA3AF] transition hover:border-[#FBBF24] hover:text-[#FBBF24]"
                                        >
                                            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" className="h-4 w-4">
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
                                            className="inline-flex h-9 w-9 items-center justify-center border border-white/15 bg-white/[0.04] text-[#9CA3AF] transition hover:border-[#FBBF24] hover:text-[#FBBF24]"
                                        >
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true" className="h-4 w-4">
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
                                            className="inline-flex h-9 w-9 items-center justify-center border border-white/15 bg-white/[0.04] text-[#9CA3AF] transition hover:border-[#FBBF24] hover:text-[#FBBF24]"
                                        >
                                            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" className="h-4 w-4">
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
                                            className="inline-flex h-9 w-9 items-center justify-center border border-white/15 bg-white/[0.04] text-[#9CA3AF] transition hover:border-[#FBBF24] hover:text-[#FBBF24]"
                                        >
                                            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" className="h-4 w-4">
                                                <path d="M23.5 6.2a3 3 0 0 0-2.1-2.1C19.5 3.6 12 3.6 12 3.6s-7.5 0-9.4.5A3 3 0 0 0 .5 6.2 31.4 31.4 0 0 0 0 12a31.4 31.4 0 0 0 .5 5.8 3 3 0 0 0 2.1 2.1c1.9.5 9.4.5 9.4.5s7.5 0 9.4-.5a3 3 0 0 0 2.1-2.1A31.4 31.4 0 0 0 24 12a31.4 31.4 0 0 0-.5-5.8ZM9.6 15.6V8.4l6.3 3.6-6.3 3.6Z" />
                                            </svg>
                                        </a>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Baris Info Kontak & Maps */}
                    {(site?.address || site?.email || site?.phone || site?.maps_url) && (
                        <div className="mt-10 flex flex-wrap items-center justify-between gap-x-6 gap-y-2 border-t border-white/10 pt-5 font-mono text-xs text-[#9CA3AF]">
                            <div className="flex flex-wrap items-center gap-3">
                                {site?.address && (
                                    site?.maps_url ? (
                                        <a
                                            href={site.maps_url}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="inline-flex items-center gap-1.5 transition hover:text-[#FBBF24]"
                                        >
                                            <span>{site.address}</span>
                                            <span className="font-bold text-[#FBBF24]">↗</span>
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
                                        className="inline-flex items-center gap-1 font-semibold text-[#FBBF24] hover:underline"
                                    >
                                        <span>Lihat Lokasi di Peta</span>
                                        <span>↗</span>
                                    </a>
                                )}
                            </div>
                            <div className="flex flex-wrap items-center gap-4 tabular-nums">
                                {site?.email && (
                                    <a href={`mailto:${site.email}`} className="transition hover:text-[#FBBF24]">
                                        {site.email}
                                    </a>
                                )}
                                {site?.phone && <span>{site.phone}</span>}
                            </div>
                        </div>
                    )}

                    {/* Bottom Bar */}
                    <div className="mt-6 flex flex-wrap items-center justify-between gap-4 border-t border-white/10 pt-6 font-mono text-xs text-[#7A8B8C]">
                        <p>© {new Date().getFullYear()} {siteName}. All rights reserved.</p>
                        <p className="font-semibold text-[#9CA3AF]">Built for young movers &amp; local energy.</p>
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
                        <div className="awo-brutal-box inline-flex items-center gap-2.5 bg-[#F4F4F6] px-4 py-2">
                            <span className="h-3 w-3 bg-[#FBBF24]" aria-hidden="true" />
                            <h2 className="font-mono text-lg font-black tracking-tight text-[#121212] uppercase sm:text-xl">{title}</h2>
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