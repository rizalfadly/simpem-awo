import { useState } from 'react';
import PublicLayout, { Section } from '@/Components/PublicLayout';
import { Empty } from '@/Components/SiteCards';
import { cx } from '@/Components/ui';

function initials(name) {
    return name
        ? name
              .split(/\s+/)
              .slice(0, 2)
              .map((w) => w[0])
              .join('')
              .toUpperCase()
        : '?';
}

function Person({ e, compact = false }) {
    return (
        <div className={cx('flex items-center gap-3', compact ? 'py-1.5' : 'py-2.5')}>
            {e.photo ? (
                <img
                    src={e.photo}
                    alt=""
                    loading="lazy"
                    className={cx(
                        'shrink-0 rounded-full border border-slate-200 object-cover',
                        compact ? 'h-9 w-9' : 'h-11 w-11',
                    )}
                />
            ) : (
                <span
                    aria-hidden="true"
                    className={cx(
                        'flex shrink-0 items-center justify-center rounded-full border border-[#36D8E8]/35 bg-[#08090B] font-extrabold text-[#36D8E8]',
                        compact ? 'h-9 w-9 text-xs' : 'h-11 w-11 text-sm',
                    )}
                >
                    {initials(e.name)}
                </span>
            )}
            <div className="min-w-0">
                <p className={cx('truncate font-bold leading-tight text-slate-900', compact ? 'text-xs' : 'text-sm')}>
                    {e.position}
                </p>
                <p className={cx('truncate text-[#5B6779]', compact ? 'text-xs' : 'text-sm')}>
                    {e.name ?? <span className="italic text-[#8A94A4]">Nama tidak dipublikasikan</span>}
                </p>
            </div>
        </div>
    );
}

