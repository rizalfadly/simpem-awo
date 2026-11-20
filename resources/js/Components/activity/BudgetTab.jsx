import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Badge, Button, Card, Field, Input, Select, cx } from '@/Components/ui';
import { rupiah } from '@/lib/format';

const keep = { preserveScroll: true };
const TYPES = { income: 'Sumber dana', expense: 'Pengeluaran' };

function ActualInput({ item }) {
    const [value, setValue] = useState(item.actual_amount ?? '');
    const save = () => {
        const next = value === '' ? null : Number(value);
        if (next === item.actual_amount) return;
        router.patch(`/budget-items/${item.id}/actual`, { actual_amount: next }, keep);
    };
    return (
        <input
            type="number"
            min="0"
            inputMode="numeric"
            value={value}
            onChange={(e) => setValue(e.target.value)}
            onBlur={save}
            aria-label={`Realisasi ${item.description}`}
            className="w-32 rounded-md border border-[#CBD2DC] px-2 py-1 text-right text-sm tabular-nums focus:border-[#16202E] focus:outline-none focus:ring-2 focus:ring-[#16202E]/15"
        />
    );
}

export default function BudgetTab({ activity, budget, can }) {
    const blank = { type: 'expense', category: '', description: '', quantity: 1, unit: '', unit_price: '', notes: '' };
    const f = useForm(blank);
    const [editing, setEditing] = useState(null);
    const showActual = can.edit_actual || budget.items.some((i) => i.actual_amount !== null);

    const reset = () => {
        f.setData(blank);
        f.clearErrors();
        setEditing(null);
    };
    const submit = (e) => {
        e.preventDefault();
        const opts = { ...keep, onSuccess: reset };
        editing ? f.put(`/budget-items/${editing}`, opts) : f.post(`/activities/${activity.id}/budget`, opts);
    };
    const edit = (i) => {
        setEditing(i.id);
        f.clearErrors();
        f.setData({ type: i.type, category: i.category, description: i.description, quantity: i.quantity, unit: i.unit ?? '', unit_price: i.unit_price, notes: i.notes ?? '' });
    };
    const remove = (i) => window.confirm(`Hapus "${i.description}"?`) && router.delete(`/budget-items/${i.id}`, keep);

    const t = budget.totals;
    const balance = t.plan_income - t.plan_expense;
    const previewTotal = Math.round((Number(f.data.quantity) || 0) * (Number(f.data.unit_price) || 0));

    const Group = ({ type }) => {
        const rows = budget.items.filter((i) => i.type === type);
        return (
            <Card className="overflow-x-auto">
                <h2 className="border-b border-[#DDE2E9] px-4 py-3 text-base font-bold">{TYPES[type]}</h2>
                {rows.length === 0 ? (
                    <p className="px-4 py-8 text-center text-sm text-[#5B6779]">Belum ada item.</p>
                ) : (
                    <table className="w-full min-w-[640px] text-left text-sm">
                        <thead className="border-b border-[#EEF1F5] text-xs text-[#5B6779]">
                            <tr>
                                <th className="px-4 py-2.5 font-semibold">Uraian</th>
                                <th className="px-3 py-2.5 text-right font-semibold">Jumlah x Harga</th>
                                <th className="px-3 py-2.5 text-right font-semibold">Rencana</th>
                                {showActual && <th className="px-3 py-2.5 text-right font-semibold">Realisasi</th>}
                                {can.edit_budget && <th className="px-3 py-2.5" />}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-[#EEF1F5]">
                            {rows.map((i) => (
                                <tr key={i.id} className={cx(editing === i.id && 'bg-[#FFF7F7]')}>
                                    <td className="px-4 py-3">
                                        <div className="font-semibold">{i.description}</div>
                                        <div className="text-xs text-[#5B6779]">
                                            {i.category}
                                            {i.notes ? ` · ${i.notes}` : ''}
                                        </div>
                                    </td>
                                    <td className="px-3 py-3 text-right tabular-nums text-[#5B6779]">
                                        {i.quantity} {i.unit ?? ''} x {rupiah(i.unit_price)}
                                    </td>
                                    <td className="px-3 py-3 text-right font-semibold tabular-nums">{rupiah(i.planned_amount)}</td>
                                    {showActual && (
                                        <td className="px-3 py-3 text-right tabular-nums">
                                            {can.edit_actual && type === 'expense' ? <ActualInput item={i} /> : i.actual_amount !== null ? rupiah(i.actual_amount) : <span className="text-[#8A94A4]">-</span>}
                                        </td>
                                    )}
                                    {can.edit_budget && (
                                        <td className="px-3 py-3">
                                            <div className="flex justify-end gap-1.5">
                                                <Button variant="secondary" size="sm" onClick={() => edit(i)}>
                                                    Ubah
                                                </Button>
                                                <Button variant="danger" size="sm" onClick={() => remove(i)}>
                                                    Hapus
                                                </Button>
                                            </div>
                                        </td>
                                    )}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}
            </Card>
        );
    };

    return (
        <div className="space-y-6">
            <dl className="grid divide-y divide-[#DDE2E9] overflow-hidden rounded-lg border border-[#DDE2E9] bg-white sm:grid-flow-col sm:auto-cols-fr sm:divide-x sm:divide-y-0">
                <div className="px-5 py-4">
                    <dt className="text-sm text-[#5B6779]">Sumber dana (rencana)</dt>
                    <dd className="mt-1 text-xl font-extrabold tabular-nums">{rupiah(t.plan_income)}</dd>
                </div>
                <div className="px-5 py-4">
                    <dt className="text-sm text-[#5B6779]">Pengeluaran (rencana)</dt>
                    <dd className="mt-1 text-xl font-extrabold tabular-nums">{rupiah(t.plan_expense)}</dd>
                    {t.actual_expense !== null && <dd className="text-xs tabular-nums text-[#5B6779]">Realisasi {rupiah(t.actual_expense)}</dd>}
                </div>
                <div className="px-5 py-4">
                    <dt className="text-sm text-[#5B6779]">Selisih rencana</dt>
                    <dd className={cx('mt-1 text-xl font-extrabold tabular-nums', balance < 0 && 'text-[#B3202A]')}>{rupiah(balance)}</dd>
                    <dd className="mt-0.5">{balance < 0 ? <Badge tone="red">Defisit, dana belum mencukupi</Badge> : <Badge tone="green">Tercukupi</Badge>}</dd>
                </div>
            </dl>

            {can.edit_budget && (
                <form onSubmit={submit} noValidate>
                    <Card className="p-5">
                        <h2 className="text-base font-bold">{editing ? 'Ubah item anggaran' : 'Tambah item anggaran'}</h2>
                        <div className="mt-4 grid gap-4 sm:grid-cols-2">
                            <Field label="Jenis" htmlFor="b_type" error={f.errors.type}>
                                <Select id="b_type" value={f.data.type} onChange={(e) => f.setData('type', e.target.value)}>
                                    {Object.entries(TYPES).map(([k, v]) => (
                                        <option key={k} value={k}>
                                            {v}
                                        </option>
                                    ))}
                                </Select>
                            </Field>
                            <Field label="Kategori" htmlFor="b_cat" error={f.errors.category} hint="Contoh: Konsumsi, Perlengkapan, Publikasi, Transport">
                                <Input id="b_cat" list="b_cats" value={f.data.category} onChange={(e) => f.setData('category', e.target.value)} />
                                <datalist id="b_cats">
                                    {budget.categories.map((c) => (
                                        <option key={c} value={c} />
                                    ))}
                                </datalist>
                            </Field>
                            <div className="sm:col-span-2">
                                <Field label="Uraian" htmlFor="b_desc" error={f.errors.description}>
                                    <Input id="b_desc" value={f.data.description} onChange={(e) => f.setData('description', e.target.value)} />
                                </Field>
                            </div>
                            <Field label="Jumlah" htmlFor="b_qty" error={f.errors.quantity}>
                                <Input id="b_qty" type="number" step="0.01" min="0.01" value={f.data.quantity} onChange={(e) => f.setData('quantity', e.target.value)} />
                            </Field>
                            <Field label="Satuan (opsional)" htmlFor="b_unit" error={f.errors.unit}>
                                <Input id="b_unit" value={f.data.unit} onChange={(e) => f.setData('unit', e.target.value)} placeholder="porsi, pcs, orang" />
                            </Field>
                            <Field label="Harga satuan (Rp)" htmlFor="b_price" error={f.errors.unit_price} hint={`Total: ${rupiah(previewTotal)}`}>
                                <Input id="b_price" type="number" min="0" inputMode="numeric" value={f.data.unit_price} onChange={(e) => f.setData('unit_price', e.target.value)} />
                            </Field>
                            <Field label="Catatan (opsional)" htmlFor="b_notes" error={f.errors.notes}>
                                <Input id="b_notes" value={f.data.notes} onChange={(e) => f.setData('notes', e.target.value)} />
                            </Field>
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
            {!can.edit_budget && activity.status !== 'cancelled' && (
                <p className="text-xs text-[#8A94A4]">Rencana anggaran terkunci setelah proposal diajukan. Realisasi diisi saat menyusun LPJ.</p>
            )}

            <Group type="income" />
            <Group type="expense" />
        </div>
    );
}
