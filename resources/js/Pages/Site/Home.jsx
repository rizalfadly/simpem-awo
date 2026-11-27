import { Link } from '@inertiajs/react';
import PublicLayout, { Section } from '@/Components/PublicLayout';
import { ActivityCard, AlbumCard, NewsCard, PartnerCard } from '@/Components/SiteCards';

const more = (href, label) => (
    <Link
        href={href}
        className="inline-flex items-center gap-1.5 font-mono text-xs font-black tracking-wider text-[#111214] uppercase transition hover:text-[#FF5C00]"
    >
        <span>{label}</span>
        <span aria-hidden="true">→</span>
    </Link>
);

const vibes = [
    'AREK WETAN ORGANIZER',
    'KREATIF & KOLABORATIF',
    'RUANG EKSPRESI PEMUDA',
    'AKSI NYATA LAPANGAN',
    'KOMUNITAS & KARYA',
    'SOLIDARITAS TANPA BATAS',
];

/**
 * Grid adaptif: jumlah kolom mengikuti jumlah item, supaya tidak ada
 * satu kartu kecil yang "menggantung" di grid 3 kolom.
 *  - 1 item  : satu kartu di tengah dengan lebar terbatas
 *  - 2 item  : 2 kolom
 *  - 3+ item : sampai `max` kolom
 */
const COLS = {
    2: 'sm:grid-cols-2',
    3: 'md:grid-cols-3',
    4: 'sm:grid-cols-2 lg:grid-cols-4',
};
const layout = (count, max = 3, base = 'grid gap-5') => {
    if (count <= 1) return 'mx-auto w-full max-w-md';
    const cols = Math.min(count, max);
    return `${base} ${COLS[cols] ?? COLS[3]}`;
};

/** Satu blok konten: judul + tautan "Semua ..." berdampingan, latar selang-seling. */
function Band({ title, href, label, alt, children }) {
    return (
        <section className={alt ? 'border-y border-[#CBD2DC] bg-white' : 'border-b border-[#CBD2DC] bg-[#ECEEF2]'}>
            <div className="mx-auto max-w-6xl border-x border-[#CBD2DC] px-4 py-10 sm:px-6 sm:py-14">
                <div className="mb-6 flex flex-wrap items-baseline justify-between gap-x-5 gap-y-2 border-b border-[#CBD2DC] pb-3">
                    <div className="flex flex-wrap items-baseline gap-x-5 gap-y-1">
                        <h2 className="flex items-center gap-2.5 text-xl font-black tracking-tight text-[#111214] uppercase sm:text-2xl">
                            <span className="h-3 w-3 bg-[#FF5C00]" aria-hidden="true" />
                            <span>{title}</span>
                        </h2>
                        {more(href, label)}
                    </div>
                </div>
                {children}
            </div>
        </section>
    );
}

