import { router, useForm } from '@inertiajs/react';
import { Badge, Button, Card, Field, Input, inputCls } from '@/Components/ui';

const tone = { draft: 'neutral', submitted: 'amber', revision: 'red', accepted: 'green' };

export default function ReportTab({ activity, report, can }) {
    const { data, setData, post, processing, errors } = useForm({
        _method: 'put',
        summary: report.summary ?? '',
        evaluation: report.evaluation ?? '',
        realized_participants: report.realized_participants ?? '',
        report_file: null,
    });
    const keep = { preserveScroll: true };
    const act = (path, payload = {}) => router.post(`/activities/${activity.id}/report/${path}`, payload, keep);

    return (
        <div className="space-y-6">
            <div className="flex flex-wrap items-center gap-3">
                <Badge tone={tone[report.status]}>{report.status_label}</Badge>
                {report.submitted_at && <span className="text-xs text-[#5B6779]">Diajukan {report.submitted_at}</span>}
                {report.reviewed_at && <span className="text-xs text-[#5B6779]">Direview {report.reviewed_at}</span>}
            </div>

            {report.status === 'revision' && report.review_note && (
                <div role="alert" className="rounded-md border border-[#E7B9BC] bg-[#FBEFF0] px-4 py-3 text-sm text-[#8F1B24]">
                    <p className="font-semibold">Catatan revisi</p>
                    <p className="mt-0.5">{report.review_note}</p>
                </div>
            )}

            {can.manage && report.editable ? (
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        post(`/activities/${activity.id}/report`, { forceFormData: true, preserveScroll: true });
                    }}
                    noValidate
                >
                    <Card className="space-y-5 p-6">
                        <Field label="Ringkasan pelaksanaan" htmlFor="r_sum" error={errors.summary}>
                            <textarea id="r_sum" rows={6} value={data.summary} onChange={(e) => setData('summary', e.target.value)} className={inputCls} />
                        </Field>
                        <Field label="Evaluasi dan catatan" htmlFor="r_eval" error={errors.evaluation} hint="Capaian, kendala, dan saran untuk kegiatan berikutnya.">
                            <textarea id="r_eval" rows={4} value={data.evaluation} onChange={(e) => setData('evaluation', e.target.value)} className={inputCls} />
                        </Field>
                        <Field label="Jumlah peserta sebenarnya" htmlFor="r_part" error={errors.realized_participants}>
                            <Input id="r_part" type="number" min="0" value={data.realized_participants} onChange={(e) => setData('realized_participants', e.target.value)} className="max-w-xs" />
                        </Field>
                        <Field
                            label="Berkas LPJ (opsional)"
                            htmlFor="r_file"
                            error={errors.report_file}
                            hint={report.has_file ? 'Sudah ada berkas. Unggah lagi untuk menggantinya.' : 'PDF, DOC, atau DOCX, maksimal 5 MB.'}
                        >
                            <input id="r_file" type="file" accept=".pdf,.doc,.docx" onChange={(e) => setData('report_file', e.target.files[0] ?? null)} className={inputCls} />
                        </Field>
                        <p className="text-xs text-[#5B6779]">Isi realisasi setiap pengeluaran di tab Anggaran sebelum mengajukan LPJ.</p>
                        <div className="flex flex-wrap gap-2">
                            <Button type="submit" disabled={processing} variant="secondary">
                                Simpan draf
                            </Button>
                            <Button onClick={() => window.confirm('Ajukan LPJ untuk direview? Setelah diajukan LPJ tidak dapat diubah.') && act('submit')}>Ajukan LPJ</Button>
                        </div>
                    </Card>
                </form>
            ) : (
                <Card className="space-y-4 p-6 text-sm">
                    <div>
                        <h3 className="text-xs font-semibold text-[#5B6779]">Ringkasan pelaksanaan</h3>
                        <p className="mt-1 whitespace-pre-line">{report.summary || <span className="text-[#8A94A4]">Belum diisi</span>}</p>
                    </div>
                    <div>
                        <h3 className="text-xs font-semibold text-[#5B6779]">Evaluasi dan catatan</h3>
                        <p className="mt-1 whitespace-pre-line">{report.evaluation || <span className="text-[#8A94A4]">Belum diisi</span>}</p>
                    </div>
                    <div>
                        <h3 className="text-xs font-semibold text-[#5B6779]">Peserta sebenarnya</h3>
                        <p className="mt-1 tabular-nums">{report.realized_participants ?? '-'}</p>
                    </div>
                    {report.status === 'accepted' && report.review_note && (
                        <p className="text-xs text-[#5B6779]">Catatan reviewer: {report.review_note}</p>
                    )}
                </Card>
            )}

            {report.has_file && (
                <a href={`/activities/${activity.id}/report/file`} className="inline-flex rounded-md border border-[#CBD2DC] bg-white px-3.5 py-2 text-sm font-semibold hover:bg-[#F2F4F7]">
                    Unduh berkas LPJ
                </a>
            )}

            {can.approve && report.status === 'submitted' && (
                <Card className="p-5">
                    <h2 className="text-base font-bold">Review LPJ</h2>
                    <p className="mt-1 text-sm text-[#5B6779]">Jika diterima, kegiatan berstatus Ditutup dan tidak dapat diubah lagi.</p>
                    <div className="mt-4 flex gap-2">
                        <Button onClick={() => window.confirm('Terima LPJ dan tutup kegiatan ini?') && act('review', { decision: 'accept' })}>Terima LPJ</Button>
                        <Button
                            variant="danger"
                            onClick={() => {
                                const note = window.prompt('Catatan revisi (wajib):');
                                if (note) act('review', { decision: 'revise', note });
                            }}
                        >
                            Minta revisi
                        </Button>
                    </div>
                </Card>
            )}
        </div>
    );
}