function OrgFlowchart({ period, leadership, divisions }) {
    return (
        <div className="mt-8 space-y-8">
            {/* Node Akar: Periode & Struktural */}
            <div className="rounded-2xl border border-white/10 bg-[#08090B] p-5 text-white shadow-sm sm:p-6">
                <div className="flex flex-wrap items-center justify-between gap-2 border-b border-white/10 pb-3.5">
                    <div className="flex items-center gap-2.5">
                        <span className="h-2.5 w-2.5 animate-pulse rounded-full bg-[#36D8E8] shadow-[0_0_8px_#36D8E8]" />
                        <h2 className="text-base font-extrabold tracking-tight text-white sm:text-lg">
                            Struktural
                        </h2>
                        <span className="text-xs font-semibold text-[#7A8B8C]">· Core Squad</span>
                    </div>
                    <span className="rounded-full border border-[#36D8E8]/30 bg-[#36D8E8]/10 px-3 py-0.5 text-xs font-bold text-[#36D8E8]">
                        Periode {period}
                    </span>
                </div>

                {leadership.length > 0 ? (
                    <div className="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        {leadership.map((e, i) => (
                            <div
                                key={i}
                                className="flex items-center gap-3 rounded-xl border border-white/10 bg-white/[0.04] px-3.5 py-2.5 transition hover:border-[#36D8E8]/40"
                            >
                                {e.photo ? (
                                    <img
                                        src={e.photo}
                                        alt=""
                                        loading="lazy"
                                        className="h-10 w-10 shrink-0 rounded-full border border-[#36D8E8]/40 object-cover"
                                    />
                                ) : (
                                    <span
                                        aria-hidden="true"
                                        className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-[#36D8E8]/40 bg-[#36D8E8]/15 text-xs font-black text-[#36D8E8]"
                                    >
                                        {initials(e.name)}
                                    </span>
                                )}
                                <div className="min-w-0">
                                    <p className="truncate text-xs font-extrabold tracking-wide text-[#36D8E8] uppercase">
                                        {e.position}
                                    </p>
                                    <p className="truncate text-sm font-semibold text-white">
                                        {e.name ?? <span className="text-[#7A8B8C]">Nama tidak dipublikasikan</span>}
                                    </p>
                                </div>
                            </div>
                        ))}
                    </div>
                ) : (
                    <p className="mt-4 text-sm text-[#94A3B8]">Data struktural belum diisi.</p>
                )}
            </div>

            {/* Jalur Listrik Mengalir ke Cabang Divisi */}
            {divisions.length > 0 && (
                <div className="relative">
                    <div className="mb-5 flex flex-col items-center">
                        <div className="awo-wire-v h-8 w-[2px]" aria-hidden="true" />
                        <span className="rounded-full border border-[#36D8E8]/40 bg-[#08090B] px-4 py-1 text-[11px] font-extrabold tracking-widest text-[#36D8E8] uppercase shadow-[0_0_16px_rgba(54,216,232,0.18)]">
                            Jalur Divisi ({divisions.length})
                        </span>
                        <div className="awo-wire-v h-7 w-[2px]" aria-hidden="true" />
                    </div>

                    <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                        {divisions.map((d, idx) => (
                            <div
                                key={d.name}
                                className="relative flex flex-col rounded-2xl border border-[#DDE2E9] bg-white p-5 shadow-sm transition hover:border-[#36D8E8]/70 hover:shadow-md"
                            >
                                {/* Titik kontak / node masuk aliran di atas kartu divisi */}
                                <span
                                    aria-hidden="true"
                                    style={{ animationDelay: `${idx * 0.25}s` }}
                                    className="mx-auto -mt-8 mb-3 flex h-3.5 w-3.5 items-center justify-center rounded-full border-2 border-[#08090B] bg-[#36D8E8] shadow-[0_0_10px_rgba(54,216,232,0.85)]"
                                />

                                <div className="flex items-center justify-between gap-2 border-b border-[#EEF1F5] pb-3">
                                    <div className="flex items-center gap-2.5">
                                        <span className="h-4 w-1 rounded-full bg-[#36D8E8]" aria-hidden="true" />
                                        <h3 className="text-base font-extrabold tracking-tight text-[#08090B]">
                                            {d.name}
                                        </h3>
                                    </div>
                                    <span className="rounded-full bg-[#F2F4F7] px-2.5 py-0.5 text-[11px] font-bold tabular-nums text-[#5B6779]">
                                        {d.entries.length}
                                    </span>
                                </div>

<ul className="mt-3 space-y-1">
                                    {d.entries.map((e, i) => {
                                        const isLast = i === d.entries.length - 1;
                                        return (
                                            <li key={i} className="relative flex items-stretch gap-3">
                                                {/* Jalur aliran hijau antar-warga/pengurus dalam divisi */}
                                                <div
                                                    className="flex w-5 shrink-0 flex-col items-center"
                                                    aria-hidden="true"
                                                >
                                                    <span
                                                        style={{ animationDelay: `${i * 0.2}s` }}
                                                        className={cx(
                                                            'mt-3.5 h-2.5 w-2.5 shrink-0 rounded-full border-2 border-[#08090B] bg-[#22C55E]',
                                                            i === 0 && 'awo-node-green',
                                                        )}
                                                    />
                                                    {!isLast && (
                                                        <div
                                                            style={{ animationDelay: `${i * 0.22}s` }}
                                                            className="awo-wire-green my-0.5 w-[2px] flex-1"
                                                        />
                                                    )}
                                                </div>

                                                <div className="min-w-0 flex-1 rounded-xl border border-transparent px-2 py-1 transition hover:border-emerald-500/35 hover:bg-emerald-500/[0.04]">
                                                    <Person e={e} compact />
                                                </div>
                                            </li>
                                        );
                                    })}
                                </ul>
                            </div>
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
}

export default function Structure({ period, leadership = [], divisions = [] }) {
    const [viewMode, setViewMode] = useState('diagram');

    return (
        <PublicLayout title="Struktur pengurus">
            {/* Header Banner Gelap Beraksen Cyan */}
            <section className="relative overflow-hidden border-b border-white/10 bg-[#08090B] text-white">
                <div className="awo-hero-grid pointer-events-none absolute inset-0" aria-hidden="true" />
                <div
                    className="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[#36D8E8]/15 blur-3xl"
                    aria-hidden="true"
                />

                <div className="relative mx-auto flex max-w-6xl flex-wrap items-end justify-between gap-4 px-4 py-12 sm:px-6 sm:py-16">
                    <div>
                        <div className="inline-flex items-center gap-2 rounded-full border border-[#36D8E8]/35 bg-[#36D8E8]/10 px-3.5 py-1 text-[11px] font-bold tracking-widest text-[#36D8E8] uppercase">
                            <span className="h-2 w-2 rounded-full bg-[#36D8E8] shadow-[0_0_8px_#36D8E8]" />
                            <span>Bagan Kepengurusan</span>
                        </div>
                        <h1 className="mt-4 text-4xl font-black tracking-tight sm:text-6xl">Struktur pengurus</h1>
                        {period && (
                            <p className="mt-2.5 text-sm font-medium text-[#94A3B8] sm:text-base">
                                Periode <span className="font-bold text-white">{period}</span>
                            </p>
                        )}
                    </div>

                    {period && (
                        <div
                            role="group"
                            aria-label="Mode tampilan struktur"
                            className="inline-flex rounded-xl border border-white/15 bg-white/[0.04] p-1"
                        >
                            <button
                                type="button"
                                onClick={() => setViewMode('diagram')}
                                className={cx(
                                    'rounded-lg px-3.5 py-1.5 text-xs font-extrabold transition',
                                    viewMode === 'diagram'
                                        ? 'bg-[#36D8E8] text-[#08090B] shadow-[0_0_14px_rgba(54,216,232,0.35)]'
                                        : 'text-[#94A3B8] hover:text-white',
                                )}
                            >
                                Diagram Alir
                            </button>
                            <button
                                type="button"
                                onClick={() => setViewMode('list')}
                                className={cx(
                                    'rounded-lg px-3.5 py-1.5 text-xs font-extrabold transition',
                                    viewMode === 'list'
                                        ? 'bg-[#36D8E8] text-[#08090B] shadow-[0_0_14px_rgba(54,216,232,0.35)]'
                                        : 'text-[#94A3B8] hover:text-white',
                                )}
                            >
                                Daftar
                            </button>
                        </div>
                    )}
                </div>
            </section>

            <Section className="py-10">
                {!period ? (
                    <div className="rounded-2xl border border-[#DDE2E9] bg-white p-2 shadow-sm">
                        <Empty>Struktur pengurus belum tersedia.</Empty>
                    </div>
                ) : viewMode === 'diagram' ? (
                    <OrgFlowchart period={period} leadership={leadership} divisions={divisions} />
                ) : (
                    <div className="space-y-10">
                        {leadership.length > 0 && (
                            <div className="rounded-2xl border border-[#DDE2E9] bg-white p-6 shadow-sm">
                                <h2 className="border-b-2 border-[#08090B] pb-1.5 text-xl font-extrabold tracking-tight text-[#08090B]">
                                    Pimpinan
                                </h2>
                                <ul className="mt-3 grid gap-x-10 sm:grid-cols-2">
                                    {leadership.map((e, i) => (
                                        <li key={i}>
                                            <Person e={e} />
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        )}

                        {divisions.length > 0 && (
                            <div className="grid gap-6 md:grid-cols-2">
                                {divisions.map((d) => (
                                    <div
                                        key={d.name}
                                        className="rounded-2xl border border-[#DDE2E9] bg-white p-6 shadow-sm"
                                    >
                                        <h2 className="border-b-2 border-[#08090B] pb-1.5 text-lg font-extrabold tracking-tight text-[#08090B]">
                                            {d.name}
                                        </h2>
                                        <ul className="mt-2 divide-y divide-[#F4F6F9]">
                                            {d.entries.map((e, i) => (
                                                <li key={i}>
                                                    <Person e={e} />
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                )}
            </Section>
        </PublicLayout>
    );
}