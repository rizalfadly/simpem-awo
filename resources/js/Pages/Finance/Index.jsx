import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Badge, Button, Card, PageHeader, Pagination } from '@/Components/ui';
import { rupiah } from '@/lib/format';

export const reportTone = { draft: 'neutral', submitted: 'amber', reviewed: 'amber', revision: 'red', approved: 'green', published: 'green' };

export default function Index({ reports, summary, can }) {
    return (
        <AppLayout>
            <Head title="Laporan Keuangan" />
            <PageHeader
                title="Laporan Keuangan"
                description="Laporan bulanan kas organisasi. Laporan yang sudah dipublikasikan dapat dilihat semua anggota."
                actions={
                    can.record && (
                        <>
                            <Button href="/finance/transactions/create?type=income" variant="secondary">
                                Catat kas masuk
                            </Button>
                            <Button href="/finance/transactions/create?type=expense">Catat kas keluar</Button>
                        </>
                    )
                }
            />

            {summary ? (
                <Link href={`/finance/reports/${summary.id}`} className="mb-8 block rounded-lg border border-[#DDE2E9] bg-white px-6 py-5 hover:border-[#16202E]">
                    <p className="text-sm text-[#5B6779]">Saldo kas per akhir {summary.label}</p>
                    <p className="mt-1 text-4xl font-extrabold tabular-nums tracking-tight">{rupiah(summary.closing)}</p>
                    <p className="mt-1 text-xs text-[#8A94A4]">Dari laporan terbit terakhir</p>
                </Link>
            ) : (
                <p className="mb-8 rounded-lg border border-dashed border-[#CBD2DC] bg-white px-6 py-8 text-center text-sm text-[#5B6779]">Belum ada laporan keuangan yang dipublikasikan.</p>
            )}

            <div className="mb-4 flex flex-wrap gap-2">
                {can.internal && (
                    <Button href="/finance/transactions" variant="secondary" size="sm">
                        Buku kas
                    </Button>
                )}
                {can.record && (
                    <Button href="/finance/categories" variant="secondary" size="sm">
                        Kategori transaksi
                    </Button>
                )}
                <Button href="/finance/disputes" variant="secondary" size="sm">
                    Sanggahan
                </Button>
            </div>

            <Card className="overflow-x-auto">
                <table className="w-full min-w-[720px] text-left text-sm">
                    <thead className="border-b border-[#DDE2E9] text-xs text-[#5B6779]">
                        <tr>
                            <th className="px-4 py-3 font-semibold">Periode</th>
                            <th className="px-3 py-3 font-semibold">Status</th>
                            <th className="px-3 py-3 text-right font-semibold">Saldo awal</th>
                            <th className="px-3 py-3 text-right font-semibold">Kas masuk</th>
                            <th className="px-3 py-3 text-right font-semibold">Kas keluar</th>
                            <th className="px-4 py-3 text-right font-semibold">Saldo akhir</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-[#EEF1F5]">
                        {reports.data.length === 0 && (
                            <tr>
                                <td colSpan={6} className="px-4 py-10 text-center text-[#5B6779]">
                                    Belum ada laporan.
                                </td>
                            </tr>
                        )}
                        {reports.data.map((r) => (
                            <tr key={r.id} className="hover:bg-[#F7F8FA]">
                                <td className="px-4 py-3">
                                    <Link href={`/finance/reports/${r.id}`} className="font-bold hover:underline">
                                        {r.label}
                                    </Link>
                                </td>
                                <td className="px-3 py-3">
                                    <Badge tone={reportTone[r.status]}>{r.status_label}</Badge>
                                </td>
                                <td className="px-3 py-3 text-right tabular-nums text-[#5B6779]">{rupiah(r.totals.opening)}</td>
                                <td className="px-3 py-3 text-right tabular-nums">{rupiah(r.totals.income)}</td>
                                <td className="px-3 py-3 text-right tabular-nums">{rupiah(r.totals.expense)}</td>
                                <td className="px-4 py-3 text-right font-bold tabular-nums">{rupiah(r.totals.closing)}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
                <div className="border-t border-[#DDE2E9]">
                    <Pagination links={reports.links} />
                </div>
            </Card>
        </AppLayout>
    );
}
