import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import Avatar, { statusTone } from '@/Components/Avatar';
import { Badge, Button, Card, Field, Input, Select } from '@/Components/ui';
import { useCan } from '@/lib/can';

function Row({ label, children }) {
    return (
        <div>
            <dt className="text-xs text-[#5B6779]">{label}</dt>
            <dd className="mt-0.5 text-sm font-semibold">{children || <span className="font-normal text-[#8A94A4]">-</span>}</dd>
        </div>
    );
}

export default function Show({ member, account, assignments, options, statuses }) {
    const can = useCan();
    const today = new Date().toLocaleDateString('en-CA');
    const currentPeriod = options.periods.find((p) => p.is_current);
    const [status, setStatus] = useState(member.status);

    const af = useForm({
        period_id: currentPeriod?.id ?? '',
        position_id: '',
        division_id: '',
        started_at: today,
        ended_at: '',
        notes: '',
    });
    const pos = options.positions.find((p) => String(p.id) === String(af.data.position_id));
    const needsDivision = pos?.scope === 'division';

    const run = (message, fn) => window.confirm(message) && fn();
    const keep = { preserveScroll: true };

    const addAssignment = (e) => {
        e.preventDefault();
        af.post(`/members/${member.id}/assignments`, {
            preserveScroll: true,
            onSuccess: () => af.reset('position_id', 'division_id', 'ended_at', 'notes'),
        });
    };

    return (
        <AppLayout>
            <Head title={member.name} />

            <div className="mb-6 flex flex-wrap items-start justify-between gap-4">
                <div className="flex items-center gap-4">
                    <Avatar name={member.name} src={member.photo_url} className="h-20 w-20 rounded-lg" textClass="text-2xl" />
                    <div>
                        <h1 className="text-2xl font-extrabold tracking-tight">{member.name}</h1>
                        <p className="mt-0.5 text-sm tabular-nums text-[#5B6779]">{member.nomor_anggota}</p>
                        <div className="mt-2 flex flex-wrap gap-1.5">
                            <Badge tone={statusTone(member.status)}>{member.status_label}</Badge>
                            {member.positions.map((p) => (
                                <Badge key={p}>{p}</Badge>
                            ))}
                        </div>
                    </div>
                </div>
                <div className="flex flex-wrap gap-2">
                    <Button variant="secondary" href={`/members/${member.id}/card`}>
                        Kartu anggota
                    </Button>
                    {can('members.update') && (
                        <Button variant="secondary" href={`/members/${member.id}/edit`}>
                            Ubah data
                        </Button>
                    )}
                </div>
            </div>

            <div className="grid gap-6 lg:grid-cols-3">
                <div className="space-y-6 lg:col-span-2">
                    <Card className="p-5">
                        <h2 className="text-base font-bold">Data pribadi</h2>
                        <dl className="mt-4 grid gap-x-6 gap-y-4 sm:grid-cols-2">
                            <Row label="Nama panggilan">{member.nickname}</Row>
                            <Row label="Jenis kelamin">{member.gender === 'L' ? 'Laki-laki' : member.gender === 'P' ? 'Perempuan' : null}</Row>
                            <Row label="Tempat, tanggal lahir">{[member.birth_place, member.birth_date].filter(Boolean).join(', ')}</Row>
                            <Row label="Bergabung">{member.joined_at}</Row>
                            <Row label="Telepon">{member.phone}</Row>
                            <Row label="Email">{member.email}</Row>
                            <div className="sm:col-span-2">
                                <Row label="Alamat">{member.address}</Row>
                            </div>
                        </dl>
                    </Card>

                    <Card>
                        <h2 className="border-b border-[#DDE2E9] px-5 py-3.5 text-base font-bold">Riwayat jabatan dan divisi</h2>
                        {assignments.length === 0 ? (
                            <p className="px-5 py-8 text-sm text-[#5B6779]">Belum ada jabatan tercatat.</p>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full min-w-[560px] text-left text-sm">
                                    <thead className="border-b border-[#EEF1F5] text-xs text-[#5B6779]">
                                        <tr>
                                            <th className="px-5 py-2.5 font-semibold">Jabatan</th>
                                            <th className="px-3 py-2.5 font-semibold">Periode</th>
                                            <th className="px-3 py-2.5 font-semibold">Masa jabatan</th>
                                            <th className="px-3 py-2.5" />
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-[#EEF1F5]">
                                        {assignments.map((a) => (
                                            <tr key={a.id}>
                                                <td className="px-5 py-3">
                                                    <div className="font-semibold">{a.position}</div>
                                                    {a.division && <div className="text-xs text-[#5B6779]">{a.division}</div>}
                                                    {a.notes && <div className="text-xs text-[#8A94A4]">{a.notes}</div>}
                                                </td>
                                                <td className="px-3 py-3">{a.period}</td>
                                                <td className="px-3 py-3">
                                                    <span className="tabular-nums">
                                                        {a.started_at} - {a.ended_at ?? 'sekarang'}
                                                    </span>{' '}
                                                    {a.is_active && <Badge tone="green">Aktif</Badge>}
                                                </td>
                                                <td className="px-3 py-3">
                                                    {can('members.assign') && (
                                                        <div className="flex justify-end gap-1.5">
                                                            {a.is_active && (
                                                                <Button
                                                                    variant="secondary"
                                                                    size="sm"
                                                                    onClick={() => run(`Akhiri jabatan ${a.position} mulai hari ini?`, () => router.patch(`/assignments/${a.id}/end`, {}, keep))}
                                                                >
                                                                    Akhiri
                                                                </Button>
                                                            )}
                                                            <Button
                                                                variant="danger"
                                                                size="sm"
                                                                onClick={() => run('Hapus baris riwayat ini? Gunakan hanya untuk koreksi data.', () => router.delete(`/assignments/${a.id}`, keep))}
                                                            >
                                                                Hapus
                                                            </Button>
                                                        </div>
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}

                        {can('members.assign') && (
                            <form onSubmit={addAssignment} noValidate className="border-t border-[#DDE2E9] bg-[#F7F8FA] p-5">
                                <h3 className="text-sm font-bold">Tetapkan jabatan</h3>
                                {options.periods.length === 0 && (
                                    <p className="mt-2 text-sm text-[#7A5206]">
                                        Belum ada periode kepengurusan. Buat dulu di menu Divisi, Jabatan, Periode.
                                    </p>
                                )}
                                <div className="mt-3 grid gap-4 sm:grid-cols-2">
                                    <Field label="Periode" htmlFor="period_id" error={af.errors.period_id}>
                                        <Select id="period_id" value={af.data.period_id} onChange={(e) => af.setData('period_id', e.target.value)}>
                                            <option value="">Pilih periode</option>
                                            {options.periods.map((p) => (
                                                <option key={p.id} value={p.id}>
                                                    {p.name}
                                                    {p.is_current ? ' (berjalan)' : ''}
                                                </option>
                                            ))}
                                        </Select>
                                    </Field>
                                    <Field label="Jabatan" htmlFor="position_id" error={af.errors.position_id}>
                                        <Select id="position_id" value={af.data.position_id} onChange={(e) => af.setData('position_id', e.target.value)}>
                                            <option value="">Pilih jabatan</option>
                                            {options.positions.map((p) => (
                                                <option key={p.id} value={p.id}>
                                                    {p.name}
                                                </option>
                                            ))}
                                        </Select>
                                    </Field>
                                    <Field
                                        label="Divisi"
                                        htmlFor="division_id"
                                        error={af.errors.division_id}
                                        hint={pos && !needsDivision ? 'Jabatan tingkat organisasi tidak memakai divisi.' : undefined}
                                    >
                                        <Select
                                            id="division_id"
                                            value={af.data.division_id}
                                            disabled={!needsDivision}
                                            onChange={(e) => af.setData('division_id', e.target.value)}
                                        >
                                            <option value="">{needsDivision ? 'Pilih divisi' : '-'}</option>
                                            {options.divisions.map((d) => (
                                                <option key={d.id} value={d.id}>
                                                    {d.name}
                                                </option>
                                            ))}
                                        </Select>
                                    </Field>
                                    <Field label="Catatan (opsional)" htmlFor="notes" error={af.errors.notes}>
                                        <Input id="notes" value={af.data.notes} onChange={(e) => af.setData('notes', e.target.value)} />
                                    </Field>
                                    <Field label="Mulai" htmlFor="started_at" error={af.errors.started_at}>
                                        <Input id="started_at" type="date" value={af.data.started_at} onChange={(e) => af.setData('started_at', e.target.value)} />
                                    </Field>
                                    <Field label="Berakhir (kosongkan jika masih menjabat)" htmlFor="ended_at" error={af.errors.ended_at}>
                                        <Input id="ended_at" type="date" value={af.data.ended_at} onChange={(e) => af.setData('ended_at', e.target.value)} />
                                    </Field>
                                </div>
                                <Button type="submit" disabled={af.processing} className="mt-4">
                                    Tambahkan
                                </Button>
                            </form>
                        )}
                    </Card>
                </div>

                <div className="space-y-6">
                    <Card className="p-5">
                        <h2 className="text-base font-bold">Akun aplikasi</h2>
                        {account ? (
                            <div className="mt-3 space-y-2 text-sm">
                                <Badge tone={account.is_active ? 'green' : 'red'}>{account.is_active ? 'Aktif' : 'Nonaktif'}</Badge>
                                <p className="text-[#5B6779]">Login terakhir: {account.last_login_at ?? 'belum pernah'}</p>
                                {can('users.view') && (
                                    <Link href={`/users?q=${encodeURIComponent(member.nomor_anggota)}`} className="font-semibold text-[#B3202A] hover:underline">
                                        Kelola akun
                                    </Link>
                                )}
                            </div>
                        ) : (
                            <div className="mt-3 text-sm">
                                <p className="text-[#5B6779]">Belum memiliki akun. Anggota masuk menggunakan nomor anggota di atas.</p>
                                {can('members.activate-account') && (
                                    <Button
                                        className="mt-3"
                                        disabled={member.status !== 'active'}
                                        onClick={() => run(`Buat akun untuk ${member.name}? Kata sandi sementara akan ditampilkan sekali.`, () => router.post(`/members/${member.id}/account`, {}, keep))}
                                    >
                                        Aktifkan akun
                                    </Button>
                                )}
                                {member.status !== 'active' && <p className="mt-2 text-xs text-[#8A94A4]">Hanya anggota berstatus Aktif yang dapat diberi akun.</p>}
                            </div>
                        )}
                    </Card>

                    {can('members.update') && (
                        <Card className="p-5">
                            <h2 className="text-base font-bold">Status keanggotaan</h2>
                            <div className="mt-3 flex gap-2">
                                <Select value={status} onChange={(e) => setStatus(e.target.value)} aria-label="Status keanggotaan">
                                    {Object.entries(statuses).map(([k, v]) => (
                                        <option key={k} value={k}>
                                            {v}
                                        </option>
                                    ))}
                                </Select>
                                <Button
                                    variant="secondary"
                                    disabled={status === member.status}
                                    onClick={() =>
                                        run(
                                            status === 'active'
                                                ? 'Aktifkan kembali keanggotaan? Akun tidak ikut aktif otomatis.'
                                                : 'Status Nonaktif/Alumni akan menonaktifkan akun dan mengakhiri jabatan yang masih berjalan. Lanjutkan?',
                                            () => router.patch(`/members/${member.id}/status`, { status }, keep),
                                        )
                                    }
                                >
                                    Simpan
                                </Button>
                            </div>
                        </Card>
                    )}

                    {can('members.update') && (
                        <Card className="p-5">
                            <h2 className="text-base font-bold">Kartu dan QR</h2>
                            <p className="mt-1 text-sm text-[#5B6779]">Jika kartu hilang atau bocor, buat QR baru. Kartu lama langsung tidak valid.</p>
                            <Button
                                variant="secondary"
                                className="mt-3"
                                onClick={() => run('Buat QR baru? Kartu yang sudah dicetak tidak akan bisa diverifikasi lagi.', () => router.post(`/members/${member.id}/regenerate-qr`, {}, keep))}
                            >
                                Buat QR baru
                            </Button>
                        </Card>
                    )}

                    {can('members.delete') && (
                        <Button
                            variant="danger"
                            onClick={() => run(`Hapus anggota ${member.name}? Akun ikut dinonaktifkan dan jabatan aktif diakhiri.`, () => router.delete(`/members/${member.id}`))}
                        >
                            Hapus anggota
                        </Button>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
