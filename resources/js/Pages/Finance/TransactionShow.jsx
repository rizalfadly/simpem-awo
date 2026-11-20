import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Badge, Button, Card } from '@/Components/ui';
import { rupiah } from '@/lib/format';

const fmt = (v) => (v === null || v === undefined ? '-' : typeof v === 'object' ? JSON.stringify(v) : String(v));

function Row({ label, children }) {
    return (
        <div>
            <dt className="text-xs text-[#5B6779]">{label}</dt>
            <dd className="mt-0.5 text-sm font-semibold">{children || <span className="font-normal text-[#8A94A4]">-</span>}</dd>
        </div>
    );
}

export default function TransactionShow({ transaction: t, history, can }) {
    return (
        <AppLayout>
            <Head title={t.reference} />
            <div className="mb-6 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <Link href={`/finance/reports/${t.report.id}`} className="text-xs font-semibold text-[#5B6779] hover:underline">
                        Laporan {t.report.label} ({t.report.status_label})
                    </Link>
                    <h1 className="mt-1 text-2xl font-extrabold tracking-tight">{t.description}</h1>
                    <p className="mt-1 text-sm tabular-nums text-[#5B6779]">{t.reference}</p>
                </div>
                {can.edit && (
                    <Button variant="secondary" href={`/finance/transactions/${t.id}/edit`}>
                        Ubah
                    </Button>
                )}
            </div>

            <div className="grid gap-6 lg:grid-cols-3">
                <Card className="p-5 lg:col-span-2">
                    <dl className="grid gap-x-6 gap-y-4 sm:grid-cols-2">
                        <Row label="Jenis">
                            <Badge tone={t.type === 'income' ? 'green' : 'amber'}>{t.type_label}</Badge>
                        </Row>
                        <Row label="Nominal">
                            <span className="text-lg tabular-nums">{rupiah(t.amount)}</span>
                        </Row>
                        <Row label="Tanggal">{t.date}</Row>
                        <Row label="Kategori">{t.category}</Row>
                        <Row label={t.type === 'expense' ? 'Penerima' : 'Sumber dana'}>{t.counterparty}</Row>
                        <Row label="Dicatat oleh">{t.recorder}</Row>
                        <Row label="Kegiatan">
                            {t.activity && (
                                <Link href={`/activities/${t.activity.id}`} className="text-[#B3202A] hover:underline">
                                    {t.activity.title}
                                </Link>
                            )}
                        </Row>
                        {t.proof_note && <Row label="Alasan tanpa bukti">{t.proof_note}</Row>}
                    </dl>
                </Card>

                <Card className="p-5">
                    <h2 className="text-base font-bold">Bukti transaksi</h2>
                    {t.proof_url ? (
                        <div className="mt-3">
                            {t.proof_is_image ? (
                                <a href={t.proof_url} target="_blank" rel="noopener noreferrer">
                                    <img src={t.proof_url} alt={`Bukti ${t.reference}`} className="max-h-72 w-full rounded-md border border-[#DDE2E9] object-contain" />
                                </a>
                            ) : (
                                <a href={t.proof_url} target="_blank" rel="noopener noreferrer" className="inline-flex rounded-md border border-[#CBD2DC] px-3.5 py-2 text-sm font-semibold hover:bg-[#F2F4F7]">
                                    Buka {t.proof_name ?? 'berkas'}
                                </a>
                            )}
                        </div>
                    ) : (
                        <p className="mt-3 text-sm text-[#5B6779]">Tidak ada berkas bukti.</p>
                    )}
                </Card>
            </div>

            {history.length > 0 && (
                <Card className="mt-6">
                    <h2 className="border-b border-[#DDE2E9] px-4 py-3 text-base font-bold">Riwayat perubahan</h2>
                    <ol className="divide-y divide-[#EEF1F5]">
                        {history.map((h) => (
                            <li key={h.id} className="px-4 py-3 text-sm">
                                <p className="font-semibold">
                                    {{ created: 'Dicatat', updated: 'Diubah', deleted: 'Dihapus' }[h.event] ?? h.event}
                                    <span className="font-normal text-[#5B6779]">
                                        {' '}
                                        oleh {h.user}, {h.at}
                                    </span>
                                </p>
                                {h.new && h.event === 'updated' && (
                                    <ul className="mt-1 space-y-0.5 text-xs text-[#3B4658]">
                                        {Object.keys(h.new).map((k) => (
                                            <li key={k}>
                                                <span className="font-semibold">{k}</span>: {fmt(h.old?.[k])} <span aria-hidden="true">→</span> {fmt(h.new[k])}
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </li>
                        ))}
                    </ol>
                </Card>
            )}
        </AppLayout>
    );
}
