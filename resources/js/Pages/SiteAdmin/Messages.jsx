import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Badge, Button, Card, PageHeader, Pagination, Select } from '@/Components/ui';

const keep = { preserveScroll: true };

export default function Messages({ items, status }) {
    return (
        <AppLayout>
            <Head title="Pesan Masuk" />
            <PageHeader title="Pesan masuk" description="Pesan dari formulir kontak website publik." />

            <Card>
                <div className="border-b border-[#DDE2E9] p-4">
                    <Select value={status} onChange={(e) => router.get('/site/messages', e.target.value ? { status: e.target.value } : {}, { preserveState: true, replace: true })} aria-label="Filter status" className="w-52">
                        <option value="">Semua pesan</option>
                        <option value="new">Belum ditangani</option>
                        <option value="handled">Sudah ditangani</option>
                    </Select>
                </div>
                {items.data.length === 0 ? (
                    <p className="px-4 py-12 text-center text-sm text-[#5B6779]">Belum ada pesan.</p>
                ) : (
                    <ul className="divide-y divide-[#EEF1F5]">
                        {items.data.map((m) => (
                            <li key={m.id} className="px-5 py-4">
                                <div className="flex flex-wrap items-start justify-between gap-3">
                                    <div className="min-w-0">
                                        <p className="text-sm font-bold">
                                            {m.name}
                                            {m.subject ? <span className="font-normal text-[#5B6779]"> · {m.subject}</span> : null}
                                        </p>
                                        <p className="text-xs text-[#5B6779]">
                                            {[m.email, m.phone].filter(Boolean).join(' · ')} · {m.at}
                                        </p>
                                    </div>
                                    <Badge tone={m.status === 'new' ? 'amber' : 'green'}>{m.status === 'new' ? 'Baru' : 'Ditangani'}</Badge>
                                </div>
                                {/* Pesan dari publik ditampilkan sebagai teks biasa. */}
                                <p className="mt-2 whitespace-pre-line text-sm">{m.message}</p>
                                <div className="mt-3 flex flex-wrap items-center gap-2">
                                    {m.status === 'new' && (
                                        <Button size="sm" onClick={() => router.post(`/site/messages/${m.id}/handle`, {}, keep)}>
                                            Tandai ditangani
                                        </Button>
                                    )}
                                    {m.status === 'handled' && m.handled_by && (
                                        <span className="text-xs text-[#5B6779]">
                                            Oleh {m.handled_by}, {m.handled_at}
                                        </span>
                                    )}
                                    <Button size="sm" variant="danger" onClick={() => window.confirm('Hapus pesan ini?') && router.delete(`/site/messages/${m.id}`, keep)}>
                                        Hapus
                                    </Button>
                                </div>
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
