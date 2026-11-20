import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button, Card, Field, Input, PageHeader, Select, inputCls } from '@/Components/ui';

export default function Form({ album, activities, defaults, can_link }) {
    const editing = !!album;
    const { data, setData, post, put, processing, errors } = useForm({
        title: album?.title ?? defaults.title ?? '',
        description: album?.description ?? '',
        event_date: album?.event_date ?? defaults.event_date ?? '',
        activity_id: album?.activity_id ?? defaults.activity_id ?? '',
    });

    return (
        <AppLayout>
            <Head title={editing ? 'Ubah Album' : 'Buat Album'} />
            <PageHeader title={editing ? 'Ubah album' : 'Buat album'} description="Album dibuat sebagai draf. Foto diunggah setelah album tersimpan." />

            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    editing ? put(`/albums/${album.id}`) : post('/albums');
                }}
                noValidate
            >
                <Card className="max-w-2xl space-y-5 p-6">
                    <Field label="Judul album" htmlFor="title" error={errors.title}>
                        <Input id="title" value={data.title} onChange={(e) => setData('title', e.target.value)} />
                    </Field>
                    <div className="grid gap-5 sm:grid-cols-2">
                        <Field label="Tanggal kegiatan" htmlFor="event_date" error={errors.event_date}>
                            <Input id="event_date" type="date" value={data.event_date} onChange={(e) => setData('event_date', e.target.value)} />
                        </Field>
                        <Field
                            label="Kegiatan terkait"
                            htmlFor="activity_id"
                            error={errors.activity_id}
                            hint={can_link ? 'Hanya kegiatan yang sudah disetujui.' : 'Hanya pengelola galeri yang dapat memindahkan album.'}
                        >
                            <Select id="activity_id" value={data.activity_id} disabled={!can_link} onChange={(e) => setData('activity_id', e.target.value)}>
                                <option value="">Tanpa kegiatan</option>
                                {activities.map((a) => (
                                    <option key={a.id} value={a.id}>
                                        {a.code} {a.title}
                                    </option>
                                ))}
                            </Select>
                        </Field>
                    </div>
                    <Field label="Deskripsi (opsional)" htmlFor="description" error={errors.description}>
                        <textarea id="description" rows={3} value={data.description} onChange={(e) => setData('description', e.target.value)} className={inputCls} />
                    </Field>
                    <div className="flex gap-2 border-t border-[#EEF1F5] pt-5">
                        <Button type="submit" disabled={processing}>
                            {editing ? 'Simpan perubahan' : 'Buat album'}
                        </Button>
                        <Button variant="secondary" href={editing ? `/albums/${album.id}` : '/albums'}>
                            Batal
                        </Button>
                    </div>
                </Card>
            </form>
        </AppLayout>
    );
}
