import { useForm } from '@inertiajs/react';
import { Button, Card, Field, Input, inputCls } from '@/Components/ui';

function Block({ label, children }) {
    return (
        <div>
            <h3 className="text-xs font-semibold text-[#5B6779]">{label}</h3>
            <p className="mt-1 whitespace-pre-line text-sm">{children || <span className="text-[#8A94A4]">Belum diisi</span>}</p>
        </div>
    );
}

export default function ProposalTab({ activity, proposal, can }) {
    const { data, setData, post, processing, errors } = useForm({
        _method: 'put',
        proposal_background: proposal.background ?? '',
        proposal_objectives: proposal.objectives ?? '',
        proposal_target_audience: proposal.target_audience ?? '',
        proposal_target_participants: proposal.target_participants ?? '',
        proposal_file: null,
    });
    const set = (k) => (e) => setData(k, e.target.value);

    return (
        <div className="space-y-6">
            {can.edit_proposal ? (
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        post(`/activities/${activity.id}/proposal`, { forceFormData: true, preserveScroll: true });
                    }}
                    noValidate
                >
                    <Card className="space-y-5 p-6">
                        <Field label="Latar belakang" htmlFor="bg" error={errors.proposal_background}>
                            <textarea id="bg" rows={5} value={data.proposal_background} onChange={set('proposal_background')} className={inputCls} />
                        </Field>
                        <Field label="Tujuan kegiatan" htmlFor="obj" error={errors.proposal_objectives}>
                            <textarea id="obj" rows={4} value={data.proposal_objectives} onChange={set('proposal_objectives')} className={inputCls} />
                        </Field>
                        <div className="grid gap-5 sm:grid-cols-2">
                            <Field label="Sasaran peserta" htmlFor="aud" error={errors.proposal_target_audience}>
                                <Input id="aud" value={data.proposal_target_audience} onChange={set('proposal_target_audience')} />
                            </Field>
                            <Field label="Target jumlah peserta" htmlFor="tp" error={errors.proposal_target_participants}>
                                <Input id="tp" type="number" min="1" value={data.proposal_target_participants} onChange={set('proposal_target_participants')} />
                            </Field>
                        </div>
                        <Field
                            label="Berkas proposal (opsional)"
                            htmlFor="pf"
                            error={errors.proposal_file}
                            hint={proposal.has_file ? 'Sudah ada berkas. Unggah lagi untuk menggantinya. PDF/DOC/DOCX, maks. 5 MB.' : 'PDF, DOC, atau DOCX, maksimal 5 MB.'}
                        >
                            <input id="pf" type="file" accept=".pdf,.doc,.docx" onChange={(e) => setData('proposal_file', e.target.files[0] ?? null)} className={inputCls} />
                        </Field>
                        <Button type="submit" disabled={processing}>
                            Simpan proposal
                        </Button>
                    </Card>
                </form>
            ) : (
                <Card className="space-y-5 p-6">
                    <Block label="Latar belakang">{proposal.background}</Block>
                    <Block label="Tujuan kegiatan">{proposal.objectives}</Block>
                    <div className="grid gap-5 sm:grid-cols-2">
                        <Block label="Sasaran peserta">{proposal.target_audience}</Block>
                        <Block label="Target jumlah peserta">{proposal.target_participants}</Block>
                    </div>
                    {proposal.submitted_at && <p className="text-xs text-[#5B6779]">Diajukan: {proposal.submitted_at}</p>}
                </Card>
            )}

            {proposal.has_file && (
                <a href={`/activities/${activity.id}/proposal/file`} className="inline-flex rounded-md border border-[#CBD2DC] bg-white px-3.5 py-2 text-sm font-semibold hover:bg-[#F2F4F7]">
                    Unduh berkas proposal
                </a>
            )}
        </div>
    );
}
