import { Head, Link, router, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Badge, Button, Card, inputCls } from '@/Components/ui';
import { loanTone } from './Index';

const keep = { preserveScroll: true };

function Row({ label, children }) {
    return (
        <div>
            <dt className="text-xs text-[#5B6779]">{label}</dt>
            <dd className="mt-0.5 text-sm font-semibold">{children || <span className="font-normal text-[#8A94A4]">-</span>}</dd>
        </div>
    );
}

function ReturnForm({ loan }) {
    const f = useForm({
        items: loan.items.map((i) => ({ id: i.id, good: i.quantity, minor: 0, broken: 0, lost: 0 })),
        note: '',
    });
    const setField = (idx, key, value) => f.setData('items', f.data.items.map((it, i) => (i === idx ? { ...it, [key]: value === '' ? '' : Number(value) } : it)));
    const sum = (it) => ['good', 'minor', 'broken', 'lost'].reduce((s, k) => s + (Number(it[k]) || 0), 0);
    const valid = loan.items.every((i, idx) => sum(f.data.items[idx]) === i.quantity);

    return (
        <form
            onSubmit={(e) => {
                e.preventDefault();
                f.post(`/inventory/loans/${loan.id}/return`, keep);
            }}
            noValidate
        >
            <Card className="p-5">
                <h2 className="text-base font-bold">Terima pengembalian</h2>
                <p className="mt-1 text-xs text-[#5B6779]">Catat kondisi tiap barang saat kembali. Jumlah semua kondisi harus sama dengan jumlah yang dipinjam.</p>
                {f.errors.items && (
                    <p role="alert" className="mt-3 rounded-md bg-[#FBEFF0] px-3 py-2 text-sm font-medium text-[#8F1B24]">
                        {f.errors.items}
                    </p>
                )}
                <div className="mt-4 space-y-4">
                    {loan.items.map((i, idx) => {
                        const it = f.data.items[idx];
                        const ok = sum(it) === i.quantity;
                        return (
                            <fieldset key={i.id} className="rounded-md border border-[#DDE2E9] p-3">
                                <legend className="px-1 text-sm font-semibold">
                                    {i.asset.name} <span className="font-normal text-[#5B6779]">(dipinjam {i.quantity} {i.asset.unit})</span>
                                </legend>
                                <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                    {[
                                        ['good', 'Baik'],
                                        ['minor', 'Rusak ringan'],
                                        ['broken', 'Rusak berat'],
                                        ['lost', 'Hilang'],
                                    ].map(([k, label]) => (
                                        <label key={k} className="text-xs font-semibold text-[#3B4658]">
                                            {label}
                                            <input type="number" min="0" value={it[k]} onChange={(e) => setField(idx, k, e.target.value)} className={`${inputCls} mt-1 text-right tabular-nums`} />
                                        </label>
                                    ))}
                                </div>
                                {!ok && <p className="mt-2 text-xs font-medium text-[#B3202A]">Jumlah saat ini {sum(it)}, seharusnya {i.quantity}.</p>}
                            </fieldset>
                        );
                    })}
                </div>
                <label className="mt-4 block text-xs font-semibold text-[#3B4658]">
                    Catatan pengembalian (opsional)
                    <textarea rows={2} value={f.data.note} onChange={(e) => f.setData('note', e.target.value)} className={`${inputCls} mt-1`} />
                </label>
                <Button type="submit" className="mt-4" disabled={f.processing || !valid}>
                    Catat pengembalian
                </Button>
            </Card>
        </form>
    );
}

export default function Show({ loan, can, separation }) {
    const post = (action, data = {}) => router.post(`/inventory/loans/${loan.id}/${action}`, data, keep);
    const ask = (msg, fn) => window.confirm(msg) && fn();

    return (
        <AppLayout>
            <Head title={loan.code} />

            <div className="mb-6 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <Link href="/inventory/loans" className="text-xs font-semibold text-[#5B6779] hover:underline">
                        Peminjaman
                    </Link>
                    <h1 className="mt-1 text-2xl font-extrabold tracking-tight tabular-nums">{loan.code}</h1>
                    <p className="mt-1 text-sm text-[#5B6779]">
                        {loan.borrower} ({loan.borrower_number})
                    </p>
                </div>
                <div className="flex flex-col items-end gap-1">
                    <Badge tone={loanTone[loan.status]}>{loan.status_label}</Badge>
                    {loan.overdue && <Badge tone="red">Terlambat {loan.late_days} hari</Badge>}
                    {loan.status === 'returned' && loan.late_days > 0 && <Badge tone="amber">Dikembalikan telat {loan.late_days} hari</Badge>}
                </div>
            </div>

            {(can.approve || can.handover || can.cancel) && (
                <Card className="mb-6 p-5">
                    <h2 className="text-base font-bold">Tindakan</h2>
                    <div className="mt-3 flex flex-wrap gap-2">
                        {can.approve && <Button onClick={() => ask('Setujui peminjaman ini? Stok akan dipesan untuk tanggal tersebut.', () => post('approve'))}>Setujui</Button>}
                        {can.reject && (
                            <Button
                                variant="danger"
                                onClick={() => {
                                    const reason = window.prompt('Alasan penolakan (wajib):');
                                    if (reason) post('reject', { reason });
                                }}
                            >
                                Tolak
                            </Button>
                        )}
                        {can.handover && <Button onClick={() => ask('Serahkan barang ke peminjam sekarang?', () => post('handover'))}>Serahkan barang</Button>}
                        {can.cancel && (
                            <Button
                                variant="secondary"
                                onClick={() => {
                                    const reason = window.prompt('Alasan pembatalan (boleh kosong):');
                                    if (reason !== null) post('cancel', { reason });
                                }}
                            >
                                Batalkan
                            </Button>
                        )}
                    </div>
                    {can.approve && separation && <p className="mt-3 text-xs text-[#5B6779]">Pemisahan tugas aktif: pengelola tidak dapat menyetujui peminjaman atas namanya sendiri.</p>}
                </Card>
            )}

            {loan.status_note && (
                <div className="mb-6 rounded-md border border-[#DDE2E9] bg-white px-4 py-3 text-sm">
                    <p className="font-semibold">{loan.status === 'rejected' ? 'Alasan penolakan' : 'Alasan pembatalan'}</p>
                    <p className="mt-0.5 text-[#3B4658]">{loan.status_note}</p>
                </div>
            )}

            <div className="grid gap-6 lg:grid-cols-3">
                <Card className="p-5 lg:col-span-2">
                    <dl className="grid gap-x-6 gap-y-4 sm:grid-cols-2">
                        <Row label="Tanggal pinjam">{loan.loan_date}</Row>
                        <Row label="Tanggal kembali">{loan.due_date}</Row>
                        <div className="sm:col-span-2">
                            <Row label="Tujuan">{loan.purpose}</Row>
                        </div>
                        <Row label="Kegiatan">
                            {loan.activity && (
                                <Link href={`/activities/${loan.activity.id}`} className="text-[#B3202A] hover:underline">
                                    {loan.activity.title}
                                </Link>
                            )}
                        </Row>
                        <Row label="Catatan">{loan.notes}</Row>
                    </dl>
                </Card>

                <Card className="self-start p-5">
                    <h2 className="text-base font-bold">Jejak</h2>
                    <ol className="mt-3 space-y-2.5 text-xs">
                        <li>
                            <span className="font-semibold">Diajukan</span> oleh {loan.requested_by ?? '-'}, {loan.requested_at}
                        </li>
                        {loan.approved_at && (
                            <li>
                                <span className="font-semibold">Disetujui</span> oleh {loan.approved_by}, {loan.approved_at}
                            </li>
                        )}
                        {loan.lent_at && (
                            <li>
                                <span className="font-semibold">Diserahkan</span> oleh {loan.handed_by}, {loan.lent_at}
                            </li>
                        )}
                        {loan.returned_at && (
                            <li>
                                <span className="font-semibold">Dikembalikan</span>, diterima {loan.received_by}, {loan.returned_at}
                                {loan.return_note && <span className="block text-[#5B6779]">{loan.return_note}</span>}
                            </li>
                        )}
                    </ol>
                </Card>
            </div>

            <Card className="mt-6 overflow-x-auto">
                <h2 className="border-b border-[#DDE2E9] px-4 py-3 text-base font-bold">Barang</h2>
                <table className="w-full min-w-[520px] text-left text-sm">
                    <tbody className="divide-y divide-[#EEF1F5]">
                        {loan.items.map((i) => (
                            <tr key={i.id}>
                                <td className="px-4 py-3">
                                    <Link href={`/inventory/${i.asset.id}`} className="font-semibold hover:underline">
                                        {i.asset.name}
                                    </Link>
                                    <div className="text-xs tabular-nums text-[#5B6779]">{i.asset.code}</div>
                                </td>
                                <td className="px-3 py-3 tabular-nums">
                                    {i.quantity} {i.asset.unit}
                                </td>
                                <td className="px-4 py-3 text-xs text-[#5B6779]">
                                    {i.returned ? `Kembali: baik ${i.returned.good}, ringan ${i.returned.minor}, berat ${i.returned.broken}, hilang ${i.returned.lost}` : ''}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </Card>

            {can.return && (
                <div className="mt-6">
                    <ReturnForm loan={loan} />
                </div>
            )}
        </AppLayout>
    );
}
