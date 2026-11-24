import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Badge, Button, Card, PageHeader, Pagination, cx, inputCls } from '@/Components/ui';

const keep = { preserveScroll: true };

function Row({ type, item }) {
    const [summary, setSummary] = useState(item.summary ?? '');
    const send = (pub) => router.post(`/site/content/${type}/${item.id}`, { public: pub, summary: item.is_activity ? summary : undefined }, keep);

    return (
        <li className="px-5 py-4">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <div className="min-w-0">
                    <p className="text-sm font-bold">{item.title}</p>
                    <p className="text-xs text-[#5B6779]">{item.status}</p>
                </div>
                <div className="flex items-center gap-2">
                    {item.is_public ? <Badge tone="green">Tampil publik</Badge> : <Badge tone="neutral">Tertutup</Badge>}
                    {item.is_public ? (
                        <Button size="sm" variant="secondary" onClick={() => window.confirm('Tarik dari website publik?') && send(false)}>
                            Tarik
                        </Button>
                    ) : (
                        <Button size="sm" disabled={!item.eligible} onClick={() => window.confirm('Tampilkan di website publik? Siapa pun di internet dapat melihatnya.') && send(true)}>
                            Tampilkan
                        </Button>
                    )}
                </div>
            </div>
            {!item.eligible && item.why && <p className="mt-1 text-xs text-[#7A5206]">{item.why}</p>}
            {item.is_activity && (
                <div className="mt-3">
                    <label htmlFor={`s${item.id}`} className="text-xs font-semibold text-[#3B4658]">
                        Ringkasan publik (deskripsi internal tidak ditampilkan)
                    </label>
                    <textarea id={`s${item.id}`} rows={2} value={summary} onChange={(e) => setSummary(e.target.value)} className={`${inputCls} mt-1`} />
                    {item.is_public && (
                        <Button size="sm" variant="secondary" className="mt-2" onClick={() => send(true)}>
                            Simpan ringkasan
                        </Button>
                    )}
                </div>
            )}
        </li>
    );
}

export default function Content({ type, types, items }) {
    return (
        <AppLayout>
            <Head title="Kurasi Konten Publik" />
            <PageHeader title="Kurasi konten publik" description="Pilih secara sadar apa yang tampil di website. Semua konten tertutup secara bawaan; menarik album/berita dari status terbit otomatis mencabutnya dari publik." />

            <div role="tablist" aria-label="Jenis konten" className="mb-6 flex flex-wrap gap-1 border-b border-[#DDE2E9]">
                {Object.entries(types).map(([k, label]) => (
                    <Link key={k} href={`/site/content?type=${k}`} role="tab" aria-selected={type === k} className={cx('-mb-px border-b-2 px-4 py-2 text-sm font-semibold', type === k ? 'border-[#B3202A] text-[#16202E]' : 'border-transparent text-[#5B6779] hover:text-[#16202E]')}>
                        {label}
                    </Link>
                ))}
            </div>

            <Card>
                {items.data.length === 0 ? (
                    <p className="px-4 py-12 text-center text-sm text-[#5B6779]">Belum ada data.</p>
                ) : (
                    <ul className="divide-y divide-[#EEF1F5]">
                        {items.data.map((it) => (
                            <Row key={`${type}-${it.id}`} type={type} item={it} />
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
