import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Badge, Button, Card, Field, Input, inputCls } from '@/Components/ui';
import { rupiah } from '@/lib/format';

const keep = { preserveScroll: true };

export default function PartnerProducts({ partner, products, can }) {
    const blank = { _method: 'post', name: '', description: '', price: '', price_note: '', is_available: true, photo: null, remove_photo: false };
    const f = useForm(blank);
    const [editing, setEditing] = useState(null);

    const reset = () => {
        f.setData(blank);
        f.clearErrors();
        setEditing(null);
    };
    const submit = (e) => {
        e.preventDefault();
        f.post(editing ? `/partners/products/${editing.id}` : `/partners/${partner.id}/products`, { ...keep, forceFormData: true, onSuccess: reset });
    };
    const edit = (p) => {
        setEditing(p);
        f.clearErrors();
        f.setData({ _method: 'put', name: p.name, description: p.description ?? '', price: p.price ?? '', price_note: p.price_note ?? '', is_available: p.is_available, photo: null, remove_photo: false });
    };

    return (
        <div className="space-y-6">
            {can.manage && (
                <form onSubmit={submit} noValidate>
                    <Card className="p-5">
                        <h2 className="text-base font-bold">{editing ? `Ubah ${editing.name}` : 'Tambah produk'}</h2>
                        <div className="mt-4 grid gap-4 sm:grid-cols-2">
                            <div className="sm:col-span-2">
                                <Field label="Nama produk" htmlFor="p_name" error={f.errors.name}>
                                    <Input id="p_name" value={f.data.name} onChange={(e) => f.setData('name', e.target.value)} />
                                </Field>
                            </div>
                            <Field label="Harga (Rp, opsional)" htmlFor="p_price" error={f.errors.price} hint={f.data.price ? rupiah(Number(f.data.price)) : undefined}>
                                <Input id="p_price" type="number" min="0" value={f.data.price} onChange={(e) => f.setData('price', e.target.value)} />
                            </Field>
                            <Field label="Keterangan harga (opsional)" htmlFor="p_note" error={f.errors.price_note} hint="Mis. mulai dari, per porsi">
                                <Input id="p_note" value={f.data.price_note} onChange={(e) => f.setData('price_note', e.target.value)} />
                            </Field>
                            <div className="sm:col-span-2">
                                <Field label="Deskripsi (opsional)" htmlFor="p_desc" error={f.errors.description}>
                                    <textarea id="p_desc" rows={2} value={f.data.description} onChange={(e) => f.setData('description', e.target.value)} className={inputCls} />
                                </Field>
                            </div>
                            <Field label="Foto (opsional)" htmlFor="p_photo" error={f.errors.photo} hint="JPG, PNG, atau WEBP, maks. 8 MB.">
                                <input id="p_photo" type="file" accept="image/jpeg,image/png,image/webp" onChange={(e) => f.setData((d) => ({ ...d, photo: e.target.files[0] ?? null, remove_photo: false }))} className={inputCls} />
                            </Field>
                            <div className="flex flex-col justify-end gap-2 pb-1">
                                <label className="flex items-center gap-2 text-sm">
                                    <input type="checkbox" checked={f.data.is_available} onChange={(e) => f.setData('is_available', e.target.checked)} className="h-4 w-4 rounded border-[#CBD2DC] text-[#B3202A] focus:ring-[#B3202A]" />
                                    Tersedia
                                </label>
                                {editing?.has_photo && !f.data.photo && (
                                    <label className="flex items-center gap-2 text-sm">
                                        <input type="checkbox" checked={f.data.remove_photo} onChange={(e) => f.setData('remove_photo', e.target.checked)} className="h-4 w-4 rounded border-[#CBD2DC] text-[#B3202A] focus:ring-[#B3202A]" />
                                        Hapus foto saat ini
                                    </label>
                                )}
                            </div>
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

            {products.length === 0 ? (
                <p className="rounded-lg border border-dashed border-[#CBD2DC] bg-white px-6 py-10 text-center text-sm text-[#5B6779]">Belum ada produk.</p>
            ) : (
                <ul className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {products.map((p) => (
                        <li key={p.id} className="overflow-hidden rounded-lg border border-[#DDE2E9] bg-white">
                            <div className="aspect-[4/3] bg-[#E9EDF2]">
                                {p.photo ? <img src={p.photo} alt={p.name} loading="lazy" className="h-full w-full object-cover" /> : <div className="flex h-full items-center justify-center text-xs text-[#8A94A4]">Tanpa foto</div>}
                            </div>
                            <div className="p-4">
                                <div className="flex items-start justify-between gap-2">
                                    <h3 className="text-sm font-bold leading-tight">{p.name}</h3>
                                    {!p.is_available && <Badge tone="neutral">Habis</Badge>}
                                </div>
                                <p className="mt-1 text-sm font-semibold tabular-nums">
                                    {p.price !== null ? rupiah(p.price) : <span className="font-normal text-[#8A94A4]">Harga hubungi mitra</span>}
                                    {p.price_note && <span className="ml-1 text-xs font-normal text-[#5B6779]">{p.price_note}</span>}
                                </p>
                                {p.description && <p className="mt-1.5 text-xs text-[#3B4658]">{p.description}</p>}
                                {can.manage && (
                                    <div className="mt-3 flex gap-1.5">
                                        <Button size="sm" variant="secondary" onClick={() => edit(p)}>
                                            Ubah
                                        </Button>
                                        <Button size="sm" variant="danger" onClick={() => window.confirm(`Hapus ${p.name}?`) && router.delete(`/partners/products/${p.id}`, keep)}>
                                            Hapus
                                        </Button>
                                    </div>
                                )}
                            </div>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
