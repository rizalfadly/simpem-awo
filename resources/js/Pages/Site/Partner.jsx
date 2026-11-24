import { Link } from '@inertiajs/react';
import PublicLayout, { Section } from '@/Components/PublicLayout';
import { Badge, Card } from '@/Components/ui';
import { rupiah } from '@/lib/format';

export default function Partner({ partner: p, products = [], promotions = [] }) {
    return (
        <PublicLayout title={p.name}>
            {/* Header Banner Gelap Beraksen Cyan untuk Profil Mitra & UMKM */}
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

                <div className="relative mx-auto max-w-6xl px-4 py-10 sm:px-6 sm:py-14">
                    <Link
                        href="/mitra"
                        className="inline-flex items-center gap-1.5 rounded-full border border-[#36D8E8]/35 bg-[#36D8E8]/10 px-3.5 py-1 text-xs font-bold tracking-wider text-[#36D8E8] uppercase transition hover:bg-[#36D8E8]/20"
                    >
                        <span aria-hidden="true">←</span>
                        <span>Mitra &amp; UMKM</span>
                    </Link>

                    <div className="mt-5 flex flex-col gap-5 sm:flex-row sm:items-center">
                        {p.logo_full ? (
                            <img
                                src={p.logo_full}
                                alt=""
                                className="h-24 w-24 shrink-0 rounded-2xl border border-white/15 bg-white object-cover shadow-md"
                            />
                        ) : (
                            <span className="flex h-24 w-24 shrink-0 items-center justify-center rounded-2xl border border-[#36D8E8]/40 bg-[#36D8E8]/10 text-4xl font-black text-[#36D8E8]">
                                {p.name.slice(0, 1).toUpperCase()}
                            </span>
                        )}

                        <div className="min-w-0">
                            <h1 className="text-3xl font-black leading-tight tracking-tight text-white sm:text-5xl">
                                {p.name}
                            </h1>
                            <div className="mt-2.5 flex flex-wrap gap-1.5">
                                <Badge>{p.type_label}</Badge>
                                {p.sector && <Badge tone="neutral">{p.sector}</Badge>}
                                {p.city && <Badge tone="neutral">{p.city}</Badge>}
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* Detail Informasi Mitra */}
            <Section className="py-10">
                <div className="rounded-2xl border border-[#DDE2E9] bg-white p-6 shadow-sm sm:p-8">
                    {p.description && (
                        <p className="max-w-3xl whitespace-pre-line text-base leading-relaxed text-[#3B4658]">
                            {p.description}
                        </p>
                    )}

                    {(p.owner_name || p.public_contact || p.website || p.instagram) && (
                        <dl className="mt-6 grid max-w-3xl gap-4 border-t border-[#EEF1F5] pt-6 sm:grid-cols-2">
                            {p.owner_name && (
                                <div className="rounded-xl border border-[#EEF1F5] bg-[#FAFBFD] px-4 py-3">
                                    <dt className="text-[11px] font-bold tracking-wider text-[#7A8B8C] uppercase">
                                        Pemilik / pimpinan
                                    </dt>
                                    <dd className="mt-0.5 text-sm font-bold text-[#08090B]">{p.owner_name}</dd>
                                </div>
                            )}
                            {p.public_contact && (
                                <div className="rounded-xl border border-[#EEF1F5] bg-[#FAFBFD] px-4 py-3">
                                    <dt className="text-[11px] font-bold tracking-wider text-[#7A8B8C] uppercase">
                                        Kontak
                                    </dt>
                                    <dd className="mt-0.5 text-sm font-bold tabular-nums text-[#08090B]">
                                        {p.public_contact}
                                    </dd>
                                </div>
                            )}
                            {p.website && (
                                <div className="rounded-xl border border-[#EEF1F5] bg-[#FAFBFD] px-4 py-3">
                                    <dt className="text-[11px] font-bold tracking-wider text-[#7A8B8C] uppercase">
                                        Website
                                    </dt>
                                    <dd className="mt-0.5 text-sm font-bold">
                                        <a
                                            href={p.website}
                                            target="_blank"
                                            rel="noopener noreferrer nofollow"
                                            className="break-all text-[#0891B2] transition hover:underline"
                                        >
                                            {p.website}
                                        </a>
                                    </dd>
                                </div>
                            )}
                            {p.instagram && (
                                <div className="rounded-xl border border-[#EEF1F5] bg-[#FAFBFD] px-4 py-3">
                                    <dt className="text-[11px] font-bold tracking-wider text-[#7A8B8C] uppercase">
                                        Instagram
                                    </dt>
                                    <dd className="mt-0.5 text-sm font-bold">
                                        <a
                                            href={`https://instagram.com/${p.instagram}`}
                                            target="_blank"
                                            rel="noopener noreferrer nofollow"
                                            className="text-[#0891B2] transition hover:underline"
                                        >
                                            @{p.instagram}
                                        </a>
                                    </dd>
                                </div>
                            )}
                        </dl>
                    )}
                </div>
            </Section>

            {/* Promosi Mitra */}
            {promotions.length > 0 && (
                <Section title="Promosi" className="pt-0">
                    <div className="grid gap-4 md:grid-cols-2">
                        {promotions.map((x) => (
                            <Card
                                key={x.id}
                                className="rounded-2xl border border-[#DDE2E9] border-l-4 border-l-[#36D8E8] bg-white p-5 shadow-xs transition hover:shadow-md"
                            >
                                <h3 className="text-base font-extrabold text-[#08090B]">{x.title}</h3>
                                {x.period && (
                                    <p className="mt-1 text-xs font-semibold tabular-nums text-[#0891B2]">{x.period}</p>
                                )}
                                {x.description && (
                                    <p className="mt-2.5 text-sm leading-relaxed text-[#3B4658]">{x.description}</p>
                                )}
                                {x.terms && (
                                    <p className="mt-3 rounded-lg bg-[#FAFBFD] px-3 py-2 text-xs text-[#5B6779]">
                                        Syarat &amp; ketentuan: {x.terms}
                                    </p>
                                )}
                            </Card>
                        ))}
                    </div>
                </Section>
            )}

            {/* Katalog Produk UMKM */}
            {products.length > 0 && (
                <Section title="Produk" className="pt-0">
                    <ul className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {products.map((x) => (
                            <li
                                key={x.id}
                                className="group overflow-hidden rounded-2xl border border-[#DDE2E9] bg-white shadow-xs transition duration-200 hover:-translate-y-0.5 hover:border-[#36D8E8]/80 hover:shadow-md"
                            >
                                <div className="aspect-[4/3] overflow-hidden bg-[#0E1116]">
                                    {x.photo ? (
                                        <img
                                            src={x.photo}
                                            alt={x.name}
                                            loading="lazy"
                                            className="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                                        />
                                    ) : (
                                        <div className="flex h-full items-center justify-center text-xs font-bold tracking-wider text-[#7A8B8C] uppercase">
                                            Tanpa Foto
                                        </div>
                                    )}
                                </div>

                                <div className="p-4">
                                    <div className="flex items-start justify-between gap-2">
                                        <h3 className="text-sm font-extrabold leading-tight text-[#08090B] transition-colors group-hover:text-[#0891B2]">
                                            {x.name}
                                        </h3>
                                        {!x.available && <Badge tone="neutral">Habis</Badge>}
                                    </div>

                                    <p className="mt-1.5 text-sm font-extrabold tabular-nums text-[#0891B2]">
                                        {x.price !== null ? (
                                            rupiah(x.price)
                                        ) : (
                                            <span className="font-normal text-[#8A94A4]">Hubungi mitra</span>
                                        )}
                                        {x.price_note && (
                                            <span className="ml-1.5 text-xs font-medium text-[#5B6779]">
                                                {x.price_note}
                                            </span>
                                        )}
                                    </p>

                                    {x.description && (
                                        <p className="mt-1.5 line-clamp-2 text-xs leading-relaxed text-[#3B4658]">
                                            {x.description}
                                        </p>
                                    )}
                                </div>
                            </li>
                        ))}
                    </ul>
                </Section>
            )}
        </PublicLayout>
    );
}