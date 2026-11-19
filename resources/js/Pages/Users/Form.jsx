import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button, Card, Field, Input, PageHeader } from '@/Components/ui';

export default function Form({ account, roles }) {
    const linked = !!account.member_id;
    const { data, setData, put, processing, errors } = useForm({
        name: account.name ?? '',
        email: account.email ?? '',
        roles: account.roles ?? [],
    });

    const toggleRole = (name) =>
        setData('roles', data.roles.includes(name) ? data.roles.filter((r) => r !== name) : [...data.roles, name]);

    return (
        <AppLayout>
            <Head title="Ubah Akun" />
            <PageHeader title="Ubah akun" description={account.nomor_anggota} />

            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    put(`/users/${account.id}`);
                }}
                noValidate
            >
                <Card className="max-w-2xl space-y-5 p-6">
                    {linked ? (
                        <div className="rounded-md bg-[#F2F4F7] px-4 py-3 text-sm">
                            <p className="font-semibold">{account.name}</p>
                            <p className="mt-0.5 text-[#5B6779]">
                                Nama dan email mengikuti data anggota.{' '}
                                <Link href={`/members/${account.member_id}`} className="font-semibold text-[#B3202A] hover:underline">
                                    Lihat data anggota
                                </Link>
                            </p>
                        </div>
                    ) : (
                        <>
                            <Field label="Nama lengkap" htmlFor="name" error={errors.name}>
                                <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} />
                            </Field>
                            <Field label="Email (opsional)" htmlFor="email" error={errors.email}>
                                <Input id="email" type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} />
                            </Field>
                        </>
                    )}

                    <fieldset>
                        <legend className="mb-2 text-sm font-semibold">Peran</legend>
                        {roles.length === 0 ? (
                            <p className="text-sm text-[#5B6779]">Belum ada role yang bisa dipilih.</p>
                        ) : (
                            <div className="grid gap-2 sm:grid-cols-2">
                                {roles.map((r) => (
                                    <label key={r} className="flex items-center gap-2 rounded-md border border-[#DDE2E9] px-3 py-2 text-sm">
                                        <input
                                            type="checkbox"
                                            checked={data.roles.includes(r)}
                                            onChange={() => toggleRole(r)}
                                            className="h-4 w-4 rounded border-[#CBD2DC] text-[#B3202A] focus:ring-[#B3202A]"
                                        />
                                        {r}
                                    </label>
                                ))}
                            </div>
                        )}
                        {(errors.roles || errors['roles.0']) && (
                            <p role="alert" className="mt-1.5 text-xs font-medium text-[#B3202A]">
                                {errors.roles || errors['roles.0']}
                            </p>
                        )}
                    </fieldset>

                    <div className="flex gap-2 border-t border-[#EEF1F5] pt-5">
                        <Button type="submit" disabled={processing}>
                            Simpan perubahan
                        </Button>
                        <Button variant="secondary" href="/users">
                            Batal
                        </Button>
                    </div>
                </Card>
            </form>
        </AppLayout>
    );
}
