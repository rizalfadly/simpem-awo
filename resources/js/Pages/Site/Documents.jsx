import { Link } from '@inertiajs/react';
import PublicLayout, { Section } from '@/Components/PublicLayout';
import { Empty } from '@/Components/SiteCards';
import { Badge, Pagination, cx } from '@/Components/ui';

export default function Documents({ documents, categories, category }) {
    const items = documents?.data ?? [];
    const activeCategory = category ?? '';

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
        <PublicLayout title="Dokumen">
            {/* Header Banner Gelap + Filter Kategori Beraksen Cyan */}
            <section className="relative overflow-hidden border-b border-white/10 bg-[#08090B] text-white">
                <div className="awo-hero-grid pointer-events-none absolute inset-0" aria-hidden="true" />
                <div
                    className="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[#36D8E8]/15 blur-3xl"
                    aria-hidden="true"
                />

                <div className="relative mx-auto max-w-6xl px-4 py-12 sm:px-6 sm:py-16">
                    <div className="inline-flex items-center gap-2 rounded-full border border-[#36D8E8]/35 bg-[#36D8E8]/10 px-3.5 py-1 text-[11px] font-bold tracking-widest text-[#36D8E8] uppercase">
                        <span className="h-2 w-2 rounded-full bg-[#36D8E8] shadow-[0_0_8px_#36D8E8]" />
                        <span>Arsip &amp; Publikasi Resmi</span>
                    </div>

                    <h1 className="mt-4 text-4xl font-black tracking-tight sm:text-6xl">Dokumen publik</h1>
                    <p className="mt-3 max-w-2xl text-sm font-medium leading-relaxed text-[#94A3B8] sm:text-base">
                        Kumpulan dokumen, regulasi, panduan, dan berkas publik Arek Wetan Organizer yang dapat diakses dan diunduh.
                    </p>

                    <div className="mt-6 flex flex-wrap gap-2" role="navigation" aria-label="Filter kategori dokumen">
                        {tab('/dokumen', 'Semua', activeCategory === '')}
                        {Object.entries(categories ?? {}).map(([k, v]) =>
                            tab(`/dokumen?category=${k}`, v, activeCategory === String(k)),
                        )}
                    </div>
                </div>
            </section>

            {/* Daftar Dokumen */}
            <Section className="py-10">
                <div className="mb-5 flex items-center justify-between gap-4">
                    <div className="flex items-center gap-3">
                        <span className="h-6 w-1 rounded-full bg-[#36D8E8]" aria-hidden="true" />
                        <h2 className="text-lg font-extrabold tracking-tight text-[#08090B]">
                            {activeCategory
                                ? `Kategori: ${categories?.[activeCategory] ?? activeCategory}`
                                : 'Semua Dokumen'}
                        </h2>
                    </div>
                    {typeof documents?.total === 'number' && (
                        <span className="rounded-full border border-[#DDE2E9] bg-white px-3 py-0.5 text-xs font-bold tabular-nums text-[#0891B2]">
                            {documents.total} berkas
                        </span>
                    )}
                </div>

                {items.length === 0 ? (
                    <div className="rounded-2xl border border-[#DDE2E9] bg-white p-2 shadow-sm">
                        <Empty>Belum ada dokumen publik.</Empty>
                    </div>
                ) : (
                    <ul className="divide-y divide-[#EEF1F5] overflow-hidden rounded-2xl border border-[#DDE2E9] bg-white shadow-sm">
                        {items.map((d) => (
                            <li
                                key={d.id}
                                className="group flex flex-wrap items-center justify-between gap-4 border-l-[3px] border-transparent px-5 py-4 transition-all hover:border-[#36D8E8] hover:bg-[#F7FAFC]"
                            >
                                <div className="min-w-0 flex-1">
                                    <p className="text-sm font-extrabold text-[#08090B] transition-colors group-hover:text-[#0891B2] sm:text-base">
                                        {d.title}
                                    </p>
                                    <p className="mt-0.5 text-xs font-medium tabular-nums text-[#5B6779]">
                                        {d.number ? `${d.number} · ` : ''}
                                        {d.date ? `${d.date} · ` : ''}
                                        {d.size}
                                    </p>
                                    {d.description && (
                                        <p className="mt-1.5 text-xs leading-relaxed text-[#3B4658] sm:text-sm">
                                            {d.description}
                                        </p>
                                    )}
                                </div>

                                <div className="flex shrink-0 items-center gap-3">
                                    <Badge>{d.category}</Badge>
                                    <a
                                        href={`/dokumen/${d.id}/unduh`}
                                        className="rounded-xl bg-[#08090B] px-4 py-2 text-xs font-extrabold text-[#36D8E8] ring-1 ring-[#36D8E8]/35 transition hover:bg-[#12161F] hover:ring-[#36D8E8] sm:text-sm"
                                    >
                                        Unduh
                                    </a>
                                </div>
                            </li>
                        ))}
                    </ul>
                )}

                {documents?.links && (
                    <div className="mt-8">
                        <Pagination links={documents.links} />
                    </div>
                )}
            </Section>
        </PublicLayout>
    );
}