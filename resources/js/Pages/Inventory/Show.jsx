import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Badge, Button, Card } from '@/Components/ui';
import { rupiah } from '@/lib/format';
import { conditionLabels } from './Index';

const fmt = (v) => (v === null || v === undefined ? '-' : typeof v === 'object' ? JSON.stringify(v) : String(v));
const keep = { preserveScroll: true };

function Row({ label, children }) {
    return (
        <div>
            <dt className="text-xs text-[#5B6779]">{label}</dt>
            <dd className="mt-0.5 text-sm font-semibold">{children || <span className="font-normal text-[#8A94A4]">-</span>}</dd>
        </div>
    );
}

export default function Show({ asset: a, usage, history, can }) {
    const d = a.details;

    return (
        <AppLayout>
            <Head title={a.name} />

            <div className="mb-6 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <Link href="/inventory" className="text-xs font-semibold text-[#5B6779] hover:underline">
                        Barang
                    </Link>
                    <h1 className="mt-1 text-2xl font-extrabold tracking-tight">{a.name}</h1>
                    <p className="mt-1 text-sm tabular-nums text-[#5B6779]">
                        {a.code}
                        {a.category ? ` · ${a.category}` : ''}
                    </p>
                </div>
                <div className="flex flex-wrap gap-2">
                    {can.request && <Button href={`/inventory/loans/create?asset=${a.id}`}>Ajukan peminjaman</Button>}
                    {can.manage && (
                        <>
                            <Button variant="secondary" href={`/inventory/${a.id}/edit`}>
                                Ubah
                            </Button>
                            {a.status === 'active' ? (
                                <Button
                                    variant="secondary"
                                    onClick={() => {
                                        const note = window.prompt('Alasan dipensiunkan (wajib, mis. rusak berat / dijual / dihibahkan):');
                                        if (note) router.post(`/inventory/${a.id}/retire`, { note }, keep);
                                    }}
                                >
                                    Pensiunkan
                                </Button>
                            ) : (
                                <Button variant="secondary" onClick={() => window.confirm('Aktifkan kembali barang ini?') && router.post(`/inventory/${a.id}/reactivate`, {}, keep)}>
                                    Aktifkan kembali
                                </Button>
                            )}
                            <Button variant="danger" onClick={() => window.confirm('Hapus barang ini? Hanya bisa jika belum punya riwayat peminjaman.') && router.delete(`/inventory/${a.id}`)}>
                                Hapus
                            </Button>
                        </>
                    )}
                </div>
            </div>

            <div className="grid gap-6 lg:grid-cols-3">
                <Card className="p-5 lg:col-span-2">
                    <dl className="grid gap-x-6 gap-y-4 sm:grid-cols-2">
                        <Row label="Status">
                            <Badge tone={a.status === 'active' ? 'green' : 'neutral'}>{a.status_label}</Badge>
                        </Row>
                        <Row label="Tersedia hari ini">{a.loanable ? `${a.available} ${a.unit}` : 'Tidak dapat dipinjam'}</Row>
                        <Row label="Lokasi penyimpanan">{a.location}</Row>
                        <Row label="Satuan">{a.unit}</Row>
                        {d && (
                            <>
                                <Row label="Nomor seri">{d.serial_number}</Row>
                                <Row label="Diperoleh">
                                    {[d.acquired_at, d.acquisition_source].filter(Boolean).join(' · ')}
                                </Row>
                                <Row label="Nilai perolehan">{d.acquisition_cost !== null ? rupiah(d.acquisition_cost) : null}</Row>
                                <Row label="Sedang dipinjam">{`${d.lent} dari ${d.total} ${a.unit}`}</Row>
                            </>
                        )}
                        {a.description && (
                            <div className="sm:col-span-2">
                                <Row label="Keterangan">
                                    <span className="whitespace-pre-line font-normal">{a.description}</span>
                                </Row>
                            </div>
                        )}
                        {a.retired_note && (
                            <div className="sm:col-span-2">
                                <Row label="Alasan dipensiunkan">{a.retired_note}</Row>
                            </div>
                        )}
                    </dl>

                    {d && (
                        <div className="mt-6 border-t border-[#EEF1F5] pt-5">
                            <h2 className="text-sm font-bold">Kondisi ({d.total} {a.unit})</h2>
                            <div className="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4">
                                {Object.entries(conditionLabels).map(([k, label]) => (
                                    <div key={k} className="rounded-md bg-[#F2F4F7] px-3 py-2">
                                        <div className="text-xs text-[#5B6779]">{label}</div>
                                        <div className="text-xl font-extrabold tabular-nums">{d.conditions[k]}</div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}
                </Card>

                <Card className="self-start overflow-hidden">
                    {a.photo ? <img src={a.photo} alt={a.name} className="w-full object-cover" /> : <div className="flex aspect-[4/3] items-center justify-center bg-[#E9EDF2] text-sm text-[#8A94A4]">Belum ada foto</div>}
                </Card>
            </div>

            {can.details && (
                <>
                    <Card className="mt-6 overflow-x-auto">
                        <h2 className="border-b border-[#DDE2E9] px-4 py-3 text-base font-bold">Riwayat penggunaan</h2>
                        {usage.length === 0 ? (
                            <p className="px-4 py-8 text-center text-sm text-[#5B6779]">Belum pernah dipinjam.</p>
                        ) : (
                            <table className="w-full min-w-[720px] text-left text-sm">
                                <thead className="border-b border-[#EEF1F5] text-xs text-[#5B6779]">
                                    <tr>
                                        <th className="px-4 py-2.5 font-semibold">Peminjaman</th>
                                        <th className="px-3 py-2.5 font-semibold">Peminjam</th>
                                        <th className="px-3 py-2.5 font-semibold">Periode</th>
                                        <th className="px-3 py-2.5 font-semibold">Jumlah</th>
                                        <th className="px-3 py-2.5 font-semibold">Status / kondisi kembali</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-[#EEF1F5]">
                                    {usage.map((u) => (
                                        <tr key={u.id}>
                                            <td className="px-4 py-3">
                                                <Link href={`/inventory/loans/${u.loan_id}`} className="font-semibold tabular-nums hover:underline">
                                                    {u.code}
                                                </Link>
                                                <div className="text-xs text-[#5B6779]">{u.activity ?? u.purpose}</div>
                                            </td>
                                            <td className="px-3 py-3">{u.borrower}</td>
                                            <td className="px-3 py-3 tabular-nums">{u.period}</td>
                                            <td className="px-3 py-3 tabular-nums">{u.quantity}</td>
                                            <td className="px-3 py-3">
                                                {u.status}
                                                {u.late_days > 0 && <span className="ml-1 text-xs font-semibold text-[#B3202A]">(telat {u.late_days} hari)</span>}
                                                {u.returned && (
                                                    <div className="text-xs text-[#5B6779]">
                                                        Baik {u.returned.good}, ringan {u.returned.minor}, berat {u.returned.broken}, hilang {u.returned.lost}
                                                    </div>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        )}
                    </Card>

                    {history.length > 0 && (
                        <Card className="mt-6">
                            <h2 className="border-b border-[#DDE2E9] px-4 py-3 text-base font-bold">Riwayat perubahan data</h2>
                            <ol className="divide-y divide-[#EEF1F5]">
                                {history.map((h) => (
                                    <li key={h.id} className="px-4 py-3 text-sm">
                                        <p className="font-semibold">
                                            {{ created: 'Ditambahkan', updated: 'Diubah', deleted: 'Dihapus' }[h.event] ?? h.event}
                                            <span className="font-normal text-[#5B6779]">
                                                {' '}
                                                oleh {h.user}, {h.at}
                                            </span>
                                        </p>
                                        {h.new && h.event === 'updated' && (
                                            <ul className="mt-1 space-y-0.5 text-xs text-[#3B4658]">
                                                {Object.keys(h.new).map((k) => (
                                                    <li key={k}>
                                                        <span className="font-semibold">{k}</span>: {fmt(h.old?.[k])} <span aria-hidden="true">→</span> {fmt(h.new[k])}
                                                    </li>
                                                ))}
                                            </ul>
                                        )}
                                    </li>
                                ))}
                            </ol>
                        </Card>
                    )}
                </>
            )}
        </AppLayout>
    );
}
