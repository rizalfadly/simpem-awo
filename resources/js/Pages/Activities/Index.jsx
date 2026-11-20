import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Badge, Button, Card, Input, PageHeader, Pagination, Select } from '@/Components/ui';
import { useCan } from '@/lib/can';
import { statusTone } from '@/lib/format';

export default function Index({ activities, filters, statuses, periods, divisions, has_member }) {
    const can = useCan();
    const [q, setQ] = useState(filters.q ?? '');

    const apply = (patch = {}) => {
        const next = { q, status: filters.status, period: filters.period, division: filters.division, mine: filters.mine, ...patch };
        Object.keys(next).forEach((k) => (next[k] === '' || next[k] == null || next[k] === false) && delete next[k]);
        router.get('/activities', next, { preserveState: true, replace: true });
    };

    return (
        <AppLayout>
            <Head title="Kegiatan" />
            <PageHeader
                title="Kegiatan"
                description="Perencanaan kegiatan organisasi: proposal, panitia, timeline, anggaran, dan LPJ."
                actions={can('activities.create') && <Button href="/activities/create">Buat kegiatan</Button>}
            />

            <Card>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        apply();
                    }}
                    className="flex flex-wrap items-center gap-3 border-b border-[#DDE2E9] p-4"
                >
                    <Input
                        type="search"
                        value={q}
                        onChange={(e) => setQ(e.target.value)}
                        placeholder="Cari nama, kode, lokasi"
                        aria-label="Cari kegiatan"
                        className="min-w-52 flex-1"
                    />
                    <Select value={filters.status} onChange={(e) => apply({ status: e.target.value })} aria-label="Filter status" className="w-44">
                        <option value="">Semua status</option>
                        {Object.entries(statuses).map(([k, v]) => (
                            <option key={k} value={k}>
                                {v}
                            </option>
                        ))}
                    </Select>
                    <Select value={filters.period} onChange={(e) => apply({ period: e.target.value })} aria-label="Filter periode" className="w-40">
                        <option value="">Semua periode</option>
                        {periods.map((p) => (
                            <option key={p.id} value={p.id}>
                                {p.name}
                            </option>
                        ))}
                    </Select>
                    <Select value={filters.division} onChange={(e) => apply({ division: e.target.value })} aria-label="Filter divisi" className="w-44">
                        <option value="">Semua divisi</option>
                        {divisions.map((d) => (
                            <option key={d.id} value={d.id}>
                                {d.name}
                            </option>
                        ))}
                    </Select>
                    {has_member && (
                        <label className="flex items-center gap-2 text-sm">
                            <input
                                type="checkbox"
                                checked={filters.mine === '1'}
                                onChange={(e) => apply({ mine: e.target.checked ? '1' : '' })}
                                className="h-4 w-4 rounded border-[#CBD2DC] text-[#B3202A] focus:ring-[#B3202A]"
                            />
                            Kegiatan saya
                        </label>
                    )}
                    <Button type="submit" variant="secondary">
                        Cari
                    </Button>
                </form>

                {activities.data.length === 0 ? (
                    <p className="px-4 py-12 text-center text-sm text-[#5B6779]">Belum ada kegiatan yang cocok.</p>
                ) : (
                    <ul className="divide-y divide-[#EEF1F5]">
                        {activities.data.map((a) => (
                            <li key={a.id}>
                                <Link href={`/activities/${a.id}`} className="block px-5 py-4 hover:bg-[#F7F8FA]">
                                    <div className="flex flex-wrap items-start justify-between gap-2">
                                        <div className="min-w-0">
                                            <p className="text-base font-bold leading-tight">{a.title}</p>
                                            <p className="mt-1 text-sm text-[#5B6779]">
                                                <span className="tabular-nums">{a.when}</span>
                                                {a.location ? `, ${a.location}` : ''}
                                            </p>
                                            <p className="mt-1 text-xs text-[#8A94A4]">
                                                <span className="tabular-nums">{a.code}</span>
                                                {a.division ? ` · ${a.division}` : ''}
                                                {a.period ? ` · ${a.period}` : ''}
                                                {a.pic ? ` · PIC ${a.pic}` : ''}
                                            </p>
                                        </div>
                                        <Badge tone={statusTone(a.status)}>{a.status_label}</Badge>
                                    </div>
                                </Link>
                            </li>
                        ))}
                    </ul>
                )}

                <div className="flex items-center justify-between border-t border-[#DDE2E9]">
                    <p className="px-4 text-sm text-[#5B6779]">{activities.total} kegiatan</p>
                    <Pagination links={activities.links} />
                </div>
            </Card>
        </AppLayout>
    );
}
