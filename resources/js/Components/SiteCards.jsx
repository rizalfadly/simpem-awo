import { Link } from '@inertiajs/react';
import { Badge } from '@/Components/ui';
import { phaseLabel, phaseTone } from '@/Components/PublicLayout';

export function ActivityCard({ a }) {
    return (
        <Link
            href={`/kegiatan/${a.slug}`}
            className="group block rounded-2xl border border-[#DDE2E9] bg-white p-5 shadow-xs transition duration-200 hover:-translate-y-0.5 hover:border-[#36D8E8]/80 hover:shadow-md"
        >
            <div className="flex items-start justify-between gap-2">
                <h3 className="text-base font-bold leading-tight text-[#08090B] transition-colors group-hover:text-[#0891B2]">
                    {a.title}
                </h3>
                <Badge tone={phaseTone[a.phase]}>{phaseLabel[a.phase]}</Badge>
            </div>
            <p className="mt-2 text-sm tabular-nums text-[#5B6779]">{a.when}</p>
            {a.location && <p className="text-sm text-[#5B6779]">{a.location}</p>}
            {a.division && (
                <p className="mt-3 inline-flex items-center gap-1.5 text-xs font-semibold text-[#7A8B8C]">
                    <span className="h-1.5 w-1.5 rounded-full bg-[#36D8E8]" aria-hidden="true" />
                    <span>{a.division}</span>
                </p>
            )}
        </Link>
    );
}

export function NewsCard({ n }) {
    return (
        <Link
            href={`/berita/${n.slug}`}
            className="group block overflow-hidden rounded-2xl border border-[#DDE2E9] bg-white shadow-xs transition duration-200 hover:-translate-y-0.5 hover:border-[#36D8E8]/80 hover:shadow-md"
        >
            <div className="aspect-[16/9] overflow-hidden bg-[#0E1116]">
                {n.cover ? (
                    <img
                        src={n.cover}
                        alt=""
                        loading="lazy"
                        className="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                    />
                ) : (
                    <div className="flex h-full items-center justify-center bg-[#08090B] text-xs font-bold tracking-widest text-[#7A8B8C] uppercase">
                        No Cover
                    </div>
                )}
            </div>
            <div className="p-4">
                <h3 className="text-base font-bold leading-tight text-[#08090B] transition-colors group-hover:text-[#0891B2]">
                    {n.title}
                </h3>
                {n.excerpt && <p className="mt-1.5 line-clamp-2 text-sm text-[#3B4658]">{n.excerpt}</p>}
                <p className="mt-3 text-xs font-medium tabular-nums text-[#8A94A4]">{n.published_at}</p>
            </div>
        </Link>
    );
}

export function AlbumCard({ a }) {
    return (
        <Link
            href={`/galeri/${a.id}`}
            className="group block overflow-hidden rounded-2xl border border-[#DDE2E9] bg-white shadow-xs transition duration-200 hover:-translate-y-0.5 hover:border-[#36D8E8]/80 hover:shadow-md"
        >
            <div className="relative aspect-[4/3] overflow-hidden bg-[#0E1116]">
                {a.cover ? (
                    <img
                        src={a.cover}
                        alt=""
                        loading="lazy"
                        className="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                    />
                ) : (
                    <div className="flex h-full items-center justify-center text-sm text-[#8A94A4]">
                        Belum ada foto
                    </div>
                )}
            </div>
            <div className="p-4">
                <h3 className="text-sm font-bold leading-tight text-[#08090B] transition-colors group-hover:text-[#0891B2]">
                    {a.title}
                </h3>
                {a.date && <p className="mt-1 text-xs tabular-nums text-[#8A94A4]">{a.date}</p>}
            </div>
        </Link>
    );
}

export function PartnerCard({ p }) {
    return (
        <Link
            href={`/mitra/${p.slug}`}
            className="group flex gap-4 rounded-2xl border border-[#DDE2E9] bg-white p-4 shadow-xs transition duration-200 hover:-translate-y-0.5 hover:border-[#36D8E8]/80 hover:shadow-md"
        >
            {p.logo ? (
                <img
                    src={p.logo}
                    alt=""
                    loading="lazy"
                    className="h-16 w-16 shrink-0 rounded-xl border border-[#EEF1F5] object-cover"
                />
            ) : (
                <span className="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl border border-[#36D8E8]/30 bg-[#08090B] text-xl font-black text-[#36D8E8]">
                    {p.name.slice(0, 1).toUpperCase()}
                </span>
            )}
            <div className="min-w-0">
                <h3 className="text-sm font-bold leading-tight text-[#08090B] transition-colors group-hover:text-[#0891B2]">
                    {p.name}
                </h3>
                <p className="mt-0.5 text-xs text-[#5B6779]">
                    {p.type_label}
                    {p.sector ? ` · ${p.sector}` : ''}
                    {p.city ? ` · ${p.city}` : ''}
                </p>
                {p.blurb && <p className="mt-1.5 line-clamp-2 text-xs text-[#3B4658]">{p.blurb}</p>}
            </div>
        </Link>
    );
}

export function Empty({ children }) {
    return (
        <div className="rounded-2xl border border-dashed border-[#CBD2DC] bg-white px-6 py-12 text-center">
            <span className="mx-auto mb-2 block h-1.5 w-8 rounded-full bg-[#36D8E8]/60" aria-hidden="true" />
            <p className="text-sm font-medium text-[#5B6779]">{children}</p>
        </div>
    );
}