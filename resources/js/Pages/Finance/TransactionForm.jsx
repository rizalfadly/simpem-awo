import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button, Card, Field, Input, PageHeader, Select, inputCls } from '@/Components/ui';
import { rupiah } from '@/lib/format';

export default function TransactionForm({ transaction, options, defaults }) {
    const editing = !!transaction;
    const today = new Date().toLocaleDateString('en-CA');
    const { data, setData, post, processing, errors } = useForm({
        ...(editing ? { _method: 'put' } : { type: defaults.type }),
        category_id: transaction?.category_id ?? '',
        transaction_date: transaction?.transaction_date ?? today,
        amount: transaction?.amount ?? '',
        description: transaction?.description ?? '',
        counterparty: transaction?.counterparty ?? '',
        activity_id: transaction?.activity_id ?? defaults.activity_id ?? '',
        proof: null,
        proof_note: transaction?.proof_note ?? '',
        remove_proof: false,
    });

    const type = editing ? transaction.type : data.type;
    const isExpense = type === 'expense';
    const categories = options.categories.filter((c) => c.type === type);
    const set = (k) => (e) => setData(k, e.target.value);

    return (
        <AppLayout>
            <Head title={editing ? 'Ubah Transaksi' : 'Catat Transaksi'} />
            <PageHeader
                title={editing ? `Ubah ${transaction.reference}` : isExpense ? 'Catat kas keluar' : 'Catat kas masuk'}
                description="Transaksi menjadi bagian laporan bulan sesuai tanggalnya. Setelah laporan diajukan, transaksi terkunci."
            />

            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    post(editing ? `/finance/transactions/${transaction.id}` : '/finance/transactions', { forceFormData: true });
                }}
                noValidate
            >
                <Card className="max-w-3xl space-y-5 p-6">
                    {!editing && (
                        <div role="radiogroup" aria-label="Jenis transaksi" className="inline-flex overflow-hidden rounded-md border border-[#CBD2DC]">
                            {[
                                ['income', 'Kas masuk'],
                                ['expense', 'Kas keluar'],
                            ].map(([k, label]) => (
                                <button
                                    key={k}
                                    type="button"
                                    role="radio"
                                    aria-checked={data.type === k}
                                    onClick={() => setData((d) => ({ ...d, type: k, category_id: '' }))}
                                    className={`px-4 py-2 text-sm font-semibold ${data.type === k ? 'bg-[#16202E] text-white' : 'bg-white hover:bg-[#F2F4F7]'}`}
                                >
                                    {label}
                                </button>
                            ))}
                        </div>
                    )}

                    <div className="grid gap-5 sm:grid-cols-2">
                        <Field label="Tanggal transaksi" htmlFor="date" error={errors.transaction_date}>
                            <Input id="date" type="date" max={today} value={data.transaction_date} onChange={set('transaction_date')} />
                        </Field>
                        <Field label="Kategori" htmlFor="category" error={errors.category_id}>
                            <Select id="category" value={data.category_id} onChange={set('category_id')}>
                                <option value="">Pilih kategori</option>
                                {categories.map((c) => (
                                    <option key={c.id} value={c.id}>
                                        {c.name}
                                    </option>
                                ))}
                            </Select>
                        </Field>
                        <Field label="Nominal (Rp)" htmlFor="amount" error={errors.amount} hint={data.amount ? rupiah(Number(data.amount)) : 'Angka bulat, tanpa titik.'}>
                            <Input id="amount" type="number" min="1" inputMode="numeric" value={data.amount} onChange={set('amount')} />
                        </Field>
                        <Field label={isExpense ? 'Penerima / toko (opsional)' : 'Sumber dana (opsional)'} htmlFor="counterparty" error={errors.counterparty}>
                            <Input id="counterparty" value={data.counterparty} onChange={set('counterparty')} />
                        </Field>
                        <div className="sm:col-span-2">
                            <Field label="Uraian" htmlFor="description" error={errors.description}>
                                <Input id="description" value={data.description} onChange={set('description')} />
                            </Field>
                        </div>
                        <div className="sm:col-span-2">
                            <Field label="Kegiatan terkait (opsional)" htmlFor="activity" error={errors.activity_id}>
                                <Select id="activity" value={data.activity_id} onChange={set('activity_id')}>
                                    <option value="">Tidak terkait kegiatan</option>
                                    {options.activities.map((a) => (
                                        <option key={a.id} value={a.id}>
                                            {a.code} {a.title}
                                        </option>
                                    ))}
                                </Select>
                            </Field>
                        </div>
                    </div>

                    <fieldset className="space-y-4 rounded-md border border-[#DDE2E9] p-4">
                        <legend className="px-1 text-sm font-semibold">Bukti transaksi</legend>
                        <Field
                            label={editing && transaction.has_proof ? `Ganti bukti (saat ini: ${transaction.proof_name})` : 'Unggah bukti (nota/kuitansi/bukti transfer)'}
                            htmlFor="proof"
                            error={errors.proof}
                            hint="PDF, JPG, atau PNG, maks. 5 MB. Lokasi (GPS) di dalam foto otomatis dihapus."
                        >
                            <input id="proof" type="file" accept=".pdf,.jpg,.jpeg,.png" onChange={(e) => setData((d) => ({ ...d, proof: e.target.files[0] ?? null, remove_proof: false }))} className={inputCls} />
                        </Field>
                        {editing && transaction.has_proof && !data.proof && (
                            <label className="flex items-center gap-2 text-sm">
                                <input type="checkbox" checked={data.remove_proof} onChange={(e) => setData('remove_proof', e.target.checked)} className="h-4 w-4 rounded border-[#CBD2DC] text-[#B3202A] focus:ring-[#B3202A]" />
                                Hapus bukti saat ini
                            </label>
                        )}
                        {isExpense && (
                            <Field label="Jika tanpa bukti, tulis alasannya" htmlFor="proof_note" error={errors.proof_note} hint="Kas keluar wajib punya bukti atau alasan sebelum laporan bisa diajukan. Contoh: parkir, tidak ada nota.">
                                <Input id="proof_note" value={data.proof_note} onChange={set('proof_note')} />
                            </Field>
                        )}
                    </fieldset>

                    <div className="flex gap-2 border-t border-[#EEF1F5] pt-5">
                        <Button type="submit" disabled={processing}>
                            {editing ? 'Simpan perubahan' : 'Catat transaksi'}
                        </Button>
                        <Button variant="secondary" href="/finance">
                            Batal
                        </Button>
                    </div>
                </Card>
            </form>
        </AppLayout>
    );
}
