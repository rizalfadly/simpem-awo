import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Badge, Button, Card, Input, PageHeader, Pagination, Select } from '@/Components/ui';
import { rupiah } from '@/lib/format';

export default function Ledger({ items, filters, categories, can_record }) {
    const [q, setQ] = useState(filters.q ?? '');
    const [month, setMonth] = useState(filters.month ?? '');

    const apply = (patch = {}) => {
        const next = { q, month, type: filters.type, category: filters.category, activity: filters.activity, ...patch };
        Object.keys(next).forEach((k) => (next[k] === '' || next[k] == null) && delete next[k]);
        router.get('/finance/transactions', next, { preserveState: true, replace: true });
    };

    return (
        <AppLayout>
            <Head title="Buku Kas" />
            <PageHeader
                title="Buku Kas"
                description="Seluruh transaksi, termasuk yang masih draf. Hanya untuk pengurus keuangan."
                actions={
                    can_record && (
                        <>
                            <Button href="/finance/transactions/create?type=income" variant="secondary">
                                Kas masuk
                            </Button>
                            <Button href="/finance/transactions/create?type=expense">Kas keluar</Button>
                        </>
                    )
                }
            />

            <Card>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        apply();
                    }}
                    className="flex flex-wrap gap-3 border-b border-[#DDE2E9] p-4"
                >
                    <Input type="search" value={q} onChange={(e) => setQ(e.target.value)} placeholder="Cari uraian, nomor bukti, pihak" aria-label="Cari transaksi" className="min-w-52 flex-1" />
                    <Select value={filters.type} onChange={(e) => apply({ type: e.target.value })} aria-label="Filter jenis" className="w-36">
                        <option value="">Semua jenis</option>
                        <option value="income">Kas masuk</option>
                        <option value="expense">Kas keluar</option>
                    </Select>
                    <Select value={filters.category} onChange={(e) => apply({ category: e.target.value })} aria-label="Filter kategori" className="w-48">
                        <option value="">Semua kategori</option>
                        {categories.map((c) => (
                            <option key={c.id} value={c.id}>
                                {c.name} ({c.type === 'income' ? 'masuk' : 'keluar'})
                            </option>
                        ))}
                    </Select>
                    <Input type="month" value={month} onChange={(e) => setMonth(e.target.value)} aria-label="Bulan" className="w-44" />
                    <Button type="submit" variant="secondary">
                        Terapkan
                    </Button>
                </form>

                <div className="overflow-x-auto">
                    <table className="w-full min-w-[820px] text-left text-sm">
                        <thead className="border-b border-[#DDE2E9] text-xs text-[#5B6779]">
                            <tr>
                                <th className="px-4 py-3 font-semibold">Tanggal</th>
                                <th className="px-3 py-3 font-semibold">Transaksi</th>
                                <th className="px-3 py-3 font-semibold">Laporan</th>
                                <th className="px-3 py-3 font-semibold">Bukti</th>
                                <th className="px-4 py-3 text-right font-semibold">Nominal</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-[#EEF1F5]">
                            {items.data.length === 0 && (
                                <tr>
                                    <td colSpan={5} className="px-4 py-10 text-center text-[#5B6779]">
                                        Tidak ada transaksi yang cocok.
                                    </td>
                                </tr>
                            )}
                            {items.data.map((t) => (
                                <tr key={t.id}>
                                    <td className="whitespace-nowrap px-4 py-3 tabular-nums text-[#5B6779]">{t.date}</td>
                                    <td className="px-3 py-3">
                                        <Link href={`/finance/transactions/${t.id}`} className="font-semibold hover:underline">
                                            {t.description}
                                        </Link>
                                        <div className="text-xs text-[#5B6779]">
                                            <span className="tabular-nums">{t.reference}</span> · {t.category}
                                            {t.activity ? ` · ${t.activity}` : ''}
                                        </div>
                                    </td>
                                    <td className="px-3 py-3">
                                        <Link href={`/finance/reports/${t.report_id}`} className="hover:underline">
                                            {t.report_label}
                                        </Link>
                                        <div className="text-xs text-[#5B6779]">{t.report_status}</div>
                                    </td>
                                    <td className="px-3 py-3">{t.has_proof ? <Badge tone="green">Ada</Badge> : <span className="text-[#8A94A4]">-</span>}</td>
                                    <td className={`px-4 py-3 text-right font-semibold tabular-nums ${t.type === 'expense' ? 'text-[#8F1B24]' : 'text-[#1E6B3C]'}`}>
                                        {t.type === 'expense' ? '-' : '+'}
                                        {rupiah(t.amount)}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
                <div className="flex items-center justify-between border-t border-[#DDE2E9]">
                    <p className="px-4 text-sm text-[#5B6779]">{items.total} transaksi</p>
                    <Pagination links={items.links} />
                </div>
            </Card>
        </AppLayout>
    );
}
