import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Badge, Button, Card, Input, PageHeader, Pagination, Select } from '@/Components/ui';

export default function Index({ documents, filters, categories, periods, can_manage }) {
    const [q, setQ] = useState(filters.q ?? '');
    const [year, setYear] = useState(filters.year ?? '');

    const apply = (patch = {}) => {
        const next = { q, year, category: filters.category, period: filters.period, ...patch };
        Object.keys(next).forEach((k) => (next[k] === '' || next[k] == null) && delete next[k]);
        router.get('/archive', next, { preserveState: true, replace: true });
    };

    return (
        <AppLayout>
            <Head title="Arsip Dokumen" />
            <PageHeader
                title="Arsip Dokumen"
                description="Notulen, surat, SK, proposal, dan laporan organisasi."
                actions={can_manage && <Button href="/archive/create">Arsipkan dokumen</Button>}
            />

            <Card>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        apply();
                    }}
                    className="flex flex-wrap gap-3 border-b border-[#DDE2E9] p-4"
                >
                    <Input type="search" value={q} onChange={(e) => setQ(e.target.value)} placeholder="Cari judul, nomor surat, uraian" aria-label="Cari dokumen" className="min-w-52 flex-1" />
                    <Select value={filters.category} onChange={(e) => apply({ category: e.target.value })} aria-label="Filter kategori" className="w-44">
                        <option value="">Semua kategori</option>
                        {Object.entries(categories).map(([k, v]) => (
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
                    <Input type="number" value={year} onChange={(e) => setYear(e.target.value)} placeholder="Tahun" aria-label="Tahun dokumen" className="w-28" />
                    <Button type="submit" variant="secondary">
                        Cari
                    </Button>
                </form>

                <div className="overflow-x-auto">
                    <table className="w-full min-w-[760px] text-left text-sm">
                        <thead className="border-b border-[#DDE2E9] text-xs text-[#5B6779]">
                            <tr>
                                <th className="px-4 py-3 font-semibold">Dokumen</th>
                                <th className="px-3 py-3 font-semibold">Kategori</th>
                                <th className="px-3 py-3 font-semibold">Tanggal</th>
                                <th className="px-3 py-3 font-semibold">Akses</th>
                                <th className="px-3 py-3 text-right font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-[#EEF1F5]">
                            {documents.data.length === 0 && (
                                <tr>
                                    <td colSpan={5} className="px-4 py-10 text-center text-[#5B6779]">
                                        Tidak ada dokumen yang cocok.
                                    </td>
                                </tr>
                            )}
                            {documents.data.map((d) => (
                                <tr key={d.id}>
                                    <td className="px-4 py-3">
                                        <div className="font-semibold">{d.title}</div>
                                        <div className="text-xs text-[#5B6779]">
                                            {d.document_number ? `${d.document_number} · ` : ''}
                                            {d.original_name} ({d.size})
                                        </div>
                                        {(d.activity || d.period) && (
                                            <div className="text-xs text-[#8A94A4]">
                                                {[d.activity, d.period].filter(Boolean).join(' · ')}
                                            </div>
                                        )}
                                    </td>
                                    <td className="px-3 py-3">
                                        <Badge>{d.category_label}</Badge>
                                    </td>
                                    <td className="px-3 py-3 tabular-nums">{d.document_date ?? '-'}</td>
                                    <td className="px-3 py-3">
                                        <Badge tone={d.access === 'restricted' ? 'amber' : 'neutral'}>{d.access_label}</Badge>
                                    </td>
                                    <td className="px-3 py-3">
                                        <div className="flex justify-end gap-1.5">
                                            <a href={`/archive/${d.id}/download`} className="inline-flex rounded-md border border-[#CBD2DC] bg-white px-2.5 py-1.5 text-xs font-semibold hover:bg-[#F2F4F7]">
                                                Unduh
                                            </a>
                                            {can_manage && (
                                                <>
                                                    <Button variant="secondary" size="sm" href={`/archive/${d.id}/edit`}>
                                                        Ubah
                                                    </Button>
                                                    <Button variant="danger" size="sm" onClick={() => window.confirm(`Hapus "${d.title}" dari arsip?`) && router.delete(`/archive/${d.id}`, { preserveScroll: true })}>
                                                        Hapus
                                                    </Button>
                                                </>
                                            )}
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <div className="flex items-center justify-between border-t border-[#DDE2E9]">
                    <p className="px-4 text-sm text-[#5B6779]">{documents.total} dokumen</p>
                    <Pagination links={documents.links} />
                </div>
            </Card>
        </AppLayout>
    );
}
