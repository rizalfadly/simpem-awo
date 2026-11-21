import { Head, useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Button, Card, Field, Input, PageHeader, Select, inputCls } from '@/Components/ui';

export default function Create({ assets, activities, members, staff, defaults, max_days }) {
    const today = new Date().toLocaleDateString('en-CA');
    const { data, setData, post, processing, errors } = useForm({
        borrower_member_id: defaults.borrower_member_id ?? '',
        activity_id: defaults.activity_id ?? '',
        purpose: '',
        loan_date: today,
        due_date: today,
        notes: '',
        items: defaults.asset_id ? [{ asset_id: defaults.asset_id, quantity: 1 }] : [],
    });
    const [avail, setAvail] = useState(null);
    const [pick, setPick] = useState('');

    // Ketersediaan sesuai rentang tanggal yang dipilih (umpan balik langsung; server tetap memvalidasi).
    useEffect(() => {
        if (!data.loan_date || !data.due_date || data.due_date < data.loan_date) {
            setAvail(null);
            return;
        }
        const ctrl = new AbortController();
        fetch(`/inventory/availability?from=${data.loan_date}&to=${data.due_date}`, { headers: { Accept: 'application/json' }, signal: ctrl.signal })
            .then((r) => (r.ok ? r.json() : null))
            .then(setAvail)
            .catch(() => {});
        return () => ctrl.abort();
    }, [data.loan_date, data.due_date]);

    const byId = Object.fromEntries(assets.map((a) => [a.id, a]));
    const left = (id) => (avail ? avail[id] : byId[id]?.available);
    const used = data.items.map((i) => Number(i.asset_id));

    const addItem = () => {
        if (!pick) return;
        setData('items', [...data.items, { asset_id: Number(pick), quantity: 1 }]);
        setPick('');
    };
    const setQty = (idx, quantity) => setData('items', data.items.map((it, i) => (i === idx ? { ...it, quantity } : it)));
    const removeItem = (idx) => setData('items', data.items.filter((_, i) => i !== idx));

    return (
        <AppLayout>
            <Head title="Ajukan Peminjaman" />
            <PageHeader title="Ajukan peminjaman" description={`Pengajuan akan disetujui pengelola. Barang baru dipesan setelah disetujui. Maksimal ${max_days} hari.`} />

            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    post('/inventory/loans');
                }}
                noValidate
            >
                <Card className="max-w-3xl space-y-6 p-6">
                    {staff && (
                        <Field label="Peminjam" htmlFor="borrower" error={errors.borrower_member_id} hint="Sebagai pengelola, Anda dapat mencatat atas nama anggota lain.">
                            <Select id="borrower" value={data.borrower_member_id} onChange={(e) => setData('borrower_member_id', e.target.value)}>
                                <option value="">Pilih anggota</option>
                                {members.map((m) => (
                                    <option key={m.id} value={m.id}>
                                        {m.name} ({m.nomor_anggota})
                                    </option>
                                ))}
                            </Select>
                        </Field>
                    )}

                    <div className="grid gap-5 sm:grid-cols-2">
                        <Field label="Tanggal pinjam" htmlFor="loan_date" error={errors.loan_date}>
                            <Input id="loan_date" type="date" min={today} value={data.loan_date} onChange={(e) => setData('loan_date', e.target.value)} />
                        </Field>
                        <Field label="Tanggal kembali" htmlFor="due_date" error={errors.due_date}>
                            <Input id="due_date" type="date" min={data.loan_date || today} value={data.due_date} onChange={(e) => setData('due_date', e.target.value)} />
                        </Field>
                        <div className="sm:col-span-2">
                            <Field label="Tujuan peminjaman" htmlFor="purpose" error={errors.purpose}>
                                <Input id="purpose" value={data.purpose} onChange={(e) => setData('purpose', e.target.value)} />
                            </Field>
                        </div>
                        <div className="sm:col-span-2">
                            <Field label="Kegiatan terkait (opsional)" htmlFor="activity" error={errors.activity_id}>
                                <Select id="activity" value={data.activity_id} onChange={(e) => setData('activity_id', e.target.value)}>
                                    <option value="">Tidak terkait kegiatan</option>
                                    {activities.map((a) => (
                                        <option key={a.id} value={a.id}>
                                            {a.code} {a.title}
                                        </option>
                                    ))}
                                </Select>
                            </Field>
                        </div>
                    </div>

                    <fieldset>
                        <legend className="mb-2 text-sm font-semibold">Barang yang dipinjam</legend>
                        {errors.items && (
                            <p role="alert" className="mb-2 rounded-md bg-[#FBEFF0] px-3 py-2 text-sm font-medium text-[#8F1B24]">
                                {errors.items}
                            </p>
                        )}

                        {data.items.length > 0 && (
                            <ul className="mb-3 divide-y divide-[#EEF1F5] rounded-md border border-[#DDE2E9]">
                                {data.items.map((it, idx) => {
                                    const a = byId[it.asset_id];
                                    const free = left(it.asset_id);
                                    const err = errors[`items.${idx}.quantity`] || errors[`items.${idx}.asset_id`];
                                    return (
                                        <li key={it.asset_id} className="flex flex-wrap items-center gap-3 px-3 py-2.5">
                                            <div className="min-w-0 flex-1">
                                                <p className="text-sm font-semibold">{a?.name}</p>
                                                <p className="text-xs text-[#5B6779]">
                                                    {a?.code} · tersedia {free ?? '...'} {a?.unit}
                                                    {free !== undefined && Number(it.quantity) > free && <span className="ml-1 font-semibold text-[#B3202A]">(melebihi stok)</span>}
                                                </p>
                                                {err && <p role="alert" className="mt-0.5 text-xs font-medium text-[#B3202A]">{err}</p>}
                                            </div>
                                            <input
                                                type="number"
                                                min="1"
                                                value={it.quantity}
                                                onChange={(e) => setQty(idx, e.target.value)}
                                                aria-label={`Jumlah ${a?.name}`}
                                                className="w-24 rounded-md border border-[#CBD2DC] px-2 py-1.5 text-right text-sm tabular-nums focus:border-[#16202E] focus:outline-none focus:ring-2 focus:ring-[#16202E]/15"
                                            />
                                            <Button size="sm" variant="danger" onClick={() => removeItem(idx)}>
                                                Hapus
                                            </Button>
                                        </li>
                                    );
                                })}
                            </ul>
                        )}

                        <div className="flex gap-2">
                            <Select value={pick} onChange={(e) => setPick(e.target.value)} aria-label="Pilih barang" className="flex-1">
                                <option value="">Pilih barang...</option>
                                {assets
                                    .filter((a) => !used.includes(a.id))
                                    .map((a) => (
                                        <option key={a.id} value={a.id}>
                                            {a.name} ({a.code}) · tersedia {left(a.id) ?? '...'} {a.unit}
                                        </option>
                                    ))}
                            </Select>
                            <Button variant="secondary" onClick={addItem} disabled={!pick}>
                                Tambah
                            </Button>
                        </div>
                    </fieldset>

                    <Field label="Catatan (opsional)" htmlFor="notes" error={errors.notes}>
                        <textarea id="notes" rows={2} value={data.notes} onChange={(e) => setData('notes', e.target.value)} className={inputCls} />
                    </Field>

                    <div className="flex gap-2 border-t border-[#EEF1F5] pt-5">
                        <Button type="submit" disabled={processing || data.items.length === 0}>
                            Ajukan peminjaman
                        </Button>
                        <Button variant="secondary" href="/inventory/loans">
                            Batal
                        </Button>
                    </div>
                </Card>
            </form>
        </AppLayout>
    );
}
