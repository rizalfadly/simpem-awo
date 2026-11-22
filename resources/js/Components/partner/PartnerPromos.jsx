import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Badge, Button, Card, Field, Input, Select, inputCls } from '@/Components/ui';

const keep = { preserveScroll: true };
export const phaseTone = { draft: 'neutral', upcoming: 'amber', running: 'green', ended: 'neutral' };
export const phaseLabel = { draft: 'Draf', upcoming: 'Akan datang', running: 'Berlangsung', ended: 'Berakhir' };

export default function PartnerPromos({ partner, promotions, can }) {
    const today = new Date().toLocaleDateString('en-CA');
    const blank = { title: '', description: '', terms: '', starts_on: today, ends_on: today, audience: 'members' };
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
        editing ? f.put(`/partners/promotions/${editing.id}`, opts) : f.post(`/partners/${partner.id}/promotions`, opts);
    };
    const edit = (p) => {
        setEditing(p);
        f.clearErrors();
        f.setData({ title: p.title, description: p.description ?? '', terms: p.terms ?? '', starts_on: p.starts_on, ends_on: p.ends_on, audience: p.audience });
    };
    const setStatus = (p, status) => router.post(`/partners/promotions/${p.id}/status`, { status }, keep);

    return (
        <div className="space-y-6">
            {can.manage && (
                <form onSubmit={submit} noValidate>
                    <Card className="p-5">
                        <h2 className="text-base font-bold">{editing ? `Ubah ${editing.title}` : 'Tambah promosi'}</h2>
                        <div className="mt-4 grid gap-4 sm:grid-cols-2">
                            <div className="sm:col-span-2">
                                <Field label="Judul promosi" htmlFor="pr_title" error={f.errors.title}>
                                    <Input id="pr_title" value={f.data.title} onChange={(e) => f.setData('title', e.target.value)} placeholder="Diskon 10% untuk anggota AWO" />
                                </Field>
                            </div>
                            <Field label="Mulai" htmlFor="pr_start" error={f.errors.starts_on}>
                                <Input id="pr_start" type="date" value={f.data.starts_on} onChange={(e) => f.setData('starts_on', e.target.value)} />
                            </Field>
                            <Field label="Berakhir" htmlFor="pr_end" error={f.errors.ends_on}>
                                <Input id="pr_end" type="date" value={f.data.ends_on} onChange={(e) => f.setData('ends_on', e.target.value)} />
                            </Field>
                            <div className="sm:col-span-2">
                                <Field label="Untuk siapa" htmlFor="pr_aud" error={f.errors.audience} hint="Promosi umum baru tampil di website publik setelah dipilih khusus oleh penerbit (V1.7).">
                                    <Select id="pr_aud" value={f.data.audience} onChange={(e) => f.setData('audience', e.target.value)}>
                                        <option value="members">Khusus anggota</option>
                                        <option value="public">Umum</option>
                                    </Select>
                                </Field>
                            </div>
                            <div className="sm:col-span-2">
                                <Field label="Deskripsi (opsional)" htmlFor="pr_desc" error={f.errors.description}>
                                    <textarea id="pr_desc" rows={2} value={f.data.description} onChange={(e) => f.setData('description', e.target.value)} className={inputCls} />
                                </Field>
                            </div>
                            <div className="sm:col-span-2">
                                <Field label="Syarat & ketentuan (opsional)" htmlFor="pr_terms" error={f.errors.terms}>
                                    <Input id="pr_terms" value={f.data.terms} onChange={(e) => f.setData('terms', e.target.value)} />
                                </Field>
                            </div>
                        </div>
                        <div className="mt-5 flex gap-2">
                            <Button type="submit" disabled={f.processing}>
                                {editing ? 'Simpan perubahan' : 'Simpan draf'}
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

            {promotions.length === 0 ? (
                <p className="rounded-lg border border-dashed border-[#CBD2DC] bg-white px-6 py-10 text-center text-sm text-[#5B6779]">Tidak ada promosi.</p>
            ) : (
                <ul className="space-y-3">
                    {promotions.map((p) => (
                        <li key={p.id} className="rounded-lg border border-[#DDE2E9] bg-white p-4">
                            <div className="flex flex-wrap items-start justify-between gap-2">
                                <div>
                                    <h3 className="text-sm font-bold">{p.title}</h3>
                                    <p className="text-xs tabular-nums text-[#5B6779]">{p.period}</p>
                                </div>
                                <div className="flex gap-1.5">
                                    <Badge tone={phaseTone[p.phase]}>{phaseLabel[p.phase]}</Badge>
                                    {can.internal && <Badge tone="neutral">{p.audience === 'public' ? 'Umum' : 'Anggota'}</Badge>}
                                </div>
                            </div>
                            {p.description && <p className="mt-2 text-sm">{p.description}</p>}
                            {p.terms && <p className="mt-1 text-xs text-[#5B6779]">S&K: {p.terms}</p>}
                            {(can.manage || can.publish) && (
                                <div className="mt-3 flex flex-wrap gap-1.5">
                                    {can.manage && p.status === 'draft' && (
                                        <>
                                            <Button size="sm" variant="secondary" onClick={() => edit(p)}>
                                                Ubah
                                            </Button>
                                            <Button size="sm" variant="danger" onClick={() => window.confirm('Hapus promosi ini?') && router.delete(`/partners/promotions/${p.id}`, keep)}>
                                                Hapus
                                            </Button>
                                        </>
                                    )}
                                    {can.publish && p.status === 'draft' && (
                                        <Button size="sm" onClick={() => window.confirm('Terbitkan promosi ini? Anggota akan dapat melihatnya.') && setStatus(p, 'published')}>
                                            Terbitkan
                                        </Button>
                                    )}
                                    {can.publish && p.status === 'published' && (
                                        <Button size="sm" variant="secondary" onClick={() => window.confirm('Tarik promosi ke draf?') && setStatus(p, 'draft')}>
                                            Tarik ke draf
                                        </Button>
                                    )}
                                    {!can.publish && p.status === 'draft' && <span className="self-center text-xs text-[#5B6779]">Menunggu penerbit (izin content.publish).</span>}
                                </div>
                            )}
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
