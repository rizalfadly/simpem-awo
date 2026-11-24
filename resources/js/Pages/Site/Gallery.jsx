import PublicLayout, { Section } from '@/Components/PublicLayout';
import { AlbumCard, Empty } from '@/Components/SiteCards';
import { Pagination } from '@/Components/ui';

export default function Gallery({ albums }) {
    const items = albums?.data ?? [];

    return (
        <PublicLayout title="Galeri">
            {/* Header Banner Gelap Beraksen Cyan */}
            <section className="relative overflow-hidden border-b border-white/10 bg-[#08090B] text-white">
                <div className="awo-hero-grid pointer-events-none absolute inset-0" aria-hidden="true" />
                <div
                    className="pointer-events-none absolute -right-24 -top-24 h-80 w-80 rounded-full bg-[#36D8E8]/15 blur-3xl"
                    aria-hidden="true"
                />
                <div
                    className="pointer-events-none absolute -bottom-24 left-12 h-72 w-72 rounded-full bg-[#0284C7]/15 blur-3xl"
                    aria-hidden="true"
                />

                <div className="relative mx-auto flex max-w-6xl flex-wrap items-end justify-between gap-6 px-4 py-12 sm:px-6 sm:py-16">
                    <div>
                        <div className="inline-flex items-center gap-2 rounded-full border border-[#36D8E8]/35 bg-[#36D8E8]/10 px-3.5 py-1 text-[11px] font-bold tracking-widest text-[#36D8E8] uppercase">
                            <span className="h-2 w-2 animate-pulse rounded-full bg-[#36D8E8] shadow-[0_0_8px_#36D8E8]" />
                            <span>Visual Archive &amp; Momen</span>
                        </div>
                        <h1 className="mt-4 text-4xl font-black tracking-tight sm:text-6xl">Galeri</h1>
                        <p className="mt-3 max-w-2xl text-sm font-medium leading-relaxed text-[#94A3B8] sm:text-base">
                            Kumpulan album dokumentasi dan momen kegiatan Arek Wetan Organizer.
                        </p>
                    </div>

                    {items.length > 0 && (
                        <div className="flex items-center gap-2 rounded-xl border border-white/10 bg-white/[0.04] px-4 py-2.5 text-xs">
                            <span className="h-2 w-2 rounded-full bg-[#36D8E8]" />
                            <span className="font-semibold text-[#94A3B8]">Total Album:</span>
                            <span className="font-black tabular-nums text-white">
                                {typeof albums?.total === 'number' ? albums.total : items.length}
                            </span>
                        </div>
                    )}
                </div>
            </section>

            <Section className="py-10">
                <div className="mb-5 flex items-center gap-3">
                    <span className="h-6 w-1 rounded-full bg-[#36D8E8]" aria-hidden="true" />
                    <h2 className="text-xl font-extrabold tracking-tight text-[#08090B]">
                        Daftar Album
                    </h2>
                </div>

                <div className="mt-6">
                    {items.length === 0 ? (
                        <Empty>Belum ada album yang dipublikasikan.</Empty>
                    ) : (
                        <div className="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
                            {items.map((a) => (
                                <AlbumCard key={a.id} a={a} />
                            ))}
                        </div>
                    )}
                </div>

                {albums?.links && (
                    <div className="mt-6">
                        <Pagination links={albums.links} />
                    </div>
                )}
            </Section>
        </PublicLayout>
    );
}