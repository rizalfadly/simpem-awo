import { Head, useForm } from '@inertiajs/react';
import { useMemo } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Button, Card, Field, Input, PageHeader, Select, inputCls } from '@/Components/ui';

export default function Form({ post, activities, defaults }) {
    const editing = !!post;
    const { data, setData, post: send, processing, errors } = useForm({
        ...(editing ? { _method: 'put' } : {}),
        title: post?.title ?? '',
        excerpt: post?.excerpt ?? '',
        body: post?.body ?? '',
        activity_id: post?.activity_id ?? defaults.activity_id ?? '',
        cover: null,
        remove_cover: false,
    });
    const preview = useMemo(() => (data.cover ? URL.createObjectURL(data.cover) : null), [data.cover]);

    return (
        <AppLayout>
            <Head title={editing ? 'Ubah Berita' : 'Tulis Berita'} />
            <PageHeader title={editing ? 'Ubah berita' : 'Tulis berita'} description="Berita disimpan sebagai draf sampai diterbitkan oleh pihak yang berwenang." />

            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    send(editing ? `/news/${post.id}` : '/news', { forceFormData: true });
                }}
                noValidate
            >
                <Card className="max-w-3xl space-y-5 p-6">
                    <Field label="Judul" htmlFor="title" error={errors.title}>
                        <Input id="title" value={data.title} onChange={(e) => setData('title', e.target.value)} />
                    </Field>
                    <Field label="Ringkasan (opsional)" htmlFor="excerpt" error={errors.excerpt} hint={`${data.excerpt.length}/300 karakter. Tampil di daftar berita.`}>
                        <textarea id="excerpt" rows={2} maxLength={300} value={data.excerpt} onChange={(e) => setData('excerpt', e.target.value)} className={inputCls} />
                    </Field>
                    <Field label="Isi berita" htmlFor="body" error={errors.body} hint="Teks biasa. Pisahkan paragraf dengan baris kosong.">
                        <textarea id="body" rows={12} value={data.body} onChange={(e) => setData('body', e.target.value)} className={inputCls} />
                    </Field>
                    <Field label="Kegiatan terkait (opsional)" htmlFor="activity_id" error={errors.activity_id} hint="Hanya kegiatan yang sudah disetujui.">
                        <Select id="activity_id" value={data.activity_id} onChange={(e) => setData('activity_id', e.target.value)}>
                            <option value="">Tanpa kegiatan</option>
                            {activities.map((a) => (
                                <option key={a.id} value={a.id}>
                                    {a.code} {a.title}
                                </option>
                            ))}
                        </Select>
                    </Field>
                    <Field label="Gambar sampul (opsional)" htmlFor="cover" error={errors.cover} hint="JPG, PNG, atau WEBP, maksimal 8 MB.">
                        <input id="cover" type="file" accept="image/jpeg,image/png,image/webp" onChange={(e) => setData((d) => ({ ...d, cover: e.target.files[0] ?? null, remove_cover: false }))} className={inputCls} />
                    </Field>
                    {preview && <img src={preview} alt="Pratinjau sampul" className="max-h-48 rounded-md" />}
                    {editing && post.has_cover && !data.cover && (
                        <label className="flex items-center gap-2 text-sm">
                            <input type="checkbox" checked={data.remove_cover} onChange={(e) => setData('remove_cover', e.target.checked)} className="h-4 w-4 rounded border-[#CBD2DC] text-[#B3202A] focus:ring-[#B3202A]" />
                            Hapus sampul saat ini
                        </label>
                    )}
                    <div className="flex gap-2 border-t border-[#EEF1F5] pt-5">
                        <Button type="submit" disabled={processing}>
                            {editing ? 'Simpan perubahan' : 'Simpan draf'}
                        </Button>
                        <Button variant="secondary" href={editing ? `/news/${post.id}` : '/news'}>
                            Batal
                        </Button>
                    </div>
                </Card>
            </form>
        </AppLayout>
    );
}
