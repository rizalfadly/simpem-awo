import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button, Card, Field, Input, PageHeader, Select, inputCls } from '@/Components/ui';
import { rupiah } from '@/lib/format';

export default function Form({ partnership, options, defaults }) {
    const editing = !!partnership;
    const { data, setData, post, processing, errors } = useForm({
        _method: editing ? 'put' : 'post',
        partner_id: partnership?.partner_id ?? defaults.partner_id ?? '',
        title: partnership?.title ?? '',
        type: partnership?.type ?? 'sponsorship',
        description: partnership?.description ?? '',
        starts_on: partnership?.starts_on ?? '',
        ends_on: partnership?.ends_on ?? '',
        signed_on: partnership?.signed_on ?? '',
        value: partnership?.value ?? '',
        pic_member_id: partnership?.pic_member_id ?? '',
        activity_id: partnership?.activity_id ?? '',
        document: null,
        remove_document: false,
    });
    const set = (k) => (e) => setData(k, e.target.value);

    return (
        <AppLayout>
            <Head title={editing ? 'Ubah Kerja Sama' : 'Catat Kerja Sama'} />
            <PageHeader title={editing ? 'Ubah kerja sama' : 'Catat kerja sama'} description="Dicatat sebagai draf. Aktifkan setelah naskah ditandatangani." />

            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    post(editing ? `/partners/partnerships/${partnership.id}` : '/partners/partnerships', { forceFormData: true });
                }}
                noValidate
            >
                <Card className="max-w-3xl space-y-5 p-6">
                    <div className="grid gap-5 sm:grid-cols-2">
                        <Field label="Mitra" htmlFor="partner_id" error={errors.partner_id}>
                            <Select id="partner_id" value={data.partner_id} onChange={set('partner_id')}>
                                <option value="">Pilih mitra</option>
                                {options.partners.map((p) => (
                                    <option key={p.id} value={p.id}>
                                        {p.name}
                                    </option>
                                ))}
                            </Select>
                        </Field>
                        <Field label="Jenis" htmlFor="type" error={errors.type}>
                            <Select id="type" value={data.type} onChange={set('type')}>
                                {Object.entries(options.types).map(([k, v]) => (
                                    <option key={k} value={k}>
                                        {v}
                                    </option>
                                ))}
                            </Select>
                        </Field>
                        <div className="sm:col-span-2">
                            <Field label="Judul" htmlFor="title" error={errors.title}>
                                <Input id="title" value={data.title} onChange={set('title')} />
                            </Field>
                        </div>
                        <Field label="Mulai (opsional)" htmlFor="starts_on" error={errors.starts_on}>
                            <Input id="starts_on" type="date" value={data.starts_on} onChange={set('starts_on')} />
                        </Field>
                        <Field label="Berakhir (opsional)" htmlFor="ends_on" error={errors.ends_on}>
                            <Input id="ends_on" type="date" value={data.ends_on} onChange={set('ends_on')} />
                        </Field>
                        <Field label="Tanggal ditandatangani (opsional)" htmlFor="signed_on" error={errors.signed_on}>
                            <Input id="signed_on" type="date" value={data.signed_on} onChange={set('signed_on')} />
                        </Field>
                        <Field label="Nilai (Rp, opsional)" htmlFor="value" error={errors.value} hint={data.value ? rupiah(Number(data.value)) : 'Mis. nilai sponsorship.'}>
                            <Input id="value" type="number" min="0" value={data.value} onChange={set('value')} />
                        </Field>
                        <Field label="PIC dari AWO (opsional)" htmlFor="pic_member_id" error={errors.pic_member_id}>
                            <Select id="pic_member_id" value={data.pic_member_id} onChange={set('pic_member_id')}>
                                <option value="">Tidak ada</option>
                                {options.members.map((m) => (
                                    <option key={m.id} value={m.id}>
                                        {m.name} ({m.nomor_anggota})
                                    </option>
                                ))}
                            </Select>
                        </Field>
                        <Field label="Kegiatan terkait (opsional)" htmlFor="activity_id" error={errors.activity_id}>
                            <Select id="activity_id" value={data.activity_id} onChange={set('activity_id')}>
                                <option value="">Tidak terkait kegiatan</option>
                                {options.activities.map((a) => (
                                    <option key={a.id} value={a.id}>
                                        {a.code} {a.title}
                                    </option>
                                ))}
                            </Select>
                        </Field>
                        <div className="sm:col-span-2">
                            <Field label="Lingkup / ketentuan ringkas (opsional)" htmlFor="description" error={errors.description}>
                                <textarea id="description" rows={3} value={data.description} onChange={set('description')} className={inputCls} />
                            </Field>
                        </div>
                        <div className="sm:col-span-2">
                            <Field
                                label={editing && partnership.document_name ? `Ganti naskah (saat ini: ${partnership.document_name})` : 'Naskah perjanjian (opsional)'}
                                htmlFor="document"
                                error={errors.document}
                                hint="PDF, JPG, atau PNG, maks. 10 MB. Disimpan privat, hanya untuk pengurus kemitraan."
                            >
                                <input id="document" type="file" accept=".pdf,.jpg,.jpeg,.png" onChange={(e) => setData((d) => ({ ...d, document: e.target.files[0] ?? null, remove_document: false }))} className={inputCls} />
                            </Field>
                            {editing && partnership.document_name && !data.document && (
                                <label className="mt-2 flex items-center gap-2 text-sm">
                                    <input type="checkbox" checked={data.remove_document} onChange={(e) => setData('remove_document', e.target.checked)} className="h-4 w-4 rounded border-[#CBD2DC] text-[#B3202A] focus:ring-[#B3202A]" />
                                    Hapus naskah saat ini
                                </label>
                            )}
                        </div>
                    </div>
                    <div className="flex gap-2 border-t border-[#EEF1F5] pt-5">
                        <Button type="submit" disabled={processing}>
                            {editing ? 'Simpan perubahan' : 'Simpan draf'}
                        </Button>
                        <Button variant="secondary" href={editing ? `/partners/partnerships/${partnership.id}` : '/partners/partnerships'}>
                            Batal
                        </Button>
                    </div>
                </Card>
            </form>
        </AppLayout>
    );
}
