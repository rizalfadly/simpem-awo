import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Badge, Button, Card, Field, Input, PageHeader, Select, cx } from '@/Components/ui';

const keep = { preserveScroll: true };

export default function Categories({ categories, types }) {
    const blank = { name: '', type: 'expense', is_active: true, sort_order: 0 };
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
        editing ? f.put(`/finance/categories/${editing.id}`, opts) : f.post('/finance/categories', opts);
    };
    const edit = (c) => {
        setEditing(c);
        f.clearErrors();
        f.setData({ name: c.name, type: c.type, is_active: c.is_active, sort_order: c.sort_order });
    };

    return (
        <AppLayout>
            <Head title="Kategori Transaksi" />
            <PageHeader title="Kategori transaksi" description="Pengelompokan kas masuk dan kas keluar pada laporan." />

            <form onSubmit={submit} noValidate>
                <Card className="mb-6 p-5">
                    <h2 className="text-base font-bold">{editing ? `Ubah kategori ${editing.name}` : 'Tambah kategori'}</h2>
                    <div className="mt-4 grid gap-4 sm:grid-cols-4">
                        <div className="sm:col-span-2">
                            <Field label="Nama" htmlFor="c_name" error={f.errors.name}>
                                <Input id="c_name" value={f.data.name} onChange={(e) => f.setData('name', e.target.value)} />
                            </Field>
                        </div>
                        <Field label="Jenis" htmlFor="c_type" error={f.errors.type} hint={editing ? 'Tidak dapat diubah.' : undefined}>
                            <Select id="c_type" value={f.data.type} disabled={!!editing} onChange={(e) => f.setData('type', e.target.value)}>
                                {Object.entries(types).map(([k, v]) => (
                                    <option key={k} value={k}>
                                        {v}
                                    </option>
                                ))}
                            </Select>
                        </Field>
                        <Field label="Urutan" htmlFor="c_order" error={f.errors.sort_order}>
                            <Input id="c_order" type="number" min="0" value={f.data.sort_order} onChange={(e) => f.setData('sort_order', e.target.value)} />
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

            {Object.entries(types).map(([type, label]) => (
                <Card key={type} className="mb-6 overflow-x-auto">
                    <h2 className="border-b border-[#DDE2E9] px-4 py-3 text-base font-bold">{label}</h2>
                    <table className="w-full min-w-[520px] text-left text-sm">
                        <tbody className="divide-y divide-[#EEF1F5]">
                            {categories.filter((c) => c.type === type).map((c) => (
                                <tr key={c.id} className={cx(editing?.id === c.id && 'bg-[#FFF7F7]')}>
                                    <td className="px-4 py-3 font-semibold">{c.name}</td>
                                    <td className="px-3 py-3">
                                        <Badge tone={c.is_active ? 'green' : 'neutral'}>{c.is_active ? 'Aktif' : 'Nonaktif'}</Badge>
                                    </td>
                                    <td className="px-3 py-3 tabular-nums text-[#5B6779]">{c.usage} transaksi</td>
                                    <td className="px-4 py-3">
                                        <div className="flex justify-end gap-1.5">
                                            <Button size="sm" variant="secondary" onClick={() => edit(c)}>
                                                Ubah
                                            </Button>
                                            <Button size="sm" variant="danger" onClick={() => window.confirm(`Hapus kategori ${c.name}?`) && router.delete(`/finance/categories/${c.id}`, keep)}>
                                                Hapus
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </Card>
            ))}
        </AppLayout>
    );
}
