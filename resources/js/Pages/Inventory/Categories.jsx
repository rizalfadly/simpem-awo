import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Badge, Button, Card, Field, Input, PageHeader, cx } from '@/Components/ui';

const keep = { preserveScroll: true };

export default function Categories({ categories }) {
    const blank = { name: '', is_active: true };
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
        editing ? f.put(`/inventory/categories/${editing.id}`, opts) : f.post('/inventory/categories', opts);
    };

    return (
        <AppLayout>
            <Head title="Kategori Barang" />
            <PageHeader title="Kategori barang" description="Pengelompokan jenis barang inventaris." />

            <form onSubmit={submit} noValidate>
                <Card className="mb-6 max-w-2xl p-5">
                    <h2 className="text-base font-bold">{editing ? `Ubah ${editing.name}` : 'Tambah kategori'}</h2>
                    <div className="mt-4">
                        <Field label="Nama" htmlFor="c_name" error={f.errors.name}>
                            <Input id="c_name" value={f.data.name} onChange={(e) => f.setData('name', e.target.value)} />
                        </Field>
                    </div>
                    <label className="mt-4 flex items-center gap-2 text-sm">
                        <input type="checkbox" checked={f.data.is_active} onChange={(e) => f.setData('is_active', e.target.checked)} className="h-4 w-4 rounded border-[#CBD2DC] text-[#B3202A] focus:ring-[#B3202A]" />
                        Kategori aktif
                    </label>
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

            <Card className="max-w-2xl overflow-x-auto">
                <table className="w-full text-left text-sm">
                    <tbody className="divide-y divide-[#EEF1F5]">
                        {categories.map((c) => (
                            <tr key={c.id} className={cx(editing?.id === c.id && 'bg-[#FFF7F7]')}>
                                <td className="px-4 py-3 font-semibold">{c.name}</td>
                                <td className="px-3 py-3">
                                    <Badge tone={c.is_active ? 'green' : 'neutral'}>{c.is_active ? 'Aktif' : 'Nonaktif'}</Badge>
                                </td>
                                <td className="px-3 py-3 tabular-nums text-[#5B6779]">{c.usage} jenis barang</td>
                                <td className="px-4 py-3">
                                    <div className="flex justify-end gap-1.5">
                                        <Button size="sm" variant="secondary" onClick={() => { setEditing(c); f.clearErrors(); f.setData({ name: c.name, is_active: c.is_active }); }}>
                                            Ubah
                                        </Button>
                                        <Button size="sm" variant="danger" onClick={() => window.confirm(`Hapus kategori ${c.name}?`) && router.delete(`/inventory/categories/${c.id}`, keep)}>
                                            Hapus
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </Card>
        </AppLayout>
    );
}
