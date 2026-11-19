import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import Avatar, { statusTone } from '@/Components/Avatar';
import { Badge, Button, Card, Input, PageHeader, Pagination, Select } from '@/Components/ui';
import { useCan } from '@/lib/can';

export default function Index({ members, filters, statuses, divisions }) {
    const can = useCan();
    const [q, setQ] = useState(filters.q ?? '');

    const apply = (patch = {}) => {
        const next = { q, status: filters.status, division: filters.division, ...patch };
        Object.keys(next).forEach((k) => (next[k] === '' || next[k] == null) && delete next[k]);
        router.get('/members', next, { preserveState: true, replace: true });
    };

    return (
        <AppLayout>
            <Head title="Anggota" />
            <PageHeader
                title="Anggota"
                description="Identitas organisasi. Akun akses aplikasi dibuat dari profil anggota."
                actions={can('members.create') && <Button href="/members/create">Tambah anggota</Button>}
            />

            <Card>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        apply();
                    }}
                    className="flex flex-wrap gap-3 border-b border-[#DDE2E9] p-4"
                >
                    <Input
                        type="search"
                        value={q}
                        onChange={(e) => setQ(e.target.value)}
                        placeholder="Cari nama, nomor anggota, telepon, email"
                        aria-label="Cari anggota"
                        className="min-w-56 flex-1"
                    />
                    <Select value={filters.status} onChange={(e) => apply({ status: e.target.value })} aria-label="Filter status" className="w-40">
                        <option value="">Semua status</option>
                        {Object.entries(statuses).map(([k, v]) => (
                            <option key={k} value={k}>
                                {v}
                            </option>
                        ))}
                    </Select>
                    <Select value={filters.division} onChange={(e) => apply({ division: e.target.value })} aria-label="Filter divisi" className="w-48">
                        <option value="">Semua divisi</option>
                        {divisions.map((d) => (
                            <option key={d.id} value={d.id}>
                                {d.name}
                            </option>
                        ))}
                    </Select>
                    <Button type="submit" variant="secondary">
                        Cari
                    </Button>
                </form>

                <div className="overflow-x-auto">
                    <table className="w-full min-w-[720px] text-left text-sm">
                        <thead className="border-b border-[#DDE2E9] text-xs text-[#5B6779]">
                            <tr>
                                <th className="px-4 py-3 font-semibold">Anggota</th>
                                <th className="px-4 py-3 font-semibold">Status</th>
                                <th className="px-4 py-3 font-semibold">Jabatan aktif</th>
                                <th className="px-4 py-3 font-semibold">Akun</th>
                                <th className="px-4 py-3 text-right font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-[#EEF1F5]">
                            {members.data.length === 0 && (
                                <tr>
                                    <td colSpan={5} className="px-4 py-10 text-center text-[#5B6779]">
                                        Tidak ada anggota yang cocok.
                                    </td>
                                </tr>
                            )}
                            {members.data.map((m) => (
                                <tr key={m.id}>
                                    <td className="px-4 py-3">
                                        <Link href={`/members/${m.id}`} className="flex items-center gap-3">
                                            <Avatar name={m.name} src={m.photo_url} />
                                            <span>
                                                <span className="block font-semibold">{m.name}</span>
                                                <span className="block text-xs tabular-nums text-[#5B6779]">{m.nomor_anggota}</span>
                                            </span>
                                        </Link>
                                    </td>
                                    <td className="px-4 py-3">
                                        <Badge tone={statusTone(m.status)}>{m.status_label}</Badge>
                                    </td>
                                    <td className="px-4 py-3 text-[#3B4658]">
                                        {m.positions.length ? m.positions.map((p) => <div key={p}>{p}</div>) : <span className="text-[#8A94A4]">-</span>}
                                    </td>
                                    <td className="px-4 py-3">
                                        {m.has_account ? (
                                            <Badge tone={m.account_active ? 'green' : 'red'}>{m.account_active ? 'Aktif' : 'Nonaktif'}</Badge>
                                        ) : (
                                            <span className="text-[#8A94A4]">Belum ada</span>
                                        )}
                                    </td>
                                    <td className="px-4 py-3">
                                        <div className="flex justify-end gap-1.5">
                                            <Button variant="secondary" size="sm" href={`/members/${m.id}`}>
                                                Profil
                                            </Button>
                                            <Button variant="secondary" size="sm" href={`/members/${m.id}/card`}>
                                                Kartu
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <div className="flex items-center justify-between border-t border-[#DDE2E9]">
                    <p className="px-4 text-sm text-[#5B6779]">{members.total} anggota</p>
                    <Pagination links={members.links} />
                </div>
            </Card>
        </AppLayout>
    );
}
