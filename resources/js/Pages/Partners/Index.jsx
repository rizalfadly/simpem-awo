import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Badge, Button, Input, PageHeader, Pagination, Select } from '@/Components/ui';

export default function Index({ partners, filters, types, statuses, can }) {
    const [q, setQ] = useState(filters.q ?? '');
    const apply = (patch = {}) => {
        const next = { q, type: filters.type, status: filters.status, ...patch };
        Object.keys(next).forEach((k) => (next[k] === '' || next[k] == null) && delete next[k]);
        router.get('/partners', next, { preserveState: true, replace: true });
    };

    return (
        <AppLayout>
            <Head title="Mitra & UMKM" />
            <PageHeader
                title="Mitra & UMKM"
                description="Direktori UMKM anggota, sponsor, instansi, dan komunitas mitra organisasi."
                actions={
                    <>
                        <Button href="/partners/promotions" variant="secondary">
                            Promosi mitra
                        </Button>
                        {can.manage && <Button href="/partners/create">Tambah mitra</Button>}
                    </>
                }
            />

            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    apply();
                }}
                className="mb-6 flex flex-wrap gap-3"
            >
                <Input type="search" value={q} onChange={(e) => setQ(e.target.value)} placeholder="Cari nama, bidang, kota, pemilik" aria-label="Cari mitra" className="min-w-52 flex-1" />
                <Select value={filters.type} onChange={(e) => apply({ type: e.target.value })} aria-label="Filter jenis" className="w-48">
                    <option value="">Semua jenis</option>
                    {Object.entries(types).map(([k, v]) => (
                        <option key={k} value={k}>
                            {v}
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

            {partners.data.length === 0 ? (
                <p className="rounded-lg border border-dashed border-[#CBD2DC] bg-white px-6 py-14 text-center text-sm text-[#5B6779]">Belum ada mitra.</p>
            ) : (
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {partners.data.map((p) => (
                        <Link key={p.id} href={`/partners/${p.id}`} className="flex gap-4 rounded-lg border border-[#DDE2E9] bg-white p-4 hover:border-[#16202E]">
                            {p.logo ? (
                                <img src={p.logo} alt="" loading="lazy" className="h-16 w-16 shrink-0 rounded-md object-cover" />
                            ) : (
                                <span className="flex h-16 w-16 shrink-0 items-center justify-center rounded-md bg-[#E9EDF2] text-xl font-extrabold text-[#3B4658]">{p.name.slice(0, 1).toUpperCase()}</span>
                            )}
                            <div className="min-w-0">
                                <h2 className="text-sm font-bold leading-tight">{p.name}</h2>
                                <p className="mt-0.5 text-xs text-[#5B6779]">
                                    {p.type_label}
                                    {p.sector ? ` · ${p.sector}` : ''}
                                    {p.city ? ` · ${p.city}` : ''}
                                </p>
                                {p.description && <p className="mt-1.5 line-clamp-2 text-xs text-[#3B4658]">{p.description}</p>}
                                <div className="mt-2 flex flex-wrap gap-1">
                                    {p.products_count > 0 && <Badge>{p.products_count} produk</Badge>}
                                    {p.status !== 'active' && <Badge tone="neutral">{p.status_label}</Badge>}
                                    {p.is_public && <Badge tone="green">Tampil publik</Badge>}
                                </div>
                            </div>
                        </Link>
                    ))}
                </div>
            )}

            <div className="mt-4 flex items-center justify-between">
                <p className="text-sm text-[#5B6779]">{partners.total} mitra</p>
                <Pagination links={partners.links} />
            </div>
        </AppLayout>
    );
}
