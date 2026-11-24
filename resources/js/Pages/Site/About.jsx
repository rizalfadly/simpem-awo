import PublicLayout, { Section } from '@/Components/PublicLayout';

function Block({ title, text, accent = 'cyan' }) {
    if (!text) return null;

    const accentClass = accent === 'cyan' ? 'bg-[#36D8E8]' : 'bg-[#7A8B8C]';

    return (
        <article className="rounded-2xl border border-[#DDE2E9] bg-white p-6 shadow-sm sm:p-7">
            <div className="flex items-center gap-3">
                <span className={`h-5 w-1 rounded-full ${accentClass}`} aria-hidden="true" />
                <h2 className="text-xl font-extrabold tracking-tight text-[#08090B]">{title}</h2>
            </div>
            <p className="mt-3.5 whitespace-pre-line text-base leading-relaxed text-[#3B4658]">{text}</p>
        </article>
    );
}

export default function About({ profile, site }) {
    const empty = !profile.about && !profile.vision && !profile.mission && !profile.history;

    return (
        <PublicLayout title="Tentang">
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
                        <span>Profil Organisasi</span>
                    </div>
                    <h1 className="mt-4 text-4xl font-black tracking-tight sm:text-6xl">Tentang {site.name}</h1>
                    <p className="mt-3 max-w-2xl text-sm font-medium leading-relaxed text-[#94A3B8] sm:text-base">
                        Mengenal lebih dekat arah gerak, nilai bersama, visi misi, serta perjalanan Arek Wetan Organizer.
                    </p>
                </div>
            </section>

            {/* Isi Profil Organisasi */}
            <Section className="py-10">
                {empty ? (
                    <div className="rounded-2xl border border-[#DDE2E9] bg-white px-6 py-12 text-center shadow-sm">
                        <div className="mx-auto inline-flex items-center gap-2 rounded-full border border-cyan-500/25 bg-cyan-500/10 px-3 py-1 text-[11px] font-bold tracking-wider text-cyan-800 uppercase">
                            <span className="h-1.5 w-1.5 rounded-full bg-[#36D8E8]" />
                            <span>Segera Diperbarui</span>
                        </div>
                        <p className="mt-3 text-sm font-medium text-[#5B6779]">Profil organisasi sedang disiapkan.</p>
                    </div>
                ) : (
                    <div className="max-w-4xl space-y-6">
                        <Block title="Profil" text={profile.about} accent="cyan" />
                        <Block title="Visi" text={profile.vision} accent="cyan" />
                        <Block title="Misi" text={profile.mission} accent="steel" />
                        <Block title="Sejarah" text={profile.history} accent="steel" />
                    </div>
                )}
            </Section>
        </PublicLayout>
    );
}