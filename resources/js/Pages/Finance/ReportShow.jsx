import { Head, Link, router, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Badge, Button, Card, Field, Select, cx, inputCls } from '@/Components/ui';
import { rupiah } from '@/lib/format';
import { reportTone } from './Index';

const keep = { preserveScroll: true };

function TxTable({ title, rows, total }) {
    return (
        <Card className="overflow-x-auto">
            <div className="flex items-center justify-between border-b border-[#DDE2E9] px-4 py-3">
                <h2 className="text-base font-bold">{title}</h2>
                <span className="text-sm font-bold tabular-nums">{rupiah(total)}</span>
            </div>
            {rows.length === 0 ? (
                <p className="px-4 py-8 text-center text-sm text-[#5B6779]">Tidak ada transaksi.</p>
            ) : (
                <table className="w-full min-w-[680px] text-left text-sm">
                    <tbody className="divide-y divide-[#EEF1F5]">
                        {rows.map((t) => (
                            <tr key={t.id}>
                                <td className="whitespace-nowrap px-4 py-3 align-top tabular-nums text-[#5B6779]">{t.date}</td>
                                <td className="px-3 py-3 align-top">
                                    <Link href={`/finance/transactions/${t.id}`} className="font-semibold hover:underline">
                                        {t.description}
                                    </Link>
                                    <div className="text-xs text-[#5B6779]">
                                        <span className="tabular-nums">{t.reference}</span> · {t.category}
                                        {t.counterparty ? ` · ${t.counterparty}` : ''}
                                        {t.activity ? ` · ${t.activity}` : ''}
                                    </div>
                                </td>
                                <td className="px-3 py-3 align-top">
                                    {t.has_proof ? <Badge tone="green">Ada bukti</Badge> : t.type === 'expense' ? <Badge tone="amber">{t.proof_note ? 'Tanpa bukti (beralasan)' : 'Belum ada bukti'}</Badge> : null}
                                </td>
                                <td className="px-3 py-3 text-right align-top font-semibold tabular-nums">{rupiah(t.amount)}</td>
                                <td className="px-4 py-3 align-top">
                                    {t.can_edit && (
                                        <div className="flex justify-end gap-1.5">
                                            <Button size="sm" variant="secondary" href={`/finance/transactions/${t.id}/edit`}>
                                                Ubah
                                            </Button>
                                            <Button size="sm" variant="danger" onClick={() => window.confirm(`Hapus ${t.reference}?`) && router.delete(`/finance/transactions/${t.id}`, keep)}>
                                                Hapus
                                            </Button>
                                        </div>
                                    )}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            )}
        </Card>
    );
}

function DisputeForm({ report, transactions }) {
    const f = useForm({ transaction_id: '', reason: '', files: [] });
    return (
        <form
            onSubmit={(e) => {
                e.preventDefault();
                f.post(`/finance/reports/${report.id}/disputes`, { forceFormData: true, preserveScroll: true });
            }}
            noValidate
            className="border-t border-[#DDE2E9] bg-[#F7F8FA] p-5"
        >
            <h3 className="text-sm font-bold">Ajukan sanggahan</h3>
            <p className="mt-1 text-xs text-[#5B6779]">Sanggahan akan diklarifikasi bendahara, direview Wakil Ketua, dan diputuskan Ketua.</p>
            <div className="mt-3 space-y-4">
                <Field label="Transaksi yang disanggah (opsional)" htmlFor="d_tx" error={f.errors.transaction_id}>
                    <Select id="d_tx" value={f.data.transaction_id} onChange={(e) => f.setData('transaction_id', e.target.value)}>
                        <option value="">Laporan secara keseluruhan</option>
                        {transactions.map((t) => (
                            <option key={t.id} value={t.id}>
                                {t.reference} {t.description} ({rupiah(t.amount)})
                            </option>
                        ))}
                    </Select>
                </Field>
                <Field label="Alasan" htmlFor="d_reason" error={f.errors.reason} hint="Minimal 20 karakter. Jelaskan apa yang tidak sesuai.">
                    <textarea id="d_reason" rows={4} value={f.data.reason} onChange={(e) => f.setData('reason', e.target.value)} className={inputCls} />
                </Field>
                <Field label="Bukti pendukung (opsional, maks. 3 berkas)" htmlFor="d_files" error={f.errors.files || f.errors['files.0']} hint="PDF, JPG, atau PNG, maks. 5 MB per berkas.">
                    <input id="d_files" type="file" multiple accept=".pdf,.jpg,.jpeg,.png" onChange={(e) => f.setData('files', Array.from(e.target.files).slice(0, 3))} className={inputCls} />
                </Field>
            </div>
            <Button type="submit" className="mt-4" disabled={f.processing}>
                Kirim sanggahan
            </Button>
        </form>
    );
}

export default function ReportShow({ report, workflow, events, transactions, disputes, can, separation }) {
    const post = (action, data = {}) => router.post(`/finance/reports/${report.id}/${action}`, data, keep);
    const ask = (msg, fn) => window.confirm(msg) && fn();
    const askNote = (label, fn) => {
        const note = window.prompt(label);
        if (note) fn(note);
    };

    const income = transactions.filter((t) => t.type === 'income');
    const expense = transactions.filter((t) => t.type === 'expense');
    const t = report.totals;

    return (
        <AppLayout>
            <Head title={`Laporan ${report.label}`} />

            <div className="mb-6 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <Link href="/finance" className="text-xs font-semibold text-[#5B6779] hover:underline">
                        Laporan Keuangan
                    </Link>
                    <h1 className="mt-1 text-2xl font-extrabold tracking-tight">Laporan {report.label}</h1>
                    {report.published_at && (
                        <p className="mt-1 text-sm text-[#5B6779]">
                            Dipublikasikan {report.published_at}
                            {report.published_by ? ` oleh ${report.published_by}` : ''}
                        </p>
                    )}
                </div>
                <Badge tone={reportTone[report.status]}>{report.status_label}</Badge>
            </div>

            <dl className="mb-6 grid divide-y divide-[#DDE2E9] overflow-hidden rounded-lg border border-[#DDE2E9] bg-white sm:grid-cols-4 sm:divide-x sm:divide-y-0">
                {[
                    ['Saldo awal', t.opening],
                    ['Kas masuk', t.income],
                    ['Kas keluar', t.expense],
                    ['Saldo akhir', t.closing],
                ].map(([label, value], i) => (
                    <div key={label} className="px-5 py-4">
                        <dt className="text-sm text-[#5B6779]">{label}</dt>
                        <dd className={cx('mt-1 text-xl font-extrabold tabular-nums', i === 3 && value < 0 && 'text-[#B3202A]')}>{rupiah(value)}</dd>
                    </div>
                ))}
            </dl>

            {(can.submit || can.review || can.approve || can.publish) && (
                <Card className="mb-6 p-5">
                    <h2 className="text-base font-bold">Tindakan</h2>
                    <div className="mt-3 flex flex-wrap gap-2">
                        {can.record && (
                            <>
                                <Button variant="secondary" href="/finance/transactions/create?type=income">
                                    Catat kas masuk
                                </Button>
                                <Button variant="secondary" href="/finance/transactions/create?type=expense">
                                    Catat kas keluar
                                </Button>
                            </>
                        )}
                        {can.submit && <Button onClick={() => ask('Ajukan laporan untuk direview? Transaksi akan terkunci dan saldo dibekukan.', () => post('submit'))}>Ajukan laporan</Button>}
                        {can.review && (
                            <>
                                <Button onClick={() => ask('Teruskan laporan ke Ketua untuk persetujuan?', () => post('review', { decision: 'forward' }))}>Teruskan ke Ketua</Button>
                                <Button variant="danger" onClick={() => askNote('Catatan pengembalian (wajib):', (note) => post('review', { decision: 'return', note }))}>
                                    Kembalikan ke bendahara
                                </Button>
                            </>
                        )}
                        {can.approve && (
                            <>
                                <Button onClick={() => ask('Setujui laporan ini?', () => post('approve', { decision: 'approve' }))}>Setujui</Button>
                                <Button variant="danger" onClick={() => askNote('Catatan pengembalian (wajib):', (note) => post('approve', { decision: 'return', note }))}>
                                    Kembalikan ke bendahara
                                </Button>
                            </>
                        )}
                        {can.publish && (
                            <Button onClick={() => ask('Publikasikan? Semua anggota yang login dapat melihat laporan dan bukti transaksinya. Tindakan ini final.', () => post('publish'))}>Publikasikan laporan</Button>
                        )}
                    </div>
                    {separation && <p className="mt-3 text-xs text-[#5B6779]">Pemisahan tugas aktif: pengaju, pereview, dan penyetuju harus orang yang berbeda.</p>}
                </Card>
            )}

            {workflow && report.status === 'revision' && (workflow.review_note || workflow.approval_note) && (
                <div role="alert" className="mb-6 rounded-md border border-[#E7B9BC] bg-[#FBEFF0] px-4 py-3 text-sm text-[#8F1B24]">
                    <p className="font-semibold">Laporan dikembalikan untuk diperbaiki</p>
                    <p className="mt-0.5">{workflow.approval_note || workflow.review_note}</p>
                </div>
            )}

            <div className="space-y-6">
                <TxTable title="Kas masuk" rows={income} total={t.income} />
                <TxTable title="Kas keluar" rows={expense} total={t.expense} />
            </div>

            <Card className="mt-6">
                <h2 className="border-b border-[#DDE2E9] px-4 py-3 text-base font-bold">Sanggahan</h2>
                {disputes.length === 0 ? (
                    <p className="px-4 py-6 text-sm text-[#5B6779]">{can.internal ? 'Belum ada sanggahan.' : 'Anda belum mengajukan sanggahan pada laporan ini.'}</p>
                ) : (
                    <ul className="divide-y divide-[#EEF1F5]">
                        {disputes.map((d) => (
                            <li key={d.id}>
                                <Link href={`/finance/disputes/${d.id}`} className="flex flex-wrap items-start justify-between gap-2 px-4 py-3 hover:bg-[#F7F8FA]">
                                    <span>
                                        <span className="block text-sm font-semibold">{d.reason}</span>
                                        <span className="block text-xs text-[#5B6779]">
                                            {d.filer} · {d.created_at}
                                            {d.transaction ? ` · ${d.transaction}` : ''}
                                        </span>
                                    </span>
                                    <Badge tone="neutral">{d.status_label}</Badge>
                                </Link>
                            </li>
                        ))}
                    </ul>
                )}
                {can.dispute && <DisputeForm report={report} transactions={transactions} />}
            </Card>

            {can.internal && (
                <Card className="mt-6">
                    <h2 className="border-b border-[#DDE2E9] px-4 py-3 text-base font-bold">Riwayat persetujuan</h2>
                    {events.length === 0 ? (
                        <p className="px-4 py-6 text-sm text-[#5B6779]">Belum ada riwayat.</p>
                    ) : (
                        <ol className="divide-y divide-[#EEF1F5]">
                            {events.map((e) => (
                                <li key={e.id} className="px-4 py-3">
                                    <p className="text-sm font-semibold">{e.action}</p>
                                    <p className="text-xs text-[#5B6779]">
                                        {e.user} · {e.at}
                                    </p>
                                    {e.note && <p className="mt-1 text-sm">{e.note}</p>}
                                </li>
                            ))}
                        </ol>
                    )}
                    {workflow && (
                        <dl className="grid gap-3 border-t border-[#DDE2E9] bg-[#F7F8FA] px-4 py-3 text-xs sm:grid-cols-3">
                            <div>
                                <dt className="text-[#5B6779]">Pengaju</dt>
                                <dd className="font-semibold">{workflow.submitted_by ?? '-'}</dd>
                            </div>
                            <div>
                                <dt className="text-[#5B6779]">Reviewer</dt>
                                <dd className="font-semibold">{workflow.reviewed_by ?? '-'}</dd>
                            </div>
                            <div>
                                <dt className="text-[#5B6779]">Penyetuju</dt>
                                <dd className="font-semibold">{workflow.approved_by ?? '-'}</dd>
                            </div>
                        </dl>
                    )}
                </Card>
            )}
        </AppLayout>
    );
}
