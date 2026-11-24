import { Link } from '@inertiajs/react';
import PublicLayout, { Section, phaseLabel, phaseTone } from '@/Components/PublicLayout';
import { AlbumCard, NewsCard } from '@/Components/SiteCards';
import { Badge } from '@/Components/ui';

export default function Activity({ activity: a, albums, news }) {
    return (
        <PublicLayout title={a.title}>
            <Section>
                <Link href="/kegiatan" className="text-sm font-semibold text-[#5B6779] hover:underline">
                    Kegiatan
                </Link>
                <div className="mt-2 flex flex-wrap items-start justify-between gap-3">
                    <h1 className="max-w-3xl text-4xl font-extrabold leading-tight tracking-tight">{a.title}</h1>
                    <Badge tone={phaseTone[a.phase]}>{phaseLabel[a.phase]}</Badge>
                </div>
                <dl className="mt-6 grid max-w-2xl gap-4 sm:grid-cols-2">
                    <div>
                        <dt className="text-xs text-[#5B6779]">Waktu</dt>
                        <dd className="text-sm font-semibold tabular-nums">{a.when}</dd>
                    </div>
                    {a.location && (
                        <div>
                            <dt className="text-xs text-[#5B6779]">Lokasi</dt>
                            <dd className="text-sm font-semibold">{a.location}</dd>
                        </div>
                    )}
                    {a.division && (
                        <div>
                            <dt className="text-xs text-[#5B6779]">Penyelenggara</dt>
                            <dd className="text-sm font-semibold">{a.division}</dd>
                        </div>
                    )}
                    {a.category && (
                        <div>
                            <dt className="text-xs text-[#5B6779]">Kategori</dt>
                            <dd className="text-sm font-semibold">{a.category}</dd>
                        </div>
                    )}
                </dl>
                <p className="mt-8 max-w-3xl whitespace-pre-line text-base leading-relaxed text-[#3B4658]">{a.summary}</p>
            </Section>

            {albums.length > 0 && (
                <Section title="Dokumentasi">
                    <div className="grid grid-cols-2 gap-4 md:grid-cols-3">
                        {albums.map((x) => (
                            <AlbumCard key={x.id} a={x} />
                        ))}
                    </div>
                </Section>
            )}
            {news.length > 0 && (
                <Section title="Berita kegiatan">
                    <div className="grid gap-5 md:grid-cols-3">
                        {news.map((n) => (
                            <NewsCard key={n.slug} n={n} />
                        ))}
                    </div>
                </Section>
            )}
        </PublicLayout>
    );
}
