import { Head, useForm } from '@inertiajs/react';
import { useMemo } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Button, Card, Field, Input, PageHeader, Select, inputCls } from '@/Components/ui';

export default function Form({ partner, options }) {
    const editing = !!partner;
    const { data, setData, post, processing, errors } = useForm({
        _method: editing ? 'put' : 'post',
        name: partner?.name ?? '',
        type: partner?.type ?? 'umkm',
        sector: partner?.sector ?? '',
        owner_name: partner?.owner_name ?? '',
        member_id: partner?.member_id ?? '',
        description: partner?.description ?? '',
        address: partner?.address ?? '',
        city: partner?.city ?? '',
        website: partner?.website ?? '',
        instagram: partner?.instagram ?? '',
        public_contact: partner?.public_contact ?? '',
        contact_person: partner?.contact_person ?? '',
        contact_phone: partner?.contact_phone ?? '',
        contact_email: partner?.contact_email ?? '',
        notes: partner?.notes ?? '',
        joined_at: partner?.joined_at ?? '',
        logo: null,
        remove_logo: false,
    });
    const set = (k) => (e) => setData(k, e.target.value);
    const preview = useMemo(() => (data.logo ? URL.createObjectURL(data.logo) : null), [data.logo]);

    return (
        <AppLayout>
            <Head title={editing ? 'Ubah Mitra' : 'Tambah Mitra'} />
            <PageHeader title={editing ? 'Ubah mitra' : 'Tambah mitra'} description="Pisahkan informasi usaha (boleh dilihat anggota) dari data kontak internal (hanya pengurus)." />

            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    post(editing ? `/partners/${partner.id}` : '/partners', { forceFormData: true });
                }}
                noValidate
                className="max-w-3xl space-y-6"
            >
                <Card className="space-y-5 p-6">
                    <h2 className="text-base font-bold">Informasi usaha / lembaga</h2>
                    <div className="grid gap-5 sm:grid-cols-2">
                        <div className="sm:col-span-2">
                            <Field label="Nama" htmlFor="name" error={errors.name}>
                                <Input id="name" value={data.name} onChange={set('name')} />
                            </Field>
                        </div>
                        <Field label="Jenis" htmlFor="type" error={errors.type}>
                            <Select id="type" value={data.type} onChange={set('type')}>
                                {Object.entries(options.types).map(([k, v]) => (
                                    <option key={k} value={k}>
                                        {v}
                                    </option>
                                ))}
                            </Select>
                        </Field>
                        <Field label="Bidang usaha (opsional)" htmlFor="sector" error={errors.sector} hint="Mis. kuliner, fashion, jasa">
                            <Input id="sector" value={data.sector} onChange={set('sector')} />
                        </Field>
                        <Field label="Pemilik / pimpinan (opsional)" htmlFor="owner_name" error={errors.owner_name}>
                            <Input id="owner_name" value={data.owner_name} onChange={set('owner_name')} />
                        </Field>
                        <Field label="Anggota AWO pemilik (opsional)" htmlFor="member_id" error={errors.member_id} hint="Untuk menandai UMKM milik anggota.">
                            <Select id="member_id" value={data.member_id} onChange={set('member_id')}>
                                <option value="">Bukan milik anggota</option>
                                {options.members.map((m) => (
                                    <option key={m.id} value={m.id}>
                                        {m.name} ({m.nomor_anggota})
                                    </option>
                                ))}
                            </Select>
                        </Field>
                        <div className="sm:col-span-2">
                            <Field label="Deskripsi" htmlFor="description" error={errors.description}>
                                <textarea id="description" rows={3} value={data.description} onChange={set('description')} className={inputCls} />
                            </Field>
                        </div>
                        <Field label="Alamat" htmlFor="address" error={errors.address}>
                            <Input id="address" value={data.address} onChange={set('address')} />
                        </Field>
                        <Field label="Kota" htmlFor="city" error={errors.city}>
                            <Input id="city" value={data.city} onChange={set('city')} />
                        </Field>
                        <Field label="Website (opsional)" htmlFor="website" error={errors.website}>
                            <Input id="website" value={data.website} onChange={set('website')} placeholder="https://" />
                        </Field>
                        <Field label="Instagram (opsional)" htmlFor="instagram" error={errors.instagram}>
                            <Input id="instagram" value={data.instagram} onChange={set('instagram')} placeholder="@namausaha" />
                        </Field>
                        <div className="sm:col-span-2">
                            <Field label="Kontak publik (opsional)" htmlFor="public_contact" error={errors.public_contact} hint="Satu-satunya kontak yang boleh tampil ke publik, mis. WhatsApp usaha. Jangan isi nomor pribadi.">
                                <Input id="public_contact" value={data.public_contact} onChange={set('public_contact')} />
                            </Field>
                        </div>
                        <Field label="Logo (opsional)" htmlFor="logo" error={errors.logo} hint="JPG, PNG, atau WEBP, maks. 8 MB.">
                            <input id="logo" type="file" accept="image/jpeg,image/png,image/webp" onChange={(e) => setData((d) => ({ ...d, logo: e.target.files[0] ?? null, remove_logo: false }))} className={inputCls} />
                        </Field>
                        <div>
                            {preview && <img src={preview} alt="Pratinjau logo" className="h-20 rounded-md" />}
                            {editing && partner.has_logo && !data.logo && (
                                <label className="flex items-center gap-2 text-sm">
                                    <input type="checkbox" checked={data.remove_logo} onChange={(e) => setData('remove_logo', e.target.checked)} className="h-4 w-4 rounded border-[#CBD2DC] text-[#B3202A] focus:ring-[#B3202A]" />
                                    Hapus logo saat ini
                                </label>
                            )}
                        </div>
                    </div>
                </Card>

                <Card className="space-y-5 border-[#E7B9BC] p-6">
                    <div>
                        <h2 className="text-base font-bold">Data internal</h2>
                        <p className="mt-0.5 text-xs text-[#5B6779]">Hanya terlihat oleh pengurus kemitraan. Tidak pernah tampil ke anggota biasa maupun publik.</p>
                    </div>
                    <div className="grid gap-5 sm:grid-cols-2">
                        <Field label="Nama kontak (PIC mitra)" htmlFor="contact_person" error={errors.contact_person}>
                            <Input id="contact_person" value={data.contact_person} onChange={set('contact_person')} />
                        </Field>
                        <Field label="Telepon" htmlFor="contact_phone" error={errors.contact_phone}>
                            <Input id="contact_phone" inputMode="tel" value={data.contact_phone} onChange={set('contact_phone')} />
                        </Field>
                        <Field label="Email" htmlFor="contact_email" error={errors.contact_email}>
                            <Input id="contact_email" type="email" value={data.contact_email} onChange={set('contact_email')} />
                        </Field>
                        <Field label="Bergabung sebagai mitra sejak" htmlFor="joined_at" error={errors.joined_at}>
                            <Input id="joined_at" type="date" value={data.joined_at} onChange={set('joined_at')} />
                        </Field>
                        <div className="sm:col-span-2">
                            <Field label="Catatan internal" htmlFor="notes" error={errors.notes}>
                                <textarea id="notes" rows={2} value={data.notes} onChange={set('notes')} className={inputCls} />
                            </Field>
                        </div>
                    </div>
                </Card>

                <div className="flex gap-2">
                    <Button type="submit" disabled={processing}>
                        {editing ? 'Simpan perubahan' : 'Tambah mitra'}
                    </Button>
                    <Button variant="secondary" href={editing ? `/partners/${partner.id}` : '/partners'}>
                        Batal
                    </Button>
                </div>
            </form>
        </AppLayout>
    );
}
