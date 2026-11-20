import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Badge, Button, Card, Field, Input, Select } from '@/Components/ui';

const keep = { preserveScroll: true };
const KINDS = { preparation: 'Persiapan', rundown: 'Rundown acara' };
const STATUS = { todo: 'Belum', doing: 'Dikerjakan', done: 'Selesai' };
const tone = { todo: 'neutral', doing: 'amber', done: 'green' };

export default function TimelineTab({ activity, timelines, can, options, full }) {
    const blank = { kind: 'preparation', title: '', description: '', starts_at: '', ends_at: '', pic_member_id: '', status: 'todo' };
    const f = useForm(blank);
    const [editing, setEditing] = useState(null);

    const reset = () => {
        f.setData(blank);
        f.clearErrors();
        setEditing(null);
    };
    const submit = (e) => {
        e.preventDefault();
        const opts = { ...keep, onSuccess: reset };
        editing ? f.put(`/timelines/${editing}`, opts) : f.post(`/activities/${activity.id}/timeline`, opts);
    };
    const edit = (t) => {
        setEditing(t.id);
        f.clearErrors();
        f.setData({
            kind: t.kind,
            title: t.title,
            description: t.description ?? '',
            starts_at: t.starts_at_iso,
            ends_at: t.ends_at_iso ?? '',
            pic_member_id: t.pic_member_id ?? '',
            status: t.status,
        });
    };
    const remove = (t) => window.confirm(`Hapus agenda "${t.title}"?`) && router.delete(`/timelines/${t.id}`, keep);
    const setStatus = (t, status) => router.patch(`/timelines/${t.id}/status`, { status }, keep);

    const kinds = full ? ['preparation', 'rundown'] : ['rundown'];

    return (
        <div className="space-y-6">
            {can.edit_structure && options && (
                <form onSubmit={submit} noValidate>
                    <Card className="p-5">
                        <h2 className="text-base font-bold">{editing ? 'Ubah agenda' : 'Tambah agenda'}</h2>
                        <div className="mt-4 grid gap-4 sm:grid-cols-2">
                            <Field label="Jenis" htmlFor="t_kind" error={f.errors.kind}>
                                <Select id="t_kind" value={f.data.kind} onChange={(e) => f.setData('kind', e.target.value)}>
                                    {Object.entries(KINDS).map(([k, v]) => (
                                        <option key={k} value={k}>
                                            {v}
                                        </option>
                                    ))}
                                </Select>
                            </Field>
                            <Field label="Judul" htmlFor="t_title" error={f.errors.title}>
                                <Input id="t_title" value={f.data.title} onChange={(e) => f.setData('title', e.target.value)} />
                            </Field>
                            <Field label="Mulai" htmlFor="t_start" error={f.errors.starts_at}>
                                <Input id="t_start" type="datetime-local" value={f.data.starts_at} onChange={(e) => f.setData('starts_at', e.target.value)} />
                            </Field>
                            <Field label="Selesai (opsional)" htmlFor="t_end" error={f.errors.ends_at}>
                                <Input id="t_end" type="datetime-local" value={f.data.ends_at} onChange={(e) => f.setData('ends_at', e.target.value)} />
                            </Field>
                            <Field label="PIC agenda (opsional)" htmlFor="t_pic" error={f.errors.pic_member_id}>
                                <Select id="t_pic" value={f.data.pic_member_id} onChange={(e) => f.setData('pic_member_id', e.target.value)}>
                                    <option value="">Tidak ada</option>
                                    {options.members.map((m) => (
                                        <option key={m.id} value={m.id}>
                                            {m.name}
                                        </option>
                                    ))}
                                </Select>
                            </Field>
                            <Field label="Status" htmlFor="t_status" error={f.errors.status}>
                                <Select id="t_status" value={f.data.status} onChange={(e) => f.setData('status', e.target.value)}>
                                    {Object.entries(STATUS).map(([k, v]) => (
                                        <option key={k} value={k}>
                                            {v}
                                        </option>
                                    ))}
                                </Select>
                            </Field>
                            <div className="sm:col-span-2">
                                <Field label="Keterangan (opsional)" htmlFor="t_desc" error={f.errors.description}>
                                    <Input id="t_desc" value={f.data.description} onChange={(e) => f.setData('description', e.target.value)} />
                                </Field>
                            </div>
                        </div>
                        <div className="mt-5 flex gap-2">
                            <Button type="submit" disabled={f.processing}>
                                {editing ? 'Simpan perubahan' : 'Tambahkan'}
                            </Button>
                            {editing && (
                                <Button variant="secondary" onClick={reset}>
                                    Batal
                                </Button>
                            )}
                        </div>
                    </Card>
                </form>
            )}

            {kinds.map((kind) => {
                const items = timelines.filter((t) => t.kind === kind);
                return (
                    <Card key={kind}>
                        <h2 className="border-b border-[#DDE2E9] px-4 py-3 text-base font-bold">{KINDS[kind]}</h2>
                        {items.length === 0 ? (
                            <p className="px-4 py-8 text-center text-sm text-[#5B6779]">Belum ada agenda.</p>
                        ) : (
                            <ul className="divide-y divide-[#EEF1F5]">
                                {items.map((t) => (
                                    <li key={t.id} className="flex flex-wrap items-start justify-between gap-3 px-4 py-3">
                                        <div className="min-w-0">
                                            <p className="text-sm font-semibold">{t.title}</p>
                                            <p className="text-xs tabular-nums text-[#5B6779]">
                                                {t.starts_at}
                                                {t.ends_at ? ` - ${t.ends_at}` : ''}
                                            </p>
                                            {(t.pic || t.description) && (
                                                <p className="mt-0.5 text-xs text-[#5B6779]">
                                                    {t.pic ? `PIC ${t.pic}` : ''}
                                                    {t.pic && t.description ? ' · ' : ''}
                                                    {t.description}
                                                </p>
                                            )}
                                        </div>
                                        <div className="flex flex-wrap items-center gap-1.5">
                                            {kind === 'preparation' &&
                                                (t.can_status ? (
                                                    <Select value={t.status} onChange={(e) => setStatus(t, e.target.value)} aria-label={`Status ${t.title}`} className="w-36 py-1 text-xs">
                                                        {Object.entries(STATUS).map(([k, v]) => (
                                                            <option key={k} value={k}>
                                                                {v}
                                                            </option>
                                                        ))}
                                                    </Select>
                                                ) : (
                                                    <Badge tone={tone[t.status]}>{STATUS[t.status]}</Badge>
                                                ))}
                                            {can.edit_structure && (
                                                <>
                                                    <Button variant="secondary" size="sm" onClick={() => edit(t)}>
                                                        Ubah
                                                    </Button>
                                                    <Button variant="danger" size="sm" onClick={() => remove(t)}>
                                                        Hapus
                                                    </Button>
                                                </>
                                            )}
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </Card>
                );
            })}
        </div>
    );
}
