import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Badge, Button, Input, PageHeader, Pagination, Select } from '@/Components/ui';

export default function Index({ posts, filters, statuses, can_create }) {
    const [q, setQ] = useState(filters.q ?? '');
    const apply = (patch = {}) => {
        const next = { q, status: filters.status, ...patch };
        Object.keys(next).forEach((k) => (next[k] === '' || next[k] == null) && delete next[k]);
        router.get('/news', next, { preserveState: true, replace: true });
    };

    return (
        <AppLayout>
            <Head title="Berita" />
            <PageHeader title="Berita" description="Kabar dan publikasi kegiatan organisasi." actions={can_create && <Button href="/news/create">Tulis berita</Button>} />

            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    apply();
                }}
                className="mb-6 flex flex-wrap gap-3"
            >
                <Input type="search" value={q} onChange={(e) => setQ(e.target.value)} placeholder="Cari judul berita" aria-label="Cari berita" className="min-w-52 flex-1" />
                <Select value={filters.status} onChange={(e) => apply({ status: e.target.value })} aria-label="Filter status" className="w-48">
                    <option value="">Semua (tanpa arsip)</option>
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

            {posts.data.length === 0 ? (
                <p className="rounded-lg border border-dashed border-[#CBD2DC] bg-white px-6 py-14 text-center text-sm text-[#5B6779]">Belum ada berita.</p>
            ) : (
                <ul className="divide-y divide-[#DDE2E9] overflow-hidden rounded-lg border border-[#DDE2E9] bg-white">
                    {posts.data.map((p) => (
                        <li key={p.id}>
                            <Link href={`/news/${p.id}`} className="flex gap-4 p-4 hover:bg-[#F7F8FA]">
                                {p.cover && <img src={p.cover} alt="" loading="lazy" className="h-20 w-28 shrink-0 rounded-md object-cover" />}
                                <div className="min-w-0 flex-1">
                                    <div className="flex flex-wrap items-start justify-between gap-2">
                                        <h2 className="text-base font-bold leading-tight">{p.title}</h2>
                                        {p.status !== 'published' && <Badge tone="neutral">{p.status_label}</Badge>}
                                    </div>
                                    {p.excerpt && <p className="mt-1 line-clamp-2 text-sm text-[#3B4658]">{p.excerpt}</p>}
                                    <p className="mt-1.5 text-xs text-[#5B6779]">
                                        {p.published_at ?? 'Belum terbit'}
                                        {p.activity ? ` · ${p.activity}` : ''}
                                    </p>
                                </div>
                            </Link>
                        </li>
                    ))}
                </ul>
            )}

            <div className="mt-4 flex items-center justify-between">
                <p className="text-sm text-[#5B6779]">{posts.total} berita</p>
                <Pagination links={posts.links} />
            </div>
        </AppLayout>
    );
}
