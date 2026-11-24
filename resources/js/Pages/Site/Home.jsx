import { Link } from '@inertiajs/react';
import PublicLayout, { Section } from '@/Components/PublicLayout';
import { ActivityCard, AlbumCard, NewsCard, PartnerCard } from '@/Components/SiteCards';

const more = (href, label) => (
    <Link
        href={href}
        className="inline-flex items-center gap-1.5 text-sm font-bold tracking-wide text-[#0891B2] transition hover:text-[#36D8E8]"
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
        <section className={alt ? 'border-y border-[#E3E7EE] bg-white' : ''}>
            <div className="mx-auto max-w-6xl px-4 py-10 sm:px-6 sm:py-14">
                <div className="mb-6 flex flex-wrap items-baseline gap-x-5 gap-y-1">
                    <h2 className="border-l-4 border-[#36D8E8] pl-3 text-2xl font-extrabold tracking-tight text-[#08090B] sm:text-3xl">
                        {title}
                    </h2>
                    {more(href, label)}
                </div>
                {children}
            </div>
        </section>
    );
}

export default function Home({ site, stats, news, activities, albums, partners }) {
    const hasContent = activities.length + news.length + albums.length + partners.length > 0;

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
            {/* Hero Gelap Beraksen Cyan & Grid Teknologi */}
            <section className="relative overflow-hidden bg-[#08090B] text-white">
                <div className="awo-hero-grid pointer-events-none absolute inset-0" aria-hidden="true" />
                <div
                    className="pointer-events-none absolute -top-24 right-1/4 h-80 w-80 rounded-full bg-[#36D8E8]/15 blur-3xl"
                    aria-hidden="true"
                />
                <div
                    className="pointer-events-none absolute -bottom-28 left-10 h-72 w-72 rounded-full bg-[#0284C7]/15 blur-3xl"
                    aria-hidden="true"
                />

                {site.hero && (
                    <img
                        src={site.hero}
                        alt=""
                        className="pointer-events-none absolute inset-0 h-full w-full object-cover opacity-20 mix-blend-luminosity"
                    />
                )}

                <div className="relative mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-24">
                    <div className="inline-flex items-center gap-2 rounded-full border border-[#36D8E8]/35 bg-[#36D8E8]/10 px-3.5 py-1 text-xs font-bold tracking-widest text-[#36D8E8] uppercase">
                        <span className="h-2 w-2 rounded-full bg-[#36D8E8] shadow-[0_0_8px_#36D8E8]" />
                        <span>Youth &amp; Creative Collective</span>
                    </div>

                    <h1 className="mt-5 max-w-4xl text-5xl font-black leading-[0.96] tracking-tight sm:text-7xl lg:text-8xl">
                        {site.name}
                    </h1>

                    {site.tagline && (
                        <p className="mt-6 max-w-2xl text-base font-medium leading-relaxed text-[#94A3B8] sm:text-xl">
                            {site.tagline}
                        </p>
                    )}

                    <div className="mt-9 flex flex-wrap items-center gap-3.5">
                        <Link
                            href="/kegiatan"
                            className="rounded-xl bg-[#36D8E8] px-6 py-3 text-sm font-extrabold text-[#08090B] shadow-[0_0_24px_rgba(54,216,232,0.35)] transition hover:bg-[#67E8F9] active:scale-[0.98]"
                        >
                            Jelajah Kegiatan
                        </Link>
                        <Link
                            href="/kontak"
                            className="rounded-xl border border-white/15 bg-white/[0.04] px-6 py-3 text-sm font-bold text-white transition hover:border-[#36D8E8]/50 hover:bg-white/[0.08]"
                        >
                            Gabung &amp; Kontak
                        </Link>
                    </div>
                </div>
            </section>

            {/* Strip Marquee Energi Komunitas */}
            <div className="overflow-hidden border-y border-white/10 bg-[#0E1116] py-2.5 text-[#36D8E8]">
                <div className="awo-marquee-track">
                    {[...vibes, ...vibes].map((item, idx) => (
                        <span
                            key={`${item}-${idx}`}
                            className="mx-6 inline-flex items-center gap-6 text-xs font-extrabold tracking-[0.2em] uppercase"
                        >
                            <span>{item}</span>
                            <span className="h-1.5 w-1.5 rotate-45 bg-[#7A8B8C]" aria-hidden="true" />
                        </span>
                    ))}
                </div>
            </div>

            {/* Kartu Statistik Duduk Bersih di Bawah Marquee */}
            <div className="mx-auto mt-8 grid max-w-4xl gap-4 px-4 sm:grid-cols-3 sm:px-6">
                {[
                    ['Anggota Aktif', stats.members, 'Solid & bergerak', '/struktur-pengurus'],
                    ['Kegiatan Terlaksana', stats.activities, 'Program & event', '/kegiatan'],
                    ['Mitra & UMKM', stats.partners, 'Kolaborasi lokal', '/mitra'],
                ].map(([label, value, note, href]) => (
                    <Link
                        key={label}
                        href={href}
                        className="group block rounded-2xl border border-[#DDE2E9] bg-white px-6 py-5 text-center shadow-sm transition hover:-translate-y-0.5 hover:border-[#36D8E8]/60 hover:shadow-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0891B2] active:scale-[0.98]"
                    >
                        <span className="block text-3xl font-black tabular-nums tracking-tight text-[#08090B] sm:text-4xl">
                            {value}
                        </span>
                        <span className="mt-1 block text-xs font-bold tracking-wider text-[#0891B2] uppercase">{label}</span>
                        <span className="mt-1 block text-xs text-[#5B6779]">{note}</span>
                    </Link>
                ))}
            </div>

            {/* Daftar Konten: latar selang-seling (abu muda / putih) agar ada ritme antarbagian */}
            <div className="mt-6">
                {bands.map((b, i) => (
                    <Band key={b.key} title={b.title} href={b.href} label={b.label} alt={i % 2 === 1}>
                        {b.node}
                    </Band>
                ))}

                {!hasContent && (
                    <Section>
                        <div className="rounded-2xl border border-dashed border-[#CBD2DC] bg-white px-6 py-12 text-center">
                            <p className="text-sm font-semibold text-[#08090B]">
                                Konten publik Arek Wetan Organizer sedang disiapkan.
                            </p>
                            <p className="mt-1 text-xs text-[#5B6779]">
                                Nantikan agenda, dokumentasi, dan kabar terbaru segera di halaman ini.
                            </p>
                        </div>
                    </Section>
                )}
            </div>
        </PublicLayout>
    );
}