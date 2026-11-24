import { router } from '@inertiajs/react';
import { useState } from 'react';
import PublicLayout, { Section } from '@/Components/PublicLayout';
import { Empty, NewsCard } from '@/Components/SiteCards';
import { Button, Input, Pagination } from '@/Components/ui';

export default function NewsList({ posts, q }) {
    const [term, setTerm] = useState(q ?? '');
    const items = posts?.data ?? [];

    return (
        <PublicLayout title="Berita">
            {/* Header Banner Gelap + Kolom Pencarian Beraksen Cyan */}
            <section className="relative overflow-hidden border-b border-white/10 bg-[#08090B] text-white">
                <div className="awo-hero-grid pointer-events-none absolute inset-0" aria-hidden="true" />
                <div
                    className="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[#36D8E8]/15 blur-3xl"
                    aria-hidden="true"
                />

                <div className="relative mx-auto max-w-6xl px-4 py-12 sm:px-6 sm:py-16">
                    <div className="inline-flex items-center gap-2 rounded-full border border-[#36D8E8]/35 bg-[#36D8E8]/10 px-3.5 py-1 text-[11px] font-bold tracking-widest text-[#36D8E8] uppercase">
                        <span className="h-2 w-2 rounded-full bg-[#36D8E8] shadow-[0_0_8px_#36D8E8]" />
                        <span>Kabar &amp; Publikasi</span>
                    </div>

                    <h1 className="mt-4 text-4xl font-black tracking-tight sm:text-6xl">Berita</h1>
                    <p className="mt-3 max-w-2xl text-sm font-medium leading-relaxed text-[#94A3B8] sm:text-base">
                        Informasi terbaru, catatan kegiatan, dan cerita seputar pergerakan Arek Wetan Organizer.
                    </p>

                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            router.get('/berita', term ? { q: term } : {}, { preserveState: true });
                        }}
                        className="mt-6 flex max-w-lg gap-2"
                    >
                        <Input
                            type="search"
                            value={term}
                            onChange={(e) => setTerm(e.target.value)}
                            placeholder="Cari berita..."
                            aria-label="Cari berita"
                            className="border-white/15 bg-white/[0.06] text-white placeholder:text-[#7A8B8C] focus:border-[#36D8E8] focus:ring-[#36D8E8]"
                        />
                        <Button
                            type="submit"
                            className="shrink-0 bg-[#36D8E8] font-extrabold text-[#08090B] shadow-[0_0_18px_rgba(54,216,232,0.28)] hover:bg-[#67E8F9]"
                        >
                            Cari
                        </Button>
                    </form>
                </div>
            </section>

            {/* Daftar Berita */}
            <Section className="py-10">
                <div className="mb-5 flex items-center justify-between gap-4">
                    <div className="flex items-center gap-3">
                        <span className="h-6 w-1 rounded-full bg-[#36D8E8]" aria-hidden="true" />
                        <h2 className="text-lg font-extrabold tracking-tight text-[#08090B]">
                            {term ? `Hasil pencarian: "${term}"` : 'Semua berita'}
                        </h2>
                    </div>
                    {typeof posts?.total === 'number' && (
                        <span className="rounded-full border border-[#DDE2E9] bg-white px-3 py-0.5 text-xs font-bold tabular-nums text-[#0891B2]">
                            {posts.total} berita
                        </span>
                    )}
                </div>

                {items.length === 0 ? (
                    <div className="rounded-2xl border border-[#DDE2E9] bg-white p-2 shadow-sm">
                        <Empty>{term ? 'Tidak ada berita yang cocok dengan pencarian.' : 'Belum ada berita.'}</Empty>
                    </div>
                ) : (
                    <div className="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                        {items.map((n) => (
                            <NewsCard key={n.slug} n={n} />
                        ))}
                    </div>
                )}

                {posts?.links && (
                    <div className="mt-8">
                        <Pagination links={posts.links} />
                    </div>
                )}
            </Section>
        </PublicLayout>
    );
}