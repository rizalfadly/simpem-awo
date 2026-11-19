import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Badge, Button, Card, Input, PageHeader, Pagination, Select } from '@/Components/ui';
import { useCan } from '@/lib/can';

export default function Index({ users, roles, filters }) {
    const can = useCan();
    const [q, setQ] = useState(filters.q ?? '');

    const apply = (patch = {}) => {
        const next = { q, role: filters.role, status: filters.status, ...patch };
        Object.keys(next).forEach((k) => (next[k] === '' || next[k] == null) && delete next[k]);
        router.get('/users', next, { preserveState: true, replace: true });
    };

    const run = (message, fn) => {
        if (window.confirm(message)) fn();
    };
    const opts = { preserveScroll: true };

    return (
        <AppLayout>
            <Head title="Akun Pengguna" />
            <PageHeader
                title="Akun Pengguna"
                description="Kelola peran, status, dan kata sandi akun. Akun baru dibuat dari halaman Anggota (Aktifkan akun)."
                actions={can('members.view') && <Button href="/members" variant="secondary">Buka daftar anggota</Button>}
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
                        placeholder="Cari nama, nomor anggota, email"
                        aria-label="Cari akun"
                        className="min-w-56 flex-1"
                    />
                    <Select value={filters.role} onChange={(e) => apply({ role: e.target.value })} aria-label="Filter peran" className="w-44">
                        <option value="">Semua peran</option>
                        {roles.map((r) => (
                            <option key={r} value={r}>
                                {r}
                            </option>
                        ))}
                    </Select>
                    <Select value={filters.status} onChange={(e) => apply({ status: e.target.value })} aria-label="Filter status" className="w-40">
                        <option value="">Semua status</option>
                        <option value="active">Aktif</option>
                        <option value="inactive">Nonaktif</option>
                    </Select>
                    <Button type="submit" variant="secondary">
                        Cari
                    </Button>
                </form>

                <div className="overflow-x-auto">
                    <table className="w-full min-w-[760px] text-left text-sm">
                        <thead className="border-b border-[#DDE2E9] text-xs text-[#5B6779]">
                            <tr>
                                <th className="px-4 py-3 font-semibold">Nomor Anggota</th>
                                <th className="px-4 py-3 font-semibold">Nama</th>
                                <th className="px-4 py-3 font-semibold">Peran</th>
                                <th className="px-4 py-3 font-semibold">Status</th>
                                <th className="px-4 py-3 font-semibold">Login terakhir</th>
                                <th className="px-4 py-3 text-right font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-[#EEF1F5]">
                            {users.data.length === 0 && (
                                <tr>
                                    <td colSpan={6} className="px-4 py-10 text-center text-[#5B6779]">
                                        Tidak ada akun yang cocok.
                                    </td>
                                </tr>
                            )}
                            {users.data.map((u) => (
                                <tr key={u.id}>
                                    <td className="px-4 py-3 font-semibold tabular-nums">{u.nomor_anggota}</td>
                                    <td className="px-4 py-3">
                                        <div className="font-medium">{u.name}</div>
                                        {u.email && <div className="text-xs text-[#5B6779]">{u.email}</div>}
                                    </td>
                                    <td className="px-4 py-3">
                                        <div className="flex flex-wrap gap-1">
                                            {u.roles.length ? u.roles.map((r) => <Badge key={r}>{r}</Badge>) : <span className="text-[#8A94A4]">-</span>}
                                        </div>
                                    </td>
                                    <td className="px-4 py-3">
                                        <Badge tone={u.is_active ? 'green' : 'red'}>{u.is_active ? 'Aktif' : 'Nonaktif'}</Badge>
                                    </td>
                                    <td className="px-4 py-3 text-[#5B6779]">{u.last_login_at ?? 'Belum pernah'}</td>
                                    <td className="px-4 py-3">
                                        {u.is_locked ? (
                                            <span className="block text-right text-xs text-[#8A94A4]">Dilindungi</span>
                                        ) : (
                                            <div className="flex flex-wrap justify-end gap-1.5">
                                                {can('users.update') && (
                                                    <Button variant="secondary" size="sm" href={`/users/${u.id}/edit`}>
                                                        Ubah
                                                    </Button>
                                                )}
                                                {!u.is_self && can('users.update') && (
                                                    <Button
                                                        variant="secondary"
                                                        size="sm"
                                                        onClick={() =>
                                                            run(
                                                                u.is_active ? `Nonaktifkan akun ${u.nomor_anggota}?` : `Aktifkan akun ${u.nomor_anggota}?`,
                                                                () => router.patch(`/users/${u.id}/toggle-active`, {}, opts),
                                                            )
                                                        }
                                                    >
                                                        {u.is_active ? 'Nonaktifkan' : 'Aktifkan'}
                                                    </Button>
                                                )}
                                                {!u.is_self && can('users.reset-password') && (
                                                    <Button
                                                        variant="secondary"
                                                        size="sm"
                                                        onClick={() =>
                                                            run(`Reset kata sandi ${u.nomor_anggota}? Kata sandi lama tidak berlaku lagi.`, () =>
                                                                router.post(`/users/${u.id}/reset-password`, {}, opts),
                                                            )
                                                        }
                                                    >
                                                        Reset sandi
                                                    </Button>
                                                )}
                                                {!u.is_self && can('users.delete') && (
                                                    <Button
                                                        variant="danger"
                                                        size="sm"
                                                        onClick={() =>
                                                            run(`Hapus akun ${u.nomor_anggota} (${u.name})?`, () => router.delete(`/users/${u.id}`, opts))
                                                        }
                                                    >
                                                        Hapus
                                                    </Button>
                                                )}
                                            </div>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <div className="flex items-center justify-between border-t border-[#DDE2E9]">
                    <p className="px-4 text-sm text-[#5B6779]">{users.total} akun</p>
                    <Pagination links={users.links} />
                </div>
            </Card>
        </AppLayout>
    );
}
