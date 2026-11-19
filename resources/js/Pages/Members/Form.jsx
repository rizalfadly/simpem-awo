import { Head, useForm } from '@inertiajs/react';
import { useMemo } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import Avatar from '@/Components/Avatar';
import { Button, Card, Field, Input, PageHeader, Select, inputCls } from '@/Components/ui';

export default function Form({ member, statuses }) {
    const editing = !!member;
    const today = new Date().toLocaleDateString('en-CA');

    const { data, setData, post, processing, errors } = useForm({
        ...(editing ? { _method: 'put' } : { status: 'active' }),
        name: member?.name ?? '',
        nickname: member?.nickname ?? '',
        gender: member?.gender ?? '',
        birth_place: member?.birth_place ?? '',
        birth_date: member?.birth_date ?? '',
        phone: member?.phone ?? '',
        email: member?.email ?? '',
        address: member?.address ?? '',
        joined_at: member?.joined_at ?? today,
        photo: null,
        remove_photo: false,
    });

    const preview = useMemo(() => (data.photo ? URL.createObjectURL(data.photo) : null), [data.photo]);
    const shownPhoto = data.remove_photo ? null : (preview ?? member?.photo_url);

    const submit = (e) => {
        e.preventDefault();
        // Unggah berkas + method spoofing: selalu POST multipart.
        post(editing ? `/members/${member.id}` : '/members', { forceFormData: true });
    };

    const set = (k) => (e) => setData(k, e.target.value);

    return (
        <AppLayout>
            <Head title={editing ? 'Ubah Anggota' : 'Tambah Anggota'} />
            <PageHeader
                title={editing ? 'Ubah data anggota' : 'Tambah anggota'}
                description={editing ? member.nomor_anggota : 'Nomor anggota dibuat otomatis saat disimpan.'}
            />

            <form onSubmit={submit} noValidate>
                <Card className="max-w-3xl space-y-6 p-6">
                    <div className="flex items-center gap-5">
                        <Avatar name={data.name} src={shownPhoto} className="h-24 w-24 rounded-lg" textClass="text-3xl" />
                        <div className="flex-1">
                            <Field label="Foto (opsional)" htmlFor="photo" error={errors.photo} hint="JPG, PNG, atau WEBP. Maksimal 2 MB.">
                                <input
                                    id="photo"
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                    onChange={(e) => setData((d) => ({ ...d, photo: e.target.files[0] ?? null, remove_photo: false }))}
                                    className={inputCls}
                                />
                            </Field>
                            {editing && member.photo_url && !data.photo && (
                                <label className="mt-2 flex items-center gap-2 text-sm">
                                    <input
                                        type="checkbox"
                                        checked={data.remove_photo}
                                        onChange={(e) => setData('remove_photo', e.target.checked)}
                                        className="h-4 w-4 rounded border-[#CBD2DC] text-[#B3202A] focus:ring-[#B3202A]"
                                    />
                                    Hapus foto saat ini
                                </label>
                            )}
                        </div>
                    </div>

                    <div className="grid gap-5 sm:grid-cols-2">
                        <div className="sm:col-span-2">
                            <Field label="Nama lengkap" htmlFor="name" error={errors.name}>
                                <Input id="name" value={data.name} onChange={set('name')} />
                            </Field>
                        </div>
                        <Field label="Nama panggilan" htmlFor="nickname" error={errors.nickname}>
                            <Input id="nickname" value={data.nickname} onChange={set('nickname')} />
                        </Field>
                        <Field label="Jenis kelamin" htmlFor="gender" error={errors.gender}>
                            <Select id="gender" value={data.gender} onChange={set('gender')}>
                                <option value="">Tidak diisi</option>
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </Select>
                        </Field>
                        <Field label="Tempat lahir" htmlFor="birth_place" error={errors.birth_place}>
                            <Input id="birth_place" value={data.birth_place} onChange={set('birth_place')} />
                        </Field>
                        <Field label="Tanggal lahir" htmlFor="birth_date" error={errors.birth_date}>
                            <Input id="birth_date" type="date" value={data.birth_date} onChange={set('birth_date')} />
                        </Field>
                        <Field label="Telepon / WhatsApp" htmlFor="phone" error={errors.phone}>
                            <Input id="phone" inputMode="tel" value={data.phone} onChange={set('phone')} />
                        </Field>
                        <Field label="Email" htmlFor="email" error={errors.email}>
                            <Input id="email" type="email" value={data.email} onChange={set('email')} />
                        </Field>
                        <div className="sm:col-span-2">
                            <Field label="Alamat" htmlFor="address" error={errors.address}>
                                <textarea id="address" rows={3} value={data.address} onChange={set('address')} className={inputCls} />
                            </Field>
                        </div>
                        <Field label="Tanggal bergabung" htmlFor="joined_at" error={errors.joined_at} hint="Tahun bergabung menjadi bagian dari nomor anggota.">
                            <Input id="joined_at" type="date" value={data.joined_at} onChange={set('joined_at')} disabled={editing} />
                        </Field>
                        {!editing && (
                            <Field label="Status awal" htmlFor="status" error={errors.status}>
                                <Select id="status" value={data.status} onChange={set('status')}>
                                    {Object.entries(statuses).map(([k, v]) => (
                                        <option key={k} value={k}>
                                            {v}
                                        </option>
                                    ))}
                                </Select>
                            </Field>
                        )}
                    </div>

                    <div className="flex gap-2 border-t border-[#EEF1F5] pt-5">
                        <Button type="submit" disabled={processing}>
                            {editing ? 'Simpan perubahan' : 'Simpan anggota'}
                        </Button>
                        <Button variant="secondary" href={editing ? `/members/${member.id}` : '/members'}>
                            Batal
                        </Button>
                    </div>
                </Card>
            </form>
        </AppLayout>
    );
}