export default function Home({ site, stats, news, activities, albums, partners }) {
    const hasContent = activities.length + news.length + albums.length + partners.length > 0;
    const siteName = site?.name || 'Arek Wetan Organizer';

    // Hanya blok yang punya isi yang dirender; latar selang-seling mengikuti urutan tampil.
    const bands = [
        activities.length > 0 && {
            key: 'activities',
            title: 'Agenda & Kegiatan Mendatang',
            href: '/kegiatan',
            label: 'Semua kegiatan',
            node: (
                <div className={layout(activities.length, 3)}>
                    {activities.map((a) => (
                        <ActivityCard key={a.slug} a={a} />
                    ))}
                </div>
            ),
        },
        news.length > 0 && {
            key: 'news',
            title: 'Update & Cerita Terbaru',
            href: '/berita',
            label: 'Semua berita',
            node: (
                <div className={layout(news.length, 3)}>
                    {news.map((n) => (
                        <NewsCard key={n.slug} n={n} />
                    ))}
                </div>
            ),
        },
        albums.length > 0 && {
            key: 'albums',
            title: 'Dokumentasi & Galeri',
            href: '/galeri',
            label: 'Semua album',
            node: (
                <div className={layout(albums.length, 3, 'grid grid-cols-2 gap-4')}>
                    {albums.map((a) => (
                        <AlbumCard key={a.id} a={a} />
                    ))}
                </div>
            ),
        },
        partners.length > 0 && {
            key: 'partners',
            title: 'Jaringan Mitra & UMKM',
            href: '/mitra',
            label: 'Semua mitra',
            node: (
                <div className={layout(partners.length, 4, 'grid gap-4')}>
                    {partners.map((p) => (
                        <PartnerCard key={p.slug} p={p} />
                    ))}
                </div>
            ),
        },
    ].filter(Boolean);

    return (
        <PublicLayout>
            {/* Hero Brutalist Modular Grid Beraksen Safety Orange */}
            <section className="relative overflow-hidden border-b border-[#CBD2DC] bg-[#ECEEF2] text-[#111214]">
                <div className="awo-hero-grid pointer-events-none absolute inset-0 opacity-70" aria-hidden="true" />

                {site?.hero && (
                    <img
                        src={site.hero}
                        alt=""
                        className="pointer-events-none absolute inset-0 h-full w-full object-cover opacity-15 mix-blend-multiply"
                    />
                )}

                <div className="relative mx-auto max-w-6xl border-x border-[#CBD2DC]">
                    {/* Sel Atas: Headline Besar Edge-to-Edge */}
                    <div className="relative border-b border-[#CBD2DC] px-4 py-10 sm:px-6 sm:py-14">
                        <span className="pointer-events-none absolute left-3 top-3 h-2 w-2 bg-[#FF5C00]" aria-hidden="true" />
                        <span className="pointer-events-none absolute right-3 top-3 h-2 w-2 bg-[#FF5C00]" aria-hidden="true" />

                        <div className="inline-flex items-center gap-2 border border-[#111214] bg-white px-3 py-1 font-mono text-[11px] font-black tracking-widest text-[#111214] uppercase">
                            <span className="h-2 w-2 bg-[#FF5C00]" />
                            <span>Youth &amp; Creative Collective</span>
                        </div>

                        <h1 className="mt-5 max-w-5xl text-4xl font-black leading-[0.95] tracking-tighter text-[#111214] uppercase sm:text-6xl lg:text-7xl">
                            {siteName}
                        </h1>
                    </div>

                    {/* Sel Bawah: Tagline & CTA Berdampingan dalam Grid */}
                    <div className="grid divide-y divide-[#CBD2DC] lg:grid-cols-12 lg:divide-x lg:divide-y-0">
                        <div className="flex items-center px-4 py-6 sm:px-6 lg:col-span-7">
                            {site?.tagline ? (
                                <p className="max-w-2xl font-mono text-xs font-bold leading-relaxed text-[#374151] sm:text-sm">
                                    {site.tagline}
                                </p>
                            ) : (
                                <p className="max-w-2xl font-mono text-xs font-bold leading-relaxed text-[#374151] sm:text-sm">
                                    Ruang gerak, event kreatif, arsip kegiatan, dan kolaborasi anak muda yang bangun karya nyata.
                                </p>
                            )}
                        </div>

                        <div className="flex flex-wrap items-center justify-start gap-3 px-4 py-6 sm:px-6 lg:col-span-5 lg:justify-end">
                            <Link
                                href="/kegiatan"
                                className="awo-brutal-btn inline-flex items-center gap-2 bg-[#FF5C00] px-5 py-3 font-mono text-xs font-black tracking-wider text-white uppercase hover:bg-[#E04F00]"
                            >
                                <span>Jelajah Kegiatan</span>
                                <span aria-hidden="true">→</span>
                            </Link>
                            <Link
                                href="/kontak"
                                className="awo-brutal-btn inline-flex items-center gap-2 bg-white px-5 py-3 font-mono text-xs font-black tracking-wider text-[#111214] uppercase hover:bg-[#F9FAFB]"
                            >
                                <span>Gabung &amp; Kontak</span>
                            </Link>
                        </div>
                    </div>
                </div>
            </section>

            {/* Strip Marquee Energi Komunitas */}
            <div className="overflow-hidden border-b border-[#111214] bg-[#111214] py-2.5 text-[#FF5C00]">
                <div className="awo-marquee-track">
                    {[...vibes, ...vibes].map((item, idx) => (
                        <span
                            key={`${item}-${idx}`}
                            className="mx-6 inline-flex items-center gap-6 font-mono text-xs font-black tracking-[0.2em] uppercase"
                        >
                            <span>{item}</span>
                            <span className="h-1.5 w-1.5 rotate-45 bg-[#F4F4F6]" aria-hidden="true" />
                        </span>
                    ))}
                </div>
            </div>

            {/* Kartu Statistik Modular di Bawah Marquee */}
            <div className="border-b border-[#CBD2DC] bg-[#ECEEF2]">
                <div className="mx-auto grid max-w-6xl divide-y divide-[#CBD2DC] border-x border-[#CBD2DC] sm:grid-cols-3 sm:divide-x sm:divide-y-0">
                    {[
                        ['Anggota Aktif', stats?.members, 'Solid & bergerak', '/struktur-pengurus'],
                        ['Kegiatan Terlaksana', stats?.activities, 'Program & event', '/kegiatan'],
                        ['Mitra & UMKM', stats?.partners, 'Kolaborasi lokal', '/mitra'],
                    ].map(([label, value, note, href]) => (
                        <Link
                            key={label}
                            href={href}
                            className="group block bg-white/80 px-6 py-6 text-center transition hover:bg-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-[#FF5C00]"
                        >
                            <span className="block font-mono text-3xl font-black tabular-nums tracking-tight text-[#111214] transition group-hover:text-[#FF5C00] sm:text-4xl">
                                {value ?? 0}
                            </span>
                            <span className="mt-1.5 block font-mono text-xs font-black tracking-wider text-[#111214] uppercase">
                                {label}
                            </span>
                            <span className="mt-1 block font-mono text-xs text-[#4B5563]">{note}</span>
                        </Link>
                    ))}
                </div>
            </div>

            {/* Daftar Konten: latar selang-seling agar ada ritme antarbagian */}
            <div>
                {bands.map((b, i) => (
                    <Band key={b.key} title={b.title} href={b.href} label={b.label} alt={i % 2 === 1}>
                        {b.node}
                    </Band>
                ))}

                {!hasContent && (
                    <Section>
                        <div className="border border-dashed border-[#111214]/30 bg-white px-6 py-12 text-center">
                            <p className="font-mono text-sm font-black text-[#111214] uppercase">
                                Konten publik Arek Wetan Organizer sedang disiapkan.
                            </p>
                            <p className="mt-1 font-mono text-xs text-[#4B5563]">
                                Nantikan agenda, dokumentasi, dan kabar terbaru segera di halaman ini.
                            </p>
                        </div>
                    </Section>
                )}
            </div>
        </PublicLayout>
    );
}