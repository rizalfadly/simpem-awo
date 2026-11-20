import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Badge, Button, Card, cx } from '@/Components/ui';
import { statusTone } from '@/lib/format';
import ProposalTab from '@/Components/activity/ProposalTab';
import CommitteeTab from '@/Components/activity/CommitteeTab';
import TimelineTab from '@/Components/activity/TimelineTab';
import BudgetTab from '@/Components/activity/BudgetTab';
import ReportTab from '@/Components/activity/ReportTab';
import PublicationsTab from '@/Components/activity/PublicationsTab';

function Row({ label, children }) {
    return (
        <div>
            <dt className="text-xs text-[#5B6779]">{label}</dt>
            <dd className="mt-0.5 text-sm font-semibold">{children || <span className="font-normal text-[#8A94A4]">-</span>}</dd>
        </div>
    );
}

export default function Show({ activity, can, committee, timelines, proposal, budget, report, options, publications }) {
    const [tab, setTab] = useState('summary');
    const keep = { preserveScroll: true };
    const post = (path, data = {}) => router.post(`/activities/${activity.id}/${path}`, data, keep);
    const confirmThen = (msg, fn) => window.confirm(msg) && fn();

    const tabs = [
        ['summary', 'Ringkasan'],
        can.full && ['proposal', 'Proposal'],
        ['committee', `Panitia (${committee.length})`],
        ['timeline', 'Timeline'],
        can.full && budget && ['budget', 'Anggaran'],
        can.full && report && ['report', 'LPJ'],
        ['docs', 'Dokumentasi'],
    ].filter(Boolean);

    const s = activity.status;

    return (
        <AppLayout>
            <Head title={activity.title} />

            <div className="mb-6">
                <p className="text-sm tabular-nums text-[#5B6779]">{activity.code}</p>
                <div className="mt-1 flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-extrabold tracking-tight">{activity.title}</h1>
                        <p className="mt-1 text-sm text-[#5B6779]">
                            <span className="tabular-nums">{activity.when}</span>
                            {activity.location ? `, ${activity.location}` : ''}
                        </p>
                    </div>
                    <Badge tone={statusTone(s)}>{activity.status_label}</Badge>
                </div>

                <div className="mt-4 flex flex-wrap gap-2">
                    {can.edit_core && (
                        <Button variant="secondary" href={`/activities/${activity.id}/edit`}>
                            Ubah data
                        </Button>
                    )}
                    {can.manage && ['draft', 'rejected'].includes(s) && (
                        <Button onClick={() => confirmThen('Ajukan proposal untuk persetujuan? Proposal dan anggaran akan dikunci.', () => post('submit'))}>
                            Ajukan proposal
                        </Button>
                    )}
                    {can.approve && s === 'proposed' && (
                        <>
                            <Button onClick={() => confirmThen('Setujui proposal kegiatan ini?', () => post('decision', { decision: 'approve' }))}>Setujui</Button>
                            <Button
                                variant="danger"
                                onClick={() => {
                                    const note = window.prompt('Alasan penolakan (wajib):');
                                    if (note) post('decision', { decision: 'reject', note });
                                }}
                            >
                                Tolak
                            </Button>
                        </>
                    )}
                    {can.manage && s === 'approved' && (
                        <Button onClick={() => confirmThen('Tandai kegiatan selesai dan mulai menyusun LPJ?', () => post('complete'))}>Tandai selesai</Button>
                    )}
                    {can.edit_structure && (
                        <Button
                            variant="danger"
                            onClick={() => {
                                const reason = window.prompt('Alasan pembatalan (wajib):');
                                if (reason) post('cancel', { reason });
                            }}
                        >
                            Batalkan kegiatan
                        </Button>
                    )}
                    {can.delete && (
                        <Button variant="danger" onClick={() => confirmThen('Hapus kegiatan ini?', () => router.delete(`/activities/${activity.id}`))}>
                            Hapus
                        </Button>
                    )}
                </div>
            </div>

            {s === 'rejected' && activity.decision_note && (
                <div role="alert" className="mb-6 rounded-md border border-[#E7B9BC] bg-[#FBEFF0] px-4 py-3 text-sm text-[#8F1B24]">
                    <p className="font-semibold">Proposal ditolak{activity.decided_by ? ` oleh ${activity.decided_by}` : ''}</p>
                    <p className="mt-0.5">{activity.decision_note}</p>
                    <p className="mt-1 text-xs">Perbaiki proposal lalu ajukan kembali.</p>
                </div>
            )}
            {s === 'cancelled' && activity.cancelled_reason && (
                <div className="mb-6 rounded-md border border-[#DDE2E9] bg-white px-4 py-3 text-sm">
                    <p className="font-semibold">Kegiatan dibatalkan</p>
                    <p className="mt-0.5 text-[#5B6779]">{activity.cancelled_reason}</p>
                </div>
            )}

            <div role="tablist" aria-label="Bagian kegiatan" className="mb-6 flex flex-wrap gap-1 border-b border-[#DDE2E9]">
                {tabs.map(([key, label]) => (
                    <button
                        key={key}
                        role="tab"
                        type="button"
                        aria-selected={tab === key}
                        onClick={() => setTab(key)}
                        className={cx(
                            '-mb-px border-b-2 px-4 py-2 text-sm font-semibold',
                            tab === key ? 'border-[#B3202A] text-[#16202E]' : 'border-transparent text-[#5B6779] hover:text-[#16202E]',
                        )}
                    >
                        {label}
                    </button>
                ))}
            </div>

            {tab === 'summary' && (
                <Card className="p-5">
                    <dl className="grid gap-x-6 gap-y-4 sm:grid-cols-2">
                        <Row label="Periode">{activity.period}</Row>
                        <Row label="Divisi penyelenggara">{activity.division ?? 'Seluruh organisasi'}</Row>
                        <Row label="PIC">{activity.pic?.name}</Row>
                        <Row label="Kategori">{activity.category}</Row>
                        <div className="sm:col-span-2">
                            <Row label="Deskripsi">
                                <span className="whitespace-pre-line font-normal">{activity.description}</span>
                            </Row>
                        </div>
                        {can.full && activity.decided_at && s !== 'rejected' && (
                            <div className="sm:col-span-2">
                                <Row label="Keputusan proposal">
                                    {activity.decided_by}, {activity.decided_at}
                                    {activity.decision_note ? ` (${activity.decision_note})` : ''}
                                </Row>
                            </div>
                        )}
                    </dl>
                    {!can.full && <p className="mt-5 text-xs text-[#8A94A4]">Proposal, anggaran, dan LPJ hanya terlihat oleh panitia dan pengurus terkait.</p>}
                </Card>
            )}
            {tab === 'proposal' && proposal && <ProposalTab activity={activity} proposal={proposal} can={can} />}
            {tab === 'committee' && <CommitteeTab activity={activity} committee={committee} can={can} options={options} />}
            {tab === 'timeline' && <TimelineTab activity={activity} timelines={timelines} can={can} options={options} full={can.full} />}
            {tab === 'budget' && budget && <BudgetTab activity={activity} budget={budget} can={can} />}
            {tab === 'report' && report && <ReportTab activity={activity} report={report} can={can} />}
            {tab === 'docs' && <PublicationsTab activity={activity} publications={publications} />}
        </AppLayout>
    );
}
