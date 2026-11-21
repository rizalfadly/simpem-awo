import { Head, useForm } from '@inertiajs/react';
import { useMemo } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Button, Card, Field, Input, PageHeader, Select, inputCls } from '@/Components/ui';
import { rupiah } from '@/lib/format';

export default function Form({ asset, categories }) {
    const editing = !!asset;
    const { data, setData, post, processing, errors } = useForm({
        ...(editing ? { _method: 'put' } : {}),
        name: asset?.name ?? '',
        category_id: asset?.category_id ?? '',
        description: asset?.description ?? '',
        location: asset?.location ?? '',
        unit: asset?.unit ?? 'unit',
        serial_number: asset?.serial_number ?? '',
        acquired_at: asset?.acquired_at ?? '',
        acquisition_source: asset?.acquisition_source ?? '',
        acquisition_cost: asset?.acquisition_cost ?? '',
        qty_good: asset?.qty_good ?? 1,
        qty_minor: asset?.qty_minor ?? 0,
        qty_broken: asset?.qty_broken ?? 0,
        qty_lost: asset?.qty_lost ?? 0,
        is_loanable: asset?.is_loanable ?? true,
        photo: null,
        remove_photo: false,
    });
    const set = (k) => (e) => setData(k, e.target.value);
    const total = ['qty_good', 'qty_minor', 'qty_broken', 'qty_lost'].reduce((s, k) => s + (Number(data[k]) || 0), 0);
    const preview = useMemo(() => (data.photo ? URL.createObjectURL(data.photo) : null), [data.photo]);

    return (
        <AppLayout>
            <Head title={editing ? 'Ubah Barang' : 'Tambah Barang'} />
            <PageHeader
                title={editing ? `Ubah ${asset.code}` : 'Tambah barang'}
                description="Satu baris mewakili satu jenis barang. Untuk barang jumlah banyak (mis. kursi), isi jumlahnya per kondisi."
            />

            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    post(editing ? `/inventory/${asset.id}` : '/inventory', { forceFormData: true });
                }}
                noValidate
            >
                <Card className="max-w-3xl space-y-6 p-6">
                    <div className="grid gap-5 sm:grid-cols-2">
                        <div className="sm:col-span-2">
                            <Field label="Nama barang" htmlFor="name" error={errors.name}>
                                <Input id="name" value={data.name} onChange={set('name')} />
                            </Field>
                        </div>
                        <Field label="Kategori" htmlFor="category_id" error={errors.category_id}>
                            <Select id="category_id" value={data.category_id} onChange={set('category_id')}>
                                <option value="">Tanpa kategori</option>
                                {categories.map((c) => (
                                    <option key={c.id} value={c.id}>
                                        {c.name}
                                    </option>
                                ))}
                            </Select>
                        </Field>
                        <Field label="Lokasi penyimpanan" htmlFor="location" error={errors.location}>
                            <Input id="location" value={data.location} onChange={set('location')} placeholder="Gudang sekretariat" />
                        </Field>
                        <Field label="Satuan" htmlFor="unit" error={errors.unit}>
                            <Input id="unit" value={data.unit} onChange={set('unit')} placeholder="unit, buah, set" />
                        </Field>
                        <Field label="Nomor seri (opsional)" htmlFor="serial_number" error={errors.serial_number}>
                            <Input id="serial_number" value={data.serial_number} onChange={set('serial_number')} />
                        </Field>
                    </div>

                    <fieldset className="rounded-md border border-[#DDE2E9] p-4">
                        <legend className="px-1 text-sm font-semibold">Jumlah per kondisi (total {total})</legend>
                        <div className="grid gap-4 sm:grid-cols-4">
                            {[
                                ['qty_good', 'Baik'],
                                ['qty_minor', 'Rusak ringan'],
                                ['qty_broken', 'Rusak berat'],
                                ['qty_lost', 'Hilang'],
                            ].map(([k, label]) => (
                                <Field key={k} label={label} htmlFor={k} error={errors[k]}>
                                    <Input id={k} type="number" min="0" value={data[k]} onChange={set(k)} />
                                </Field>
                            ))}
                        </div>
                        <p className="mt-3 text-xs text-[#5B6779]">
                            Hanya unit berkondisi <strong>Baik</strong> yang bisa dipinjamkan.
                            {editing && asset.lent > 0 && ` Saat ini ${asset.lent} unit sedang dipinjam dan dihitung sebagai Baik.`} Perubahan jumlah tercatat di riwayat.
                        </p>
                    </fieldset>

                    <div className="grid gap-5 sm:grid-cols-3">
                        <Field label="Tanggal diperoleh" htmlFor="acquired_at" error={errors.acquired_at}>
                            <Input id="acquired_at" type="date" value={data.acquired_at} onChange={set('acquired_at')} />
                        </Field>
                        <Field label="Sumber" htmlFor="acquisition_source" error={errors.acquisition_source} hint="Pembelian, hibah, donasi, dll.">
                            <Input id="acquisition_source" value={data.acquisition_source} onChange={set('acquisition_source')} />
                        </Field>
                        <Field label="Nilai perolehan total (Rp)" htmlFor="acquisition_cost" error={errors.acquisition_cost} hint={data.acquisition_cost ? rupiah(Number(data.acquisition_cost)) : undefined}>
                            <Input id="acquisition_cost" type="number" min="0" value={data.acquisition_cost} onChange={set('acquisition_cost')} />
                        </Field>
                    </div>

                    <Field label="Keterangan (opsional)" htmlFor="description" error={errors.description}>
                        <textarea id="description" rows={3} value={data.description} onChange={set('description')} className={inputCls} />
                    </Field>

                    <label className="flex items-center gap-2 text-sm">
                        <input type="checkbox" checked={data.is_loanable} onChange={(e) => setData('is_loanable', e.target.checked)} className="h-4 w-4 rounded border-[#CBD2DC] text-[#B3202A] focus:ring-[#B3202A]" />
                        Boleh dipinjam anggota
                    </label>

                    <Field label="Foto (opsional)" htmlFor="photo" error={errors.photo} hint="JPG, PNG, atau WEBP, maks. 8 MB.">
                        <input id="photo" type="file" accept="image/jpeg,image/png,image/webp" onChange={(e) => setData((d) => ({ ...d, photo: e.target.files[0] ?? null, remove_photo: false }))} className={inputCls} />
                    </Field>
                    {preview && <img src={preview} alt="Pratinjau" className="max-h-40 rounded-md" />}
                    {editing && asset.has_photo && !data.photo && (
                        <label className="flex items-center gap-2 text-sm">
                            <input type="checkbox" checked={data.remove_photo} onChange={(e) => setData('remove_photo', e.target.checked)} className="h-4 w-4 rounded border-[#CBD2DC] text-[#B3202A] focus:ring-[#B3202A]" />
                            Hapus foto saat ini
                        </label>
                    )}

                    <div className="flex gap-2 border-t border-[#EEF1F5] pt-5">
                        <Button type="submit" disabled={processing}>
                            {editing ? 'Simpan perubahan' : 'Tambah barang'}
                        </Button>
                        <Button variant="secondary" href={editing ? `/inventory/${asset.id}` : '/inventory'}>
                            Batal
                        </Button>
                    </div>
                </Card>
            </form>
        </AppLayout>
    );
}
