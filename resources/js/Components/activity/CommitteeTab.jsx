import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import Avatar from '@/Components/Avatar';
import { Badge, Button, Card, Field, Input, Select } from '@/Components/ui';

const CORE = ['leader', 'deputy', 'secretary', 'treasurer'];
const keep = { preserveScroll: true };

export default function CommitteeTab({ activity, committee, can, options }) {
    const blank = { member_id: '', role: 'member', section: '', notes: '' };
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
        editing ? f.put(`/committee-members/${editing.id}`, opts) : f.post(`/activities/${activity.id}/committee`, opts);
    };
    const edit = (c) => {
        setEditing(c);
        f.clearErrors();
        f.setData({ member_id: c.member_id, role: c.role, section: c.section ?? '', notes: c.notes ?? '' });
    };
    const remove = (c) => window.confirm(`Hapus ${c.name} dari panitia?`) && router.delete(`/committee-members/${c.id}`, keep);

    const core = committee.filter((c) => CORE.includes(c.role));
    const sections = {};
    committee.filter((c) => !CORE.includes(c.role)).forEach((c) => {
        const key = c.section || 'Umum';
        (sections[key] ??= []).push(c);
    });

    const Person = ({ c }) => (
        <li className="flex items-center gap-3 px-4 py-2.5">
            <Avatar name={c.name} src={c.photo_url} className="h-9 w-9 rounded-full" textClass="text-xs" />
            <div className="min-w-0 flex-1">
                <p className="text-sm font-semibold leading-tight">{c.name}</p>
                <p className="text-xs text-[#5B6779]">
                    {c.role_label}
                    {c.notes ? ` · ${c.notes}` : ''}
                </p>
            </div>
            {can.edit_structure && (
                <div className="flex gap-1.5">
                    <Button variant="secondary" size="sm" onClick={() => edit(c)}>
                        Ubah
                    </Button>
                    <Button variant="danger" size="sm" onClick={() => remove(c)}>
                        Hapus
                    </Button>
                </div>
            )}
        </li>
    );

    return (
        <div className="space-y-6">
            {can.edit_structure && options && (
                <form onSubmit={submit} noValidate>
                    <Card className="p-5">
                        <h2 className="text-base font-bold">{editing ? `Ubah peran ${editing.name}` : 'Tambah panitia'}</h2>
                        <div className="mt-4 grid gap-4 sm:grid-cols-2">
                            <Field label="Anggota" htmlFor="c_member" error={f.errors.member_id}>
                                <Select id="c_member" value={f.data.member_id} disabled={!!editing} onChange={(e) => f.setData('member_id', e.target.value)}>
                                    <option value="">Pilih anggota</option>
                                    {options.members.map((m) => (
                                        <option key={m.id} value={m.id}>
                                            {m.name} ({m.nomor_anggota})
                                        </option>
                                    ))}
                                </Select>
                            </Field>
                            <Field label="Peran" htmlFor="c_role" error={f.errors.role}>
                                <Select id="c_role" value={f.data.role} onChange={(e) => f.setData('role', e.target.value)}>
                                    {Object.entries(options.roles).map(([k, v]) => (
                                        <option key={k} value={k}>
                                            {v}
                                        </option>
                                    ))}
                                </Select>
                            </Field>
                            <Field label="Seksi" htmlFor="c_section" error={f.errors.section} hint="Wajib untuk Koordinator Seksi. Contoh: Acara, Humas, Konsumsi.">
                                <Input id="c_section" list="c_sections" value={f.data.section} onChange={(e) => f.setData('section', e.target.value)} />
                                <datalist id="c_sections">
                                    {options.sections.map((s) => (
                                        <option key={s} value={s} />
                                    ))}
                                </datalist>
                            </Field>
                            <Field label="Catatan (opsional)" htmlFor="c_notes" error={f.errors.notes}>
                                <Input id="c_notes" value={f.data.notes} onChange={(e) => f.setData('notes', e.target.value)} />
                            </Field>
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

            {committee.length === 0 ? (
                <p className="rounded-lg border border-dashed border-[#CBD2DC] bg-white px-6 py-10 text-center text-sm text-[#5B6779]">Belum ada panitia ditetapkan.</p>
            ) : (
                <div className="space-y-6">
                    {core.length > 0 && (
                        <Card>
                            <h2 className="border-b border-[#DDE2E9] px-4 py-3 text-base font-bold">Inti panitia</h2>
                            <ul className="divide-y divide-[#EEF1F5]">
                                {core.map((c) => (
                                    <Person key={c.id} c={c} />
                                ))}
                            </ul>
                        </Card>
                    )}
                    {Object.entries(sections).map(([name, list]) => (
                        <Card key={name}>
                            <h2 className="flex items-center gap-2 border-b border-[#DDE2E9] px-4 py-3 text-base font-bold">
                                Seksi {name} <Badge>{list.length}</Badge>
                            </h2>
                            <ul className="divide-y divide-[#EEF1F5]">
                                {list.map((c) => (
                                    <Person key={c.id} c={c} />
                                ))}
                            </ul>
                        </Card>
                    ))}
                </div>
            )}
        </div>
    );
}
