import { Head, Link, router, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Badge, Button, Card, Field, Select, inputCls } from '@/Components/ui';
import { rupiah } from '@/lib/format';

const keep = { preserveScroll: true };

function Files({ files }) {
    if (!files.length) return null;
    return (
        <ul className="mt-2 flex flex-wrap gap-2">
            {files.map((f) => (
                <li key={f.id}>
                    <a href={f.url} target="_blank" rel="noopener noreferrer" className="inline-flex rounded-md border border-[#CBD2DC] bg-white px-2.5 py-1 text-xs font-semibold hover:bg-[#F2F4F7]">
                        {f.name}
                    </a>
                </li>
            ))}
        </ul>
    );
}

function Stage({ title, who, at, children }) {
    return (
        <Card className="p-5">
            <div className="flex flex-wrap items-baseline justify-between gap-2">
                <h2 className="text-base font-bold">{title}</h2>
                {who && (
                    <span className="text-xs text-[#5B6779]">
                        {who}, {at}
                    </span>
                )}
            </div>
            <div className="mt-2 text-sm">{children}</div>
        </Card>
    );
}

export default function DisputeShow({ dispute: d, can }) {
    const clarify = useForm({ clarification: '', files: [] });
    const review = useForm({ note: '' });
    const decide = useForm({ decision: 'upheld', note: '' });

    const post = (action, form, extra = {}) =>
        form.post(`/finance/disputes/${d.id}/${action}`, { ...keep, ...extra });

    return (
        <AppLayout>
            <Head title={`Sanggahan #${d.id}`} />
            <div className="mb-6 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <Link href={`/finance/reports/${d.report.id}`} className="text-xs font-semibold text-[#5B6779] hover:underline">
                        Laporan {d.report.label}
                    </Link>
                    <h1 className="mt-1 text-2xl font-extrabold tracking-tight">Sanggahan #{d.id}</h1>
                </div>
                <Badge tone={['upheld'].includes(d.status) ? 'green' : ['open', 'clarified', 'reviewed'].includes(d.status) ? 'amber' : 'neutral'}>{d.status_label}</Badge>
            </div>

            <div className="max-w-3xl space-y-5">
                <Stage title="Pengajuan" who={d.filer} at={d.filed_at}>
                    {d.transaction && (
                        <p className="mb-2 rounded-md bg-[#F2F4F7] px-3 py-2 text-xs">
                            Transaksi:{' '}
                            <Link href={`/finance/transactions/${d.transaction.id}`} className="font-semibold hover:underline">
                                {d.transaction.reference}
                            </Link>{' '}
                            {d.transaction.description} ({rupiah(d.transaction.amount)})
                        </p>
                    )}
                    <p className="whitespace-pre-line">{d.reason}</p>
                    <Files files={d.filing_files} />
                </Stage>

                {d.clarification && (
                    <Stage title="Klarifikasi bendahara" who={d.clarified_by} at={d.clarified_at}>
                        <p className="whitespace-pre-line">{d.clarification}</p>
                        <Files files={d.clarification_files} />
                    </Stage>
                )}
                {d.review_note && (
                    <Stage title="Review" who={d.reviewed_by} at={d.reviewed_at}>
                        <p className="whitespace-pre-line">{d.review_note}</p>
                    </Stage>
                )}
                {d.decision_note && (
                    <Stage title="Keputusan akhir" who={d.decided_by} at={d.decided_at}>
                        <p className="whitespace-pre-line">{d.decision_note}</p>
                    </Stage>
                )}

                {can.clarify && (
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            post('clarify', clarify, { forceFormData: true });
                        }}
                        noValidate
                    >
                        <Card className="space-y-4 p-5">
                            <h2 className="text-base font-bold">Berikan klarifikasi</h2>
                            <Field label="Klarifikasi" htmlFor="cl" error={clarify.errors.clarification}>
                                <textarea id="cl" rows={4} value={clarify.data.clarification} onChange={(e) => clarify.setData('clarification', e.target.value)} className={inputCls} />
                            </Field>
                            <Field label="Bukti pendukung (opsional, maks. 3)" htmlFor="clf" error={clarify.errors.files || clarify.errors['files.0']}>
                                <input id="clf" type="file" multiple accept=".pdf,.jpg,.jpeg,.png" onChange={(e) => clarify.setData('files', Array.from(e.target.files).slice(0, 3))} className={inputCls} />
                            </Field>
                            <Button type="submit" disabled={clarify.processing}>
                                Kirim klarifikasi
                            </Button>
                        </Card>
                    </form>
                )}

                {can.review && (
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            post('review', review);
                        }}
                        noValidate
                    >
                        <Card className="space-y-4 p-5">
                            <h2 className="text-base font-bold">Review</h2>
                            <Field label="Catatan review" htmlFor="rv" error={review.errors.note}>
                                <textarea id="rv" rows={3} value={review.data.note} onChange={(e) => review.setData('note', e.target.value)} className={inputCls} />
                            </Field>
                            <Button type="submit" disabled={review.processing}>
                                Teruskan ke Ketua
                            </Button>
                        </Card>
                    </form>
                )}

                {can.decide && (
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            if (window.confirm('Keputusan akhir tidak dapat diubah. Lanjutkan?')) post('decide', decide);
                        }}
                        noValidate
                    >
                        <Card className="space-y-4 p-5">
                            <h2 className="text-base font-bold">Keputusan akhir</h2>
                            <Field label="Keputusan" htmlFor="dc" error={decide.errors.decision}>
                                <Select id="dc" value={decide.data.decision} onChange={(e) => decide.setData('decision', e.target.value)}>
                                    <option value="upheld">Sanggahan diterima (perlu koreksi)</option>
                                    <option value="rejected">Sanggahan ditolak</option>
                                </Select>
                            </Field>
                            <Field label="Alasan keputusan" htmlFor="dn" error={decide.errors.note}>
                                <textarea id="dn" rows={3} value={decide.data.note} onChange={(e) => decide.setData('note', e.target.value)} className={inputCls} />
                            </Field>
                            <Button type="submit" disabled={decide.processing}>
                                Putuskan
                            </Button>
                        </Card>
                    </form>
                )}

                {can.withdraw && (
                    <Button variant="secondary" onClick={() => window.confirm('Cabut sanggahan ini?') && router.post(`/finance/disputes/${d.id}/withdraw`, {}, keep)}>
                        Cabut sanggahan
                    </Button>
                )}
            </div>
        </AppLayout>
    );
}
