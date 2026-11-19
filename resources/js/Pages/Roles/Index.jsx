import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Badge, Button, Card, PageHeader } from '@/Components/ui';
import { useCan } from '@/lib/can';

export default function Index({ roles }) {
    const can = useCan();
    const manage = can('roles.manage');

    return (
        <AppLayout>
            <Head title="Role & Permission" />
            <PageHeader
                title="Role & Permission"
                description="Role menentukan fitur apa yang boleh diakses sebuah akun. Satu akun dapat memiliki beberapa role."
                actions={manage && <Button href="/roles/create">Tambah role</Button>}
            />

            <Card className="overflow-x-auto">
                <table className="w-full min-w-[560px] text-left text-sm">
                    <thead className="border-b border-[#DDE2E9] text-xs text-[#5B6779]">
                        <tr>
                            <th className="px-4 py-3 font-semibold">Role</th>
                            <th className="px-4 py-3 font-semibold">Izin</th>
                            <th className="px-4 py-3 font-semibold">Akun</th>
                            <th className="px-4 py-3 text-right font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-[#EEF1F5]">
                        {roles.map((r) => (
                            <tr key={r.id}>
                                <td className="px-4 py-3 font-semibold">{r.name}</td>
                                <td className="px-4 py-3">{r.is_super ? <Badge tone="amber">Akses penuh</Badge> : `${r.permissions_count} izin`}</td>
                                <td className="px-4 py-3 tabular-nums">{r.users_count}</td>
                                <td className="px-4 py-3">
                                    <div className="flex justify-end gap-1.5">
                                        {r.is_super ? (
                                            <span className="text-xs text-[#8A94A4]">Dilindungi</span>
                                        ) : (
                                            manage && (
                                                <>
                                                    <Button variant="secondary" size="sm" href={`/roles/${r.id}/edit`}>
                                                        Ubah
                                                    </Button>
                                                    <Button
                                                        variant="danger"
                                                        size="sm"
                                                        onClick={() => window.confirm(`Hapus role ${r.name}?`) && router.delete(`/roles/${r.id}`, { preserveScroll: true })}
                                                    >
                                                        Hapus
                                                    </Button>
                                                </>
                                            )
                                        )}
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </Card>
        </AppLayout>
    );
}
