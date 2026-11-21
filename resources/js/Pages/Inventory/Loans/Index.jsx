import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Badge, Button, Card, Input, PageHeader, Pagination, Select } from '@/Components/ui';

export const loanTone = { requested: 'amber', approved: 'amber', lent: 'neutral', returned: 'green', rejected: 'red', cancelled: 'neutral' };

export default function Index({ loans, filters, statuses, viewer, can_request }) {
    const [q, setQ] = useState(filters.q ?? '');
    const apply = (patch = {}) => {
        const next = { q, status: filters.status, mine: filters.mine, ...patch };
        Object.keys(next).forEach((k) => (next[k] === '' || next[k] == null) && delete next[k]);
        router.get('/inventory/loans', next, { preserveState: true, replace: true });
    };

    return (
        <AppLayout>
            <Head title="Peminjaman" />
            <PageHeader
                title="Peminjaman barang"
                description={viewer ? 'Seluruh pengajuan dan peminjaman barang organisasi.' : 'Pengajuan dan peminjaman barang milik Anda.'}
                actions={can_request && <Button href="/inventory/loans/create">Ajukan peminjaman</Button>}
            />

            <Card>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        apply();
                    }}
                    className="flex flex-wrap items-center gap-3 border-b border-[#DDE2E9] p-4"
                >
                    <Input type="search" value={q} onChange={(e) => setQ(e.target.value)} placeholder="Cari kode, peminjam, tujuan" aria-label="Cari peminjaman" className="min-w-52 flex-1" />
                    <Select value={filters.status} onChange={(e) => apply({ status: e.target.value })} aria-label="Filter status" className="w-52">
                        <option value="">Semua status</option>
                        <option value="overdue">Terlambat dikembalikan</option>
                        {Object.entries(statuses).map(([k, v]) => (
                            <option key={k} value={k}>
                                {v}
                            </option>
                        ))}
                    </Select>
                    {viewer && (
                        <label className="flex items-center gap-2 text-sm">
                            <input type="checkbox" checked={filters.mine === '1'} onChange={(e) => apply({ mine: e.target.checked ? '1' : '' })} className="h-4 w-4 rounded border-[#CBD2DC] text-[#B3202A] focus:ring-[#B3202A]" />
                            Milik saya saja
                        </label>
                    )}
                    <Button type="submit" variant="secondary">
                        Cari
                    </Button>
                </form>

                {loans.data.length === 0 ? (
                    <p className="px-4 py-12 text-center text-sm text-[#5B6779]">Belum ada peminjaman.</p>
                ) : (
                    <ul className="divide-y divide-[#EEF1F5]">
                        {loans.data.map((l) => (
                            <li key={l.id}>
                                <Link href={`/inventory/loans/${l.id}`} className="flex flex-wrap items-start justify-between gap-3 px-5 py-4 hover:bg-[#F7F8FA]">
                                    <div className="min-w-0">
                                        <p className="text-sm font-bold">{l.items}</p>
                                        <p className="mt-0.5 text-xs text-[#5B6779]">
                                            <span className="tabular-nums">{l.code}</span> · {l.borrower} · <span className="tabular-nums">{l.period}</span>
                                        </p>
                                        <p className="mt-0.5 text-xs text-[#8A94A4]">{l.purpose}</p>
                                    </div>
                                    <div className="flex flex-col items-end gap-1">
                                        <Badge tone={loanTone[l.status]}>{l.status_label}</Badge>
                                        {l.overdue && <Badge tone="red">Terlambat {l.late_days} hari</Badge>}
                                        {!l.overdue && l.status === 'returned' && l.late_days > 0 && <Badge tone="amber">Telat {l.late_days} hari</Badge>}
                                    </div>
                                </Link>
                            </li>
                        ))}
                    </ul>
                )}
                <div className="border-t border-[#DDE2E9]">
                    <Pagination links={loans.links} />
                </div>
            </Card>
        </AppLayout>
    );
}
