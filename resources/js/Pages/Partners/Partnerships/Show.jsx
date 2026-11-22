import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Badge, Button, Card } from '@/Components/ui';
import { rupiah } from '@/lib/format';
import { partnershipTone } from './Index';

const keep = { preserveScroll: true };

function Row({ label, children }) {
    return (
        <div>
            <dt className="text-xs text-[#5B6779]">{label}</dt>
            <dd className="mt-0.5 text-sm font-semibold">{children || <span className="font-normal text-[#8A94A4]">-</span>}</dd>
        </div>
    );
}

export default function Show({ item: p, can }) {
    const setStatus = (status, note) => router.post(`/partners/partnerships/${p.id}/status`, { status, note }, keep);

    return (
        <AppLayout>
            <Head title={p.title} />
            <div className="mb-6 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <Link href="/partners/partnerships" className="text-xs font-semibold text-[#5B6779] hover:underline">
                        Kerja sama
                    </Link>
                    <h1 className="mt-1 text-2xl font-extrabold tracking-tight">{p.title}</h1>
                    <p className="mt-1 text-sm text-[#5B6779]">
                        <Link href={`/partners/${p.partner.id}`} className="font-semibold text-[#B3202A] hover:underline">
                            {p.partner.name}
                        </Link>{' '}
                        · {p.type_label}
                    </p>
                </div>
                <div className="flex flex-col items-end gap-1">
                    <Badge tone={partnershipTone[p.status]}>{p.status_label}</Badge>
                    {p.lapsed && <Badge tone="red">Lewat tanggal berakhir</Badge>}
                    {p.expiring && <Badge tone="amber">Segera berakhir</Badge>}
                </div>
            </div>

            {can.manage && (
                <Card className="mb-6 p-5">
                    <h2 className="text-base font-bold">Tindakan</h2>
                    <div className="mt-3 flex flex-wrap gap-2">
                        {can.edit && (
                            <Button variant="secondary" href={`/partners/partnerships/${p.id}/edit`}>
                                Ubah
                            </Button>
                        )}
                        {can.activate && <Button onClick={() => window.confirm('Aktifkan kerja sama ini?') && setStatus('active')}>Aktifkan</Button>}
                        {can.finish && (
                            <>
                                <Button onClick={() => window.confirm('Tandai kerja sama selesai?') && setStatus('completed')}>Tandai selesai</Button>
                                <Button
                                    variant="danger"
                                    onClick={() => {
                                        const note = window.prompt('Alasan penghentian (wajib):');
                                        if (note) setStatus('terminated', note);
                                    }}
                                >
                                    Hentikan
                                </Button>
                            </>
                        )}
                        {can.delete && (
                            <Button variant="danger" onClick={() => window.confirm('Hapus draf kerja sama ini?') && router.delete(`/partners/partnerships/${p.id}`)}>
                                Hapus
                            </Button>
                        )}
                    </div>
                </Card>
            )}

            {p.status_note && (
                <div className="mb-6 rounded-md border border-[#DDE2E9] bg-white px-4 py-3 text-sm">
                    <p className="font-semibold">Catatan status</p>
                    <p className="mt-0.5 text-[#3B4658]">{p.status_note}</p>
                </div>
            )}

            <Card className="p-5">
                {p.description && <p className="mb-5 whitespace-pre-line text-sm">{p.description}</p>}
                <dl className="grid gap-x-6 gap-y-4 sm:grid-cols-2">
                    <Row label="Mulai">{p.starts_on}</Row>
                    <Row label="Berakhir">{p.ends_on}</Row>
                    <Row label="Ditandatangani">{p.signed_on}</Row>
                    <Row label="Nilai">{p.value !== null ? rupiah(p.value) : null}</Row>
                    <Row label="PIC dari AWO">{p.pic}</Row>
                    <Row label="Kegiatan terkait">
                        {p.activity && (
                            <Link href={`/activities/${p.activity.id}`} className="text-[#B3202A] hover:underline">
                                {p.activity.title}
                            </Link>
                        )}
                    </Row>
                    <Row label="Naskah perjanjian">
                        {p.document && (
                            <a href={p.document.url} target="_blank" rel="noopener noreferrer" className="text-[#B3202A] hover:underline">
                                {p.document.name}
                            </a>
                        )}
                    </Row>
                </dl>
            </Card>
        </AppLayout>
    );
}
