import PublicLayout, { Section } from '@/Components/PublicLayout';
import { ActivityCard, Empty } from '@/Components/SiteCards';
import { Pagination } from '@/Components/ui';

export default function Activities({ upcoming, past }) {
    const upcomingList = upcoming ?? [];
    const pastItems = past?.data ?? [];

    return (
        <PublicLayout title="Kegiatan">
            {/* Header Banner Gelap Beraksen Cyan */}
            <section className="relative overflow-hidden border-b border-white/10 bg-[#08090B] text-white">
                <div className="awo-hero-grid pointer-events-none absolute inset-0" aria-hidden="true" />
                <div
                    className="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[#36D8E8]/15 blur-3xl"
                    aria-hidden="true"
                />
                <div
                    className="pointer-events-none absolute -bottom-28 left-12 h-64 w-64 rounded-full bg-[#0284C7]/15 blur-3xl"
                    aria-hidden="true"
                />

                <div className="relative mx-auto max-w-6xl px-4 py-12 sm:px-6 sm:py-16">
                    <div className="inline-flex items-center gap-2 rounded-full border border-[#36D8E8]/35 bg-[#36D8E8]/10 px-3.5 py-1 text-[11px] font-bold tracking-widest text-[#36D8E8] uppercase">
                        <span className="h-2 w-2 rounded-full bg-[#36D8E8] shadow-[0_0_8px_#36D8E8]" />
                        <span>Agenda &amp; Rekam Jejak</span>
                    </div>
                    <h1 className="mt-4 text-4xl font-black tracking-tight sm:text-6xl">Kegiatan</h1>
                    <p className="mt-3 max-w-2xl text-sm font-medium leading-relaxed text-[#94A3B8] sm:text-base">
                        Rangkaian program, agenda mendatang, dan dokumentasi kegiatan yang sudah terlaksana bersama Arek Wetan Organizer.
                    </p>
                </div>
            </section>

            {/* Daftar Kegiatan */}
            <Section className="space-y-12 py-10">
                <div>
                    <div className="mb-5 flex items-center justify-between gap-4">
                        <div className="flex items-center gap-3">
                            <span className="h-6 w-1 rounded-full bg-[#36D8E8]" aria-hidden="true" />
                            <h2 className="text-xl font-extrabold tracking-tight text-[#08090B]">
                                Akan datang &amp; berlangsung
                            </h2>
                        </div>
                        <span className="rounded-full border border-[#DDE2E9] bg-white px-3 py-0.5 text-xs font-bold tabular-nums text-[#0891B2]">
                            {upcomingList.length} agenda
                        </span>
                    </div>

                    {upcomingList.length === 0 ? (
                        <div className="rounded-2xl border border-[#DDE2E9] bg-white p-2 shadow-sm">
                            <Empty>Belum ada kegiatan yang dijadwalkan.</Empty>
                        </div>
                    ) : (
                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            {upcomingList.map((a) => (
                                <ActivityCard key={a.slug} a={a} />
                            ))}
                        </div>
                    )}
                </div>

                <div>
                    <div className="mb-5 flex items-center justify-between gap-4">
                        <div className="flex items-center gap-3">
                            <span className="h-6 w-1 rounded-full bg-[#7A8B8C]" aria-hidden="true" />
                            <h2 className="text-xl font-extrabold tracking-tight text-[#08090B]">
                                Kegiatan terlaksana
                            </h2>
                        </div>
                        <span className="rounded-full border border-[#DDE2E9] bg-white px-3 py-0.5 text-xs font-bold tabular-nums text-[#5B6779]">
                            {pastItems.length} arsip
                        </span>
                    </div>

                    {pastItems.length === 0 ? (
                        <div className="rounded-2xl border border-[#DDE2E9] bg-white p-2 shadow-sm">
                            <Empty>Belum ada kegiatan yang dipublikasikan.</Empty>
                        </div>
                    ) : (
                        <>
                            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                                {pastItems.map((a) => (
                                    <ActivityCard key={a.slug} a={a} />
                                ))}
                            </div>
                            <div className="mt-6">
                                <Pagination links={past.links} />
                            </div>
                        </>
                    )}
                </div>
            </Section>
        </PublicLayout>
    );
}