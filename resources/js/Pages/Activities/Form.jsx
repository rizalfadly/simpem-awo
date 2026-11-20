import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button, Card, Field, Input, PageHeader, Select, inputCls } from '@/Components/ui';

export default function Form({ activity, options, defaults }) {
    const editing = !!activity;
    const { data, setData, post, put, processing, errors } = useForm({
        title: activity?.title ?? '',
        category: activity?.category ?? '',
        period_id: activity?.period_id ?? defaults.period_id ?? '',
        division_id: activity?.division_id ?? '',
        pic_member_id: activity?.pic_member_id ?? defaults.pic_member_id ?? '',
        description: activity?.description ?? '',
        location: activity?.location ?? '',
        starts_at: activity?.starts_at ?? '',
        ends_at: activity?.ends_at ?? '',
    });
    const set = (k) => (e) => setData(k, e.target.value);

    return (
        <AppLayout>
            <Head title={editing ? 'Ubah Kegiatan' : 'Buat Kegiatan'} />
            <PageHeader
                title={editing ? 'Ubah kegiatan' : 'Buat kegiatan'}
                description={editing ? activity.code : 'Kegiatan dibuat sebagai draf. Lengkapi proposal, panitia, dan anggaran setelahnya.'}
            />

            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    editing ? put(`/activities/${activity.id}`) : post('/activities');
                }}
                noValidate
            >
                <Card className="max-w-3xl space-y-5 p-6">
                    <Field label="Nama kegiatan" htmlFor="title" error={errors.title}>
                        <Input id="title" value={data.title} onChange={set('title')} />
                    </Field>

                    <div className="grid gap-5 sm:grid-cols-2">
                        <Field label="Kategori (opsional)" htmlFor="category" error={errors.category} hint="Contoh: Bakti sosial, Pelatihan, Olahraga">
                            <Input id="category" value={data.category} onChange={set('category')} />
                        </Field>
                        <Field label="Lokasi" htmlFor="location" error={errors.location}>
                            <Input id="location" value={data.location} onChange={set('location')} />
                        </Field>
                        <Field label="Mulai" htmlFor="starts_at" error={errors.starts_at}>
                            <Input id="starts_at" type="datetime-local" value={data.starts_at} onChange={set('starts_at')} />
                        </Field>
                        <Field label="Selesai" htmlFor="ends_at" error={errors.ends_at}>
                            <Input id="ends_at" type="datetime-local" value={data.ends_at} onChange={set('ends_at')} />
                        </Field>
                        <Field label="Periode kepengurusan" htmlFor="period_id" error={errors.period_id}>
                            <Select id="period_id" value={data.period_id} onChange={set('period_id')}>
                                <option value="">Tidak dipilih</option>
                                {options.periods.map((p) => (
                                    <option key={p.id} value={p.id}>
                                        {p.name}
                                        {p.is_current ? ' (berjalan)' : ''}
                                    </option>
                                ))}
                            </Select>
                        </Field>
                        <Field label="Divisi penyelenggara" htmlFor="division_id" error={errors.division_id}>
                            <Select id="division_id" value={data.division_id} onChange={set('division_id')}>
                                <option value="">Seluruh organisasi</option>
                                {options.divisions.map((d) => (
                                    <option key={d.id} value={d.id}>
                                        {d.name}
                                    </option>
                                ))}
                            </Select>
                        </Field>
                        <div className="sm:col-span-2">
                            <Field label="PIC / penanggung jawab" htmlFor="pic_member_id" error={errors.pic_member_id} hint="PIC dapat mengelola kegiatan ini (panitia, timeline, anggaran) tanpa izin khusus.">
                                <Select id="pic_member_id" value={data.pic_member_id} onChange={set('pic_member_id')}>
                                    <option value="">Pilih anggota</option>
                                    {options.members.map((m) => (
                                        <option key={m.id} value={m.id}>
                                            {m.name} ({m.nomor_anggota})
                                        </option>
                                    ))}
                                </Select>
                            </Field>
                        </div>
                    </div>

                    <Field label="Deskripsi singkat" htmlFor="description" error={errors.description}>
                        <textarea id="description" rows={4} value={data.description} onChange={set('description')} className={inputCls} />
                    </Field>

                    <div className="flex gap-2 border-t border-[#EEF1F5] pt-5">
                        <Button type="submit" disabled={processing}>
                            {editing ? 'Simpan perubahan' : 'Buat kegiatan'}
                        </Button>
                        <Button variant="secondary" href={editing ? `/activities/${activity.id}` : '/activities'}>
                            Batal
                        </Button>
                    </div>
                </Card>
            </form>
        </AppLayout>
    );
}
