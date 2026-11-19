import { Head, router } from '@inertiajs/react';
import { Fragment, useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Badge, Button, Card, Input, PageHeader, Pagination, Select } from '@/Components/ui';

const tone = (e) => (['login_failed', 'deleted', 'role_deleted'].includes(e) ? 'red' : ['created', 'role_created'].includes(e) ? 'green' : 'neutral');

function Diff({ log }) {
    const keys = [...new Set([...Object.keys(log.old_values ?? {}), ...Object.keys(log.new_values ?? {})])];
    const fmt = (v) => (v === null || v === undefined ? '-' : typeof v === 'object' ? JSON.stringify(v) : String(v));
    return (
        <div className="space-y-2 px-4 py-3 text-xs">
            <p className="text-[#5B6779]">
                {log.subject && <>Objek: {log.subject}. </>}IP: {log.ip_address ?? '-'}
            </p>
            {keys.length > 0 && (
                <table className="w-full max-w-2xl text-left">
                    <thead className="text-[#5B6779]">
                        <tr>
                            <th className="py-1 pr-4 font-semibold">Field</th>
                            <th className="py-1 pr-4 font-semibold">Sebelum</th>
                            <th className="py-1 font-semibold">Sesudah</th>
                        </tr>
                    </thead>
                    <tbody>
                        {keys.map((k) => (
                            <tr key={k} className="border-t border-[#E3E7ED]">
                                <td className="py-1 pr-4 font-semibold">{k}</td>
                                <td className="py-1 pr-4 break-all">{fmt(log.old_values?.[k])}</td>
                                <td className="py-1 break-all">{fmt(log.new_values?.[k])}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            )}
        </div>
    );
}

export default function Index({ logs, filters, events }) {
    const [f, setF] = useState(filters);
    const [openId, setOpenId] = useState(null);

    const apply = (next = f) => {
        const clean = Object.fromEntries(Object.entries(next).filter(([, v]) => v !== '' && v != null));
        router.get('/audit-logs', clean, { preserveState: true, replace: true });
    };

    return (
        <AppLayout>
            <Head title="Audit Log" />
            <PageHeader title="Audit Log" description="Rekam jejak aktivitas pengguna. Log bersifat hanya-tambah dan tidak dapat diubah atau dihapus dari aplikasi." />

            <Card>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        apply();
                    }}
                    className="flex flex-wrap items-end gap-3 border-b border-[#DDE2E9] p-4"
                >
                    <Input
                        type="search"
                        value={f.q}
                        onChange={(e) => setF({ ...f, q: e.target.value })}
                        placeholder="Cari deskripsi atau pengguna"
                        aria-label="Cari log"
                        className="min-w-56 flex-1"
                    />
                    <Select
                        value={f.event}
                        onChange={(e) => {
                            const next = { ...f, event: e.target.value };
                            setF(next);
                            apply(next);
                        }}
                        aria-label="Filter event"
                        className="w-48"
                    >
                        <option value="">Semua event</option>
                        {Object.entries(events).map(([k, v]) => (
                            <option key={k} value={k}>
                                {v}
                            </option>
                        ))}
                    </Select>
                    <Input type="date" value={f.from} onChange={(e) => setF({ ...f, from: e.target.value })} aria-label="Dari tanggal" className="w-40" />
                    <Input type="date" value={f.to} onChange={(e) => setF({ ...f, to: e.target.value })} aria-label="Sampai tanggal" className="w-40" />
                    <Button type="submit" variant="secondary">
                        Terapkan
                    </Button>
                </form>

                <div className="overflow-x-auto">
                    <table className="w-full min-w-[760px] text-left text-sm">
                        <thead className="border-b border-[#DDE2E9] text-xs text-[#5B6779]">
                            <tr>
                                <th className="px-4 py-3 font-semibold">Waktu</th>
                                <th className="px-4 py-3 font-semibold">Pengguna</th>
                                <th className="px-4 py-3 font-semibold">Event</th>
                                <th className="px-4 py-3 font-semibold">Deskripsi</th>
                                <th className="px-4 py-3" />
                            </tr>
                        </thead>
                        <tbody>
                            {logs.data.length === 0 && (
                                <tr>
                                    <td colSpan={5} className="px-4 py-10 text-center text-[#5B6779]">
                                        Tidak ada log yang cocok.
                                    </td>
                                </tr>
                            )}
                            {logs.data.map((l) => {
                                const expandable = l.old_values || l.new_values || l.ip_address;
                                return (
                                    <Fragment key={l.id}>
                                        <tr className="border-t border-[#EEF1F5] align-top">
                                            <td className="whitespace-nowrap px-4 py-3 tabular-nums text-[#5B6779]">{l.created_at}</td>
                                            <td className="px-4 py-3">{l.user_label}</td>
                                            <td className="px-4 py-3">
                                                <Badge tone={tone(l.event)}>{events[l.event] ?? l.event}</Badge>
                                            </td>
                                            <td className="px-4 py-3">{l.description}</td>
                                            <td className="px-4 py-3 text-right">
                                                {expandable && (
                                                    <button
                                                        type="button"
                                                        onClick={() => setOpenId(openId === l.id ? null : l.id)}
                                                        aria-expanded={openId === l.id}
                                                        className="text-xs font-semibold text-[#B3202A] hover:underline"
                                                    >
                                                        {openId === l.id ? 'Tutup' : 'Detail'}
                                                    </button>
                                                )}
                                            </td>
                                        </tr>
                                        {openId === l.id && (
                                            <tr className="bg-[#F7F8FA]">
                                                <td colSpan={5}>
                                                    <Diff log={l} />
                                                </td>
                                            </tr>
                                        )}
                                    </Fragment>
                                );
                            })}
                        </tbody>
                    </table>
                </div>
                <div className="flex items-center justify-between border-t border-[#DDE2E9]">
                    <p className="px-4 text-sm text-[#5B6779]">{logs.total} catatan</p>
                    <Pagination links={logs.links} />
                </div>
            </Card>
        </AppLayout>
    );
}
