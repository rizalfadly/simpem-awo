import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Badge, Button, Card, Input, PageHeader, Pagination, Select } from '@/Components/ui';
import { rupiah } from '@/lib/format';

export const partnershipTone = { draft: 'neutral', active: 'green', completed: 'neutral', terminated: 'red' };

export default function Index({ items, filters, statuses, can_manage }) {
    const [q, setQ] = useState(filters.q ?? '');
    const apply = (patch = {}) => {
        const next = { q, status: filters.status, ...patch };
        Object.keys(next).forEach((k) => (next[k] === '' || next[k] == null) && delete next[k]);
        router.get('/partners/partnerships', next, { preserveState: true, replace: true });
    };

    return (
        <AppLayout>
            <Head title="Kerja Sama" />
            <PageHeader
                title="Kerja sama organisasi"
                description="Sponsorship, MoU, barter, dan kerja sama kegiatan dengan mitra. Data internal pengurus."
                actions={can_manage && <Button href="/partners/partnerships/create">Catat kerja sama</Button>}
            />

            <Card>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        apply();
                    }}
                    className="flex flex-wrap gap-3 border-b border-[#DDE2E9] p-4"
                >
                    <Input type="search" value={q} onChange={(e) => setQ(e.target.value)} placeholder="Cari judul atau nama mitra" aria-label="Cari kerja sama" className="min-w-52 flex-1" />
                    <Select value={filters.status} onChange={(e) => apply({ status: e.target.value })} aria-label="Filter status" className="w-56">
                        <option value="">Semua status</option>
                        <option value="expiring">Berakhir dalam 30 hari</option>
                        <option value="lapsed">Lewat tanggal berakhir</option>
                        {Object.entries(statuses).map(([k, v]) => (
                            <option key={k} value={k}>
                                {v}
                            </option>
                        ))}
                    </Select>
                    <Button type="submit" variant="secondary">
                        Cari
                    </Button>
                </form>

                {items.data.length === 0 ? (
                    <p className="px-4 py-12 text-center text-sm text-[#5B6779]">Belum ada kerja sama.</p>
                ) : (
                    <ul className="divide-y divide-[#EEF1F5]">
                        {items.data.map((p) => (
                            <li key={p.id}>
                                <Link href={`/partners/partnerships/${p.id}`} className="flex flex-wrap items-start justify-between gap-3 px-5 py-4 hover:bg-[#F7F8FA]">
                                    <div className="min-w-0">
                                        <p className="text-sm font-bold">{p.title}</p>
                                        <p className="mt-0.5 text-xs text-[#5B6779]">
                                            {p.partner} · {p.type_label} · <span className="tabular-nums">{p.period}</span>
                                            {p.value !== null ? ` · ${rupiah(p.value)}` : ''}
                                        </p>
                                    </div>
                                    <div className="flex flex-col items-end gap-1">
                                        <Badge tone={partnershipTone[p.status]}>{p.status_label}</Badge>
                                        {p.lapsed && <Badge tone="red">Lewat tanggal berakhir</Badge>}
                                        {p.expiring && <Badge tone="amber">Segera berakhir</Badge>}
                                    </div>
                                </Link>
                            </li>
                        ))}
                    </ul>
                )}
                <div className="border-t border-[#DDE2E9]">
                    <Pagination links={items.links} />
                </div>
            </Card>
        </AppLayout>
    );
}
