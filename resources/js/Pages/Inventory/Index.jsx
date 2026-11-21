import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Badge, Button, Card, Input, PageHeader, Pagination, Select } from '@/Components/ui';

export const conditionLabels = { good: 'Baik', minor: 'Rusak ringan', broken: 'Rusak berat', lost: 'Hilang' };

export default function Index({ assets, filters, categories, statuses, can }) {
    const [q, setQ] = useState(filters.q ?? '');
    const apply = (patch = {}) => {
        const next = { q, category: filters.category, status: filters.status, ...patch };
        Object.keys(next).forEach((k) => (next[k] === '' || next[k] == null) && delete next[k]);
        router.get('/inventory', next, { preserveState: true, replace: true });
    };

    return (
        <AppLayout>
            <Head title="Barang" />
            <PageHeader
                title="Barang"
                description={can.viewer ? 'Inventaris organisasi lengkap dengan kondisi dan jumlahnya.' : 'Barang organisasi yang dapat dipinjam anggota.'}
                actions={
                    <>
                        {can.request && (
                            <Button href="/inventory/loans/create" variant={can.manage ? 'secondary' : 'primary'}>
                                Ajukan peminjaman
                            </Button>
                        )}
                        {can.manage && (
                            <>
                                <Button href="/inventory/categories" variant="secondary">
                                    Kategori
                                </Button>
                                <Button href="/inventory/create">Tambah barang</Button>
                            </>
                        )}
                    </>
                }
            />

            <Card>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        apply();
                    }}
                    className="flex flex-wrap gap-3 border-b border-[#DDE2E9] p-4"
                >
                    <Input type="search" value={q} onChange={(e) => setQ(e.target.value)} placeholder="Cari nama, kode, lokasi" aria-label="Cari barang" className="min-w-52 flex-1" />
                    <Select value={filters.category} onChange={(e) => apply({ category: e.target.value })} aria-label="Filter kategori" className="w-48">
                        <option value="">Semua kategori</option>
                        {categories.map((c) => (
                            <option key={c.id} value={c.id}>
                                {c.name}
                            </option>
                        ))}
                    </Select>
                    {can.viewer && (
                        <Select value={filters.status} onChange={(e) => apply({ status: e.target.value })} aria-label="Filter status" className="w-44">
                            <option value="">Semua status</option>
                            {Object.entries(statuses).map(([k, v]) => (
                                <option key={k} value={k}>
                                    {v}
                                </option>
                            ))}
                        </Select>
                    )}
                    <Button type="submit" variant="secondary">
                        Cari
                    </Button>
                </form>

                <div className="overflow-x-auto">
                    <table className="w-full min-w-[760px] text-left text-sm">
                        <thead className="border-b border-[#DDE2E9] text-xs text-[#5B6779]">
                            <tr>
                                <th className="px-4 py-3 font-semibold">Barang</th>
                                <th className="px-3 py-3 font-semibold">Kategori / lokasi</th>
                                {can.viewer && <th className="px-3 py-3 font-semibold">Kondisi</th>}
                                <th className="px-3 py-3 font-semibold">Tersedia hari ini</th>
                                <th className="px-4 py-3" />
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-[#EEF1F5]">
                            {assets.data.length === 0 && (
                                <tr>
                                    <td colSpan={5} className="px-4 py-10 text-center text-[#5B6779]">
                                        Tidak ada barang yang cocok.
                                    </td>
                                </tr>
                            )}
                            {assets.data.map((a) => (
                                <tr key={a.id}>
                                    <td className="px-4 py-3">
                                        <Link href={`/inventory/${a.id}`} className="flex items-center gap-3">
                                            {a.thumb ? (
                                                <img src={a.thumb} alt="" loading="lazy" className="h-12 w-12 shrink-0 rounded-md object-cover" />
                                            ) : (
                                                <span className="flex h-12 w-12 shrink-0 items-center justify-center rounded-md bg-[#E9EDF2] text-xs text-[#8A94A4]">Foto</span>
                                            )}
                                            <span>
                                                <span className="block font-semibold leading-tight hover:underline">{a.name}</span>
                                                <span className="block text-xs tabular-nums text-[#5B6779]">{a.code}</span>
                                            </span>
                                        </Link>
                                    </td>
                                    <td className="px-3 py-3 text-[#3B4658]">
                                        {a.category ?? '-'}
                                        {a.location && <div className="text-xs text-[#5B6779]">{a.location}</div>}
                                    </td>
                                    {can.viewer && (
                                        <td className="px-3 py-3">
                                            <div className="flex flex-wrap gap-1">
                                                <Badge tone="green">
                                                    {conditionLabels.good} {a.conditions.good}
                                                </Badge>
                                                {a.conditions.minor > 0 && <Badge tone="amber">Ringan {a.conditions.minor}</Badge>}
                                                {a.conditions.broken > 0 && <Badge tone="red">Berat {a.conditions.broken}</Badge>}
                                                {a.conditions.lost > 0 && <Badge tone="red">Hilang {a.conditions.lost}</Badge>}
                                            </div>
                                            <div className="mt-1 text-xs text-[#5B6779]">
                                                Total {a.total} {a.unit}
                                            </div>
                                        </td>
                                    )}
                                    <td className="px-3 py-3">
                                        {a.loanable ? (
                                            <span className="font-semibold tabular-nums">
                                                {a.available} <span className="font-normal text-[#5B6779]">{a.unit}</span>
                                            </span>
                                        ) : (
                                            <Badge tone="neutral">{a.status === 'retired' ? 'Dipensiunkan' : 'Tidak dipinjamkan'}</Badge>
                                        )}
                                    </td>
                                    <td className="px-4 py-3 text-right">
                                        {a.loanable && can.request && (
                                            <Button size="sm" variant="secondary" href={`/inventory/loans/create?asset=${a.id}`}>
                                                Pinjam
                                            </Button>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
                <div className="flex items-center justify-between border-t border-[#DDE2E9]">
                    <p className="px-4 text-sm text-[#5B6779]">{assets.total} jenis barang</p>
                    <Pagination links={assets.links} />
                </div>
            </Card>
        </AppLayout>
    );
}
