import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Badge, Card, PageHeader, Pagination, Select } from '@/Components/ui';

const tone = { open: 'amber', clarified: 'amber', reviewed: 'amber', upheld: 'green', rejected: 'neutral', withdrawn: 'neutral' };

export default function Disputes({ items, status, statuses, internal }) {
    return (
        <AppLayout>
            <Head title="Sanggahan Keuangan" />
            <PageHeader
                title="Sanggahan keuangan"
                description={internal ? 'Semua sanggahan atas laporan keuangan.' : 'Sanggahan yang Anda ajukan. Ajukan sanggahan baru dari halaman laporan yang sudah dipublikasikan.'}
            />

            <Card>
                <div className="border-b border-[#DDE2E9] p-4">
                    <Select value={status} onChange={(e) => router.get('/finance/disputes', e.target.value ? { status: e.target.value } : {}, { preserveState: true, replace: true })} aria-label="Filter status" className="w-72">
                        <option value="">Semua status</option>
                        {Object.entries(statuses).map(([k, v]) => (
                            <option key={k} value={k}>
                                {v}
                            </option>
                        ))}
                    </Select>
                </div>
                {items.data.length === 0 ? (
                    <p className="px-4 py-12 text-center text-sm text-[#5B6779]">Belum ada sanggahan.</p>
                ) : (
                    <ul className="divide-y divide-[#EEF1F5]">
                        {items.data.map((d) => (
                            <li key={d.id}>
                                <Link href={`/finance/disputes/${d.id}`} className="flex flex-wrap items-start justify-between gap-2 px-5 py-4 hover:bg-[#F7F8FA]">
                                    <span className="min-w-0">
                                        <span className="block text-sm font-semibold">{d.reason}</span>
                                        <span className="block text-xs text-[#5B6779]">
                                            Laporan {d.report}
                                            {d.transaction ? ` · ${d.transaction}` : ''}
                                            {internal && d.filer ? ` · ${d.filer}` : ''} · {d.created_at}
                                        </span>
                                    </span>
                                    <Badge tone={tone[d.status]}>{d.status_label}</Badge>
                                </Link>
                            </li>
                        ))}
                    </ul>
                )}
                <div className="border-t border-[#DDE2E9]">
                    <Pagination links={items.links} />
                </div>
            </Card>
        </AppLayout>
    );
}
