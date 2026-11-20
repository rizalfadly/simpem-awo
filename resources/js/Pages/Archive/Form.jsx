import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button, Card, Field, Input, PageHeader, Select, inputCls } from '@/Components/ui';

export default function Form({ document: doc, options }) {
    const editing = !!doc;
    const { data, setData, post, processing, errors } = useForm({
        ...(editing ? { _method: 'put' } : {}),
        title: doc?.title ?? '',
        category: doc?.category ?? 'notulen',
        document_number: doc?.document_number ?? '',
        document_date: doc?.document_date ?? '',
        description: doc?.description ?? '',
        activity_id: doc?.activity_id ?? '',
        period_id: doc?.period_id ?? '',
        access: doc?.access ?? 'members',
        file: null,
    });
    const set = (k) => (e) => setData(k, e.target.value);

    return (
        <AppLayout>
            <Head title={editing ? 'Ubah Dokumen' : 'Arsipkan Dokumen'} />
            <PageHeader title={editing ? 'Ubah dokumen' : 'Arsipkan dokumen'} />

            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    post(editing ? `/archive/${doc.id}` : '/archive', { forceFormData: true });
                }}
                noValidate
            >
                <Card className="max-w-3xl space-y-5 p-6">
                    <Field label="Judul dokumen" htmlFor="title" error={errors.title}>
                        <Input id="title" value={data.title} onChange={set('title')} />
                    </Field>
                    <div className="grid gap-5 sm:grid-cols-2">
                        <Field label="Kategori" htmlFor="category" error={errors.category}>
                            <Select id="category" value={data.category} onChange={set('category')}>
                                {Object.entries(options.categories).map(([k, v]) => (
                                    <option key={k} value={k}>
                                        {v}
                                    </option>
                                ))}
                            </Select>
                        </Field>
                        <Field label="Nomor dokumen (opsional)" htmlFor="document_number" error={errors.document_number}>
                            <Input id="document_number" value={data.document_number} onChange={set('document_number')} />
                        </Field>
                        <Field label="Tanggal dokumen" htmlFor="document_date" error={errors.document_date}>
                            <Input id="document_date" type="date" value={data.document_date} onChange={set('document_date')} />
                        </Field>
                        <Field label="Akses" htmlFor="access" error={errors.access} hint="Terbatas = hanya pengurus berizin arsip.">
                            <Select id="access" value={data.access} onChange={set('access')}>
                                {Object.entries(options.access).map(([k, v]) => (
                                    <option key={k} value={k}>
                                        {v}
                                    </option>
                                ))}
                            </Select>
                        </Field>
                        <Field label="Periode (opsional)" htmlFor="period_id" error={errors.period_id}>
                            <Select id="period_id" value={data.period_id} onChange={set('period_id')}>
                                <option value="">Tidak dipilih</option>
                                {options.periods.map((p) => (
                                    <option key={p.id} value={p.id}>
                                        {p.name}
                                    </option>
                                ))}
                            </Select>
                        </Field>
                        <Field label="Kegiatan terkait (opsional)" htmlFor="activity_id" error={errors.activity_id}>
                            <Select id="activity_id" value={data.activity_id} onChange={set('activity_id')}>
                                <option value="">Tidak ada</option>
                                {options.activities.map((a) => (
                                    <option key={a.id} value={a.id}>
                                        {a.code} {a.title}
                                    </option>
                                ))}
                            </Select>
                        </Field>
                    </div>
                    <Field label="Uraian singkat (opsional)" htmlFor="description" error={errors.description}>
                        <textarea id="description" rows={3} value={data.description} onChange={set('description')} className={inputCls} />
                    </Field>
                    <Field
                        label={editing ? 'Ganti berkas (opsional)' : 'Berkas'}
                        htmlFor="file"
                        error={errors.file}
                        hint={editing ? `Berkas saat ini: ${doc.original_name}. PDF, DOC(X), XLS(X), PPT(X), JPG, PNG, maks. 10 MB.` : 'PDF, DOC(X), XLS(X), PPT(X), JPG, PNG, maksimal 10 MB.'}
                    >
                        <input id="file" type="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png" onChange={(e) => setData('file', e.target.files[0] ?? null)} className={inputCls} />
                    </Field>
                    <div className="flex gap-2 border-t border-[#EEF1F5] pt-5">
                        <Button type="submit" disabled={processing}>
                            {editing ? 'Simpan perubahan' : 'Arsipkan'}
                        </Button>
                        <Button variant="secondary" href="/archive">
                            Batal
                        </Button>
                    </div>
                </Card>
            </form>
        </AppLayout>
    );
}
