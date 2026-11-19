import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Badge, Button, Card, Field, Input, PageHeader, Select, cx } from '@/Components/ui';
import { useCan } from '@/lib/can';

const keep = { preserveScroll: true };

function Check({ label, checked, onChange }) {
    return (
        <label className="flex items-center gap-2 text-sm">
            <input type="checkbox" checked={checked} onChange={(e) => onChange(e.target.checked)} className="h-4 w-4 rounded border-[#CBD2DC] text-[#B3202A] focus:ring-[#B3202A]" />
            {label}
        </label>
    );
}

/** Form tambah/ubah + tabel. Dipakai ketiga tab. */
function Panel({ title, blank, endpoint, items, manage, fields, columns, toForm }) {
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
        editing ? f.put(`${endpoint}/${editing}`, opts) : f.post(endpoint, opts);
    };
    const edit = (item) => {
        setEditing(item.id);
        f.clearErrors();
        f.setData(toForm(item));
    };
    const remove = (item) => window.confirm(`Hapus ${item.name}?`) && router.delete(`${endpoint}/${item.id}`, keep);

    return (
        <div className="space-y-6">
            {manage && (
                <form onSubmit={submit} noValidate>
                    <Card className="p-5">
                        <h2 className="text-base font-bold">{editing ? `Ubah ${title}` : `Tambah ${title}`}</h2>
                        <div className="mt-4 grid gap-4 sm:grid-cols-2">{fields(f)}</div>
                        <div className="mt-5 flex gap-2">
                            <Button type="submit" disabled={f.processing}>
                                {editing ? 'Simpan perubahan' : 'Tambah'}
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

            <Card className="overflow-x-auto">
                <table className="w-full min-w-[560px] text-left text-sm">
                    <thead className="border-b border-[#DDE2E9] text-xs text-[#5B6779]">
                        <tr>
                            {columns.map((c) => (
                                <th key={c.label} className="px-4 py-3 font-semibold">
                                    {c.label}
                                </th>
                            ))}
                            <th className="px-4 py-3 text-right font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-[#EEF1F5]">
                        {items.length === 0 && (
                            <tr>
                                <td colSpan={columns.length + 1} className="px-4 py-10 text-center text-[#5B6779]">
                                    Belum ada data.
                                </td>
                            </tr>
                        )}
                        {items.map((it) => (
                            <tr key={it.id} className={cx(editing === it.id && 'bg-[#FFF7F7]')}>
                                {columns.map((c) => (
                                    <td key={c.label} className="px-4 py-3">
                                        {c.render(it)}
                                    </td>
                                ))}
                                <td className="px-4 py-3">
                                    {manage && (
                                        <div className="flex justify-end gap-1.5">
                                            <Button variant="secondary" size="sm" onClick={() => edit(it)}>
                                                Ubah
                                            </Button>
                                            <Button variant="danger" size="sm" onClick={() => remove(it)}>
                                                Hapus
                                            </Button>
                                        </div>
                                    )}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </Card>
        </div>
    );
}

const active = (it) => <Badge tone={it.is_active ? 'green' : 'neutral'}>{it.is_active ? 'Aktif' : 'Nonaktif'}</Badge>;

export default function Index({ divisions, positions, periods, scopes }) {
    const can = useCan();
    const manage = can('organization.manage');
    const [tab, setTab] = useState('periods');
    const tabs = [
        ['periods', 'Periode'],
        ['divisions', 'Divisi'],
        ['positions', 'Jabatan'],
    ];

    return (
        <AppLayout>
            <Head title="Divisi, Jabatan, Periode" />
            <PageHeader title="Divisi, Jabatan, Periode" description="Data master untuk menyusun struktur dan riwayat kepengurusan." />

            <div role="tablist" aria-label="Data master" className="mb-6 flex gap-1 border-b border-[#DDE2E9]">
                {tabs.map(([key, label]) => (
                    <button
                        key={key}
                        role="tab"
                        type="button"
                        aria-selected={tab === key}
                        onClick={() => setTab(key)}
                        className={cx(
                            '-mb-px border-b-2 px-4 py-2 text-sm font-semibold',
                            tab === key ? 'border-[#B3202A] text-[#16202E]' : 'border-transparent text-[#5B6779] hover:text-[#16202E]',
                        )}
                    >
                        {label}
                    </button>
                ))}
            </div>

            {tab === 'periods' && (
                <Panel
                    key="periods"
                    title="periode"
                    endpoint="/organisasi/periode"
                    manage={manage}
                    items={periods}
                    blank={{ name: '', start_date: '', end_date: '', is_current: false }}
                    toForm={(p) => ({ name: p.name, start_date: p.start_date, end_date: p.end_date, is_current: p.is_current })}
                    fields={(f) => (
                        <>
                            <Field label="Nama periode" htmlFor="p_name" error={f.errors.name} hint="Contoh: 2026/2027">
                                <Input id="p_name" value={f.data.name} onChange={(e) => f.setData('name', e.target.value)} />
                            </Field>
                            <div />
                            <Field label="Mulai" htmlFor="p_start" error={f.errors.start_date}>
                                <Input id="p_start" type="date" value={f.data.start_date} onChange={(e) => f.setData('start_date', e.target.value)} />
                            </Field>
                            <Field label="Berakhir" htmlFor="p_end" error={f.errors.end_date}>
                                <Input id="p_end" type="date" value={f.data.end_date} onChange={(e) => f.setData('end_date', e.target.value)} />
                            </Field>
                            <div className="sm:col-span-2">
                                <Check label="Jadikan periode berjalan" checked={f.data.is_current} onChange={(v) => f.setData('is_current', v)} />
                                <p className="mt-1 text-xs text-[#5B6779]">Periode berjalan sebelumnya akan ditutup, dan jabatan yang masih terbuka di dalamnya diakhiri otomatis.</p>
                            </div>
                        </>
                    )}
                    columns={[
                        { label: 'Periode', render: (p) => <span className="font-semibold">{p.name}</span> },
                        { label: 'Masa', render: (p) => <span className="tabular-nums">{p.range}</span> },
                        { label: 'Status', render: (p) => (p.is_current ? <Badge tone="green">Berjalan</Badge> : <span className="text-[#8A94A4]">-</span>) },
                        { label: 'Penugasan', render: (p) => <span className="tabular-nums">{p.assignments_count}</span> },
                    ]}
                />
            )}

            {tab === 'divisions' && (
                <Panel
                    key="divisions"
                    title="divisi"
                    endpoint="/organisasi/divisi"
                    manage={manage}
                    items={divisions}
                    blank={{ name: '', description: '', is_active: true }}
                    toForm={(d) => ({ name: d.name, description: d.description ?? '', is_active: d.is_active })}
                    fields={(f) => (
                        <>
                            <Field label="Nama divisi" htmlFor="d_name" error={f.errors.name}>
                                <Input id="d_name" value={f.data.name} onChange={(e) => f.setData('name', e.target.value)} />
                            </Field>
                            <Field label="Deskripsi (opsional)" htmlFor="d_desc" error={f.errors.description}>
                                <Input id="d_desc" value={f.data.description} onChange={(e) => f.setData('description', e.target.value)} />
                            </Field>
                            <div className="sm:col-span-2">
                                <Check label="Divisi aktif" checked={f.data.is_active} onChange={(v) => f.setData('is_active', v)} />
                            </div>
                        </>
                    )}
                    columns={[
                        { label: 'Divisi', render: (d) => <span className="font-semibold">{d.name}</span> },
                        { label: 'Deskripsi', render: (d) => d.description ?? <span className="text-[#8A94A4]">-</span> },
                        { label: 'Status', render: active },
                        { label: 'Penugasan', render: (d) => <span className="tabular-nums">{d.assignments_count}</span> },
                    ]}
                />
            )}

            {tab === 'positions' && (
                <Panel
                    key="positions"
                    title="jabatan"
                    endpoint="/organisasi/jabatan"
                    manage={manage}
                    items={positions}
                    blank={{ name: '', scope: 'division', max_holders: '', sort_order: 0, is_active: true }}
                    toForm={(p) => ({ name: p.name, scope: p.scope, max_holders: p.max_holders ?? '', sort_order: p.sort_order, is_active: p.is_active })}
                    fields={(f) => (
                        <>
                            <Field label="Nama jabatan" htmlFor="j_name" error={f.errors.name}>
                                <Input id="j_name" value={f.data.name} onChange={(e) => f.setData('name', e.target.value)} />
                            </Field>
                            <Field label="Tingkat" htmlFor="j_scope" error={f.errors.scope} hint="Tingkat divisi wajib dipasangkan dengan divisi saat ditetapkan.">
                                <Select id="j_scope" value={f.data.scope} onChange={(e) => f.setData('scope', e.target.value)}>
                                    {Object.entries(scopes).map(([k, v]) => (
                                        <option key={k} value={k}>
                                            {v}
                                        </option>
                                    ))}
                                </Select>
                            </Field>
                            <Field label="Maks. pemegang" htmlFor="j_max" error={f.errors.max_holders} hint="Per periode (dan per divisi). Kosongkan jika tanpa batas.">
                                <Input id="j_max" type="number" min="1" value={f.data.max_holders} onChange={(e) => f.setData('max_holders', e.target.value)} />
                            </Field>
                            <Field label="Urutan tampil" htmlFor="j_order" error={f.errors.sort_order}>
                                <Input id="j_order" type="number" min="0" value={f.data.sort_order} onChange={(e) => f.setData('sort_order', e.target.value)} />
                            </Field>
                            <div className="sm:col-span-2">
                                <Check label="Jabatan aktif" checked={f.data.is_active} onChange={(v) => f.setData('is_active', v)} />
                            </div>
                        </>
                    )}
                    columns={[
                        { label: 'Jabatan', render: (p) => <span className="font-semibold">{p.name}</span> },
                        { label: 'Tingkat', render: (p) => p.scope_label },
                        { label: 'Maks.', render: (p) => <span className="tabular-nums">{p.max_holders ?? 'Bebas'}</span> },
                        { label: 'Status', render: active },
                        { label: 'Penugasan', render: (p) => <span className="tabular-nums">{p.assignments_count}</span> },
                    ]}
                />
            )}
        </AppLayout>
    );
}
