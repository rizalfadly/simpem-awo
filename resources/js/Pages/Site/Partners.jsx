import { Link } from '@inertiajs/react';
import PublicLayout, { Section } from '@/Components/PublicLayout';
import { Empty, PartnerCard } from '@/Components/SiteCards';
import { Pagination, cx } from '@/Components/ui';

export default function Partners({ partners, types, type }) {
    const items = partners?.data ?? [];
    const activeType = type ?? '';

    const tab = (href, label, on) => (
        <Link
            key={label}
            href={href}
            aria-current={on ? 'page' : undefined}
            className={cx(
                'rounded-full border px-3.5 py-1.5 text-xs font-extrabold tracking-wide transition sm:text-sm',
                on
                    ? 'border-[#36D8E8] bg-[#36D8E8] text-[#08090B] shadow-[0_0_16px_rgba(54,216,232,0.3)]'
                    : 'border-white/15 bg-white/[0.04] text-[#94A3B8] hover:border-[#36D8E8]/50 hover:bg-white/[0.08] hover:text-white',
            )}
        >
            {label}
        </Link>
    );

    return (
        <PublicLayout title="Mitra & UMKM">
            {/* Header Banner Gelap + Tab Filter Tipe Mitra */}
            <section className="relative overflow-hidden border-b border-white/10 bg-[#08090B] text-white">
                <div className="awo-hero-grid pointer-events-none absolute inset-0" aria-hidden="true" />
                <div
                    className="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[#36D8E8]/15 blur-3xl"
                    aria-hidden="true"
                />

                <div className="relative mx-auto max-w-6xl px-4 py-12 sm:px-6 sm:py-16">
                    <div className="inline-flex items-center gap-2 rounded-full border border-[#36D8E8]/35 bg-[#36D8E8]/10 px-3.5 py-1 text-[11px] font-bold tracking-widest text-[#36D8E8] uppercase">
                        <span className="h-2 w-2 rounded-full bg-[#36D8E8] shadow-[0_0_8px_#36D8E8]" />
                        <span>Kolaborasi &amp; Ekosistem</span>
                    </div>

                    <h1 className="mt-4 text-4xl font-black tracking-tight sm:text-6xl">Mitra &amp; UMKM</h1>
                    <p className="mt-3 max-w-2xl text-sm font-medium leading-relaxed text-[#94A3B8] sm:text-base">
                        UMKM binaan, sponsor, dan lembaga yang bekerja sama dengan Arek Wetan Organizer.
                    </p>

                    <div className="mt-6 flex flex-wrap gap-2" role="navigation" aria-label="Filter tipe mitra">
                        {tab('/mitra', 'Semua', activeType === '')}
                        {Object.entries(types ?? {}).map(([k, v]) =>
                            tab(`/mitra?type=${k}`, v, activeType === String(k)),
                        )}
                    </div>
                </div>
            </section>

            {/* Grid Daftar Mitra */}
            <Section className="py-10">
                <div className="mb-5 flex items-center justify-between gap-4">
                    <div className="flex items-center gap-3">
                        <span className="h-6 w-1 rounded-full bg-[#36D8E8]" aria-hidden="true" />
                        <h2 className="text-lg font-extrabold tracking-tight text-[#08090B]">
                            {activeType ? `Kategori: ${types?.[activeType] ?? activeType}` : 'Semua Mitra & UMKM'}
                        </h2>
                    </div>
                    {typeof partners?.total === 'number' && (
                        <span className="rounded-full border border-[#DDE2E9] bg-white px-3 py-0.5 text-xs font-bold tabular-nums text-[#0891B2]">
                            {partners.total} mitra
                        </span>
                    )}
                </div>

                {items.length === 0 ? (
                    <div className="rounded-2xl border border-[#DDE2E9] bg-white p-2 shadow-sm">
                        <Empty>Belum ada mitra yang ditampilkan.</Empty>
                    </div>
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {items.map((p) => (
                            <PartnerCard key={p.slug} p={p} />
                        ))}
                    </div>
                )}

                {partners?.links && (
                    <div className="mt-8">
                        <Pagination links={partners.links} />
                    </div>
                )}
            </Section>
        </PublicLayout>
    );
}