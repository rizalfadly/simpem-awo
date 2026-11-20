import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Badge, Button, Card, Input, PageHeader, Pagination, Select } from '@/Components/ui';

const tone = { published: 'green', draft: 'neutral', archived: 'neutral' };

export default function Index({ albums, filters, statuses, can_create }) {
    const [q, setQ] = useState(filters.q ?? '');
    const apply = (patch = {}) => {
        const next = { q, status: filters.status, ...patch };
        Object.keys(next).forEach((k) => (next[k] === '' || next[k] == null) && delete next[k]);
        router.get('/albums', next, { preserveState: true, replace: true });
    };

    return (
        <AppLayout>
            <Head title="Galeri" />
            <PageHeader
                title="Galeri"
                description="Album foto dan video kegiatan organisasi."
                actions={can_create && <Button href="/albums/create">Buat album</Button>}
            />

            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    apply();
                }}
                className="mb-6 flex flex-wrap gap-3"
            >
                <Input type="search" value={q} onChange={(e) => setQ(e.target.value)} placeholder="Cari judul album" aria-label="Cari album" className="min-w-52 flex-1" />
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

            {albums.data.length === 0 ? (
                <p className="rounded-lg border border-dashed border-[#CBD2DC] bg-white px-6 py-14 text-center text-sm text-[#5B6779]">Belum ada album.</p>
            ) : (
                <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    {albums.data.map((a) => (
                        <Link key={a.id} href={`/albums/${a.id}`} className="group block overflow-hidden rounded-lg border border-[#DDE2E9] bg-white hover:border-[#16202E]">
                            <div className="aspect-[4/3] bg-[#E9EDF2]">
                                {a.cover ? (
                                    <img src={a.cover} alt="" loading="lazy" className="h-full w-full object-cover" />
                                ) : (
                                    <div className="flex h-full items-center justify-center text-sm text-[#8A94A4]">Belum ada foto</div>
                                )}
                            </div>
                            <div className="p-4">
                                <div className="flex items-start justify-between gap-2">
                                    <h2 className="text-base font-bold leading-tight">{a.title}</h2>
                                    {a.status !== 'published' && <Badge tone={tone[a.status]}>{a.status_label}</Badge>}
                                </div>
                                <p className="mt-1 text-xs text-[#5B6779]">
                                    {a.event_date ? `${a.event_date} · ` : ''}
                                    {a.photos} foto
                                    {a.videos ? `, ${a.videos} video` : ''}
                                </p>
                                {a.activity && <p className="mt-0.5 text-xs text-[#8A94A4]">{a.activity}</p>}
                            </div>
                        </Link>
                    ))}
                </div>
            )}

            <Card className="mt-6">
                <div className="flex items-center justify-between">
                    <p className="px-4 py-2 text-sm text-[#5B6779]">{albums.total} album</p>
                    <Pagination links={albums.links} />
                </div>
            </Card>
        </AppLayout>
    );
}
