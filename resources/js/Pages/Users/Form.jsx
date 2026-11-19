import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button, Card, Field, Input, PageHeader } from '@/Components/ui';

export default function Form({ account, roles }) {
    const editing = !!account;
    const { data, setData, post, put, processing, errors } = useForm({
        nomor_anggota: account?.nomor_anggota ?? '',
        name: account?.name ?? '',
        email: account?.email ?? '',
        password: '',
        roles: account?.roles ?? [],
    });

    const toggleRole = (name) =>
        setData('roles', data.roles.includes(name) ? data.roles.filter((r) => r !== name) : [...data.roles, name]);

    const submit = (e) => {
        e.preventDefault();
        editing ? put(`/users/${account.id}`) : post('/users');
    };

    return (
        <AppLayout>
            <Head title={editing ? 'Ubah Akun' : 'Tambah Akun'} />
            <PageHeader
                title={editing ? 'Ubah akun' : 'Tambah akun'}
                description={editing ? account.nomor_anggota : 'Akun baru wajib mengganti kata sandi saat login pertama.'}
            />

            <form onSubmit={submit} noValidate>
                <Card className="max-w-2xl space-y-5 p-6">
                    <Field
                        label="Nomor Anggota"
                        htmlFor="nomor_anggota"
                        error={errors.nomor_anggota}
                        hint={editing ? 'Nomor anggota tidak dapat diubah.' : 'Contoh: AWO-0002. Dipakai untuk login.'}
                    >
                        <Input
                            id="nomor_anggota"
                            value={data.nomor_anggota}
                            onChange={(e) => setData('nomor_anggota', e.target.value)}
                            disabled={editing}
                            className="font-semibold uppercase"
                        />
                    </Field>

                    <Field label="Nama lengkap" htmlFor="name" error={errors.name}>
                        <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} />
                    </Field>

                    <Field label="Email (opsional)" htmlFor="email" error={errors.email}>
                        <Input id="email" type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} />
                    </Field>

                    {!editing && (
                        <Field
                            label="Kata sandi sementara (opsional)"
                            htmlFor="password"
                            error={errors.password}
                            hint="Kosongkan agar sistem membuatkan kata sandi acak."
                        >
                            <Input id="password" type="text" autoComplete="off" value={data.password} onChange={(e) => setData('password', e.target.value)} />
                        </Field>
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
                            {editing ? 'Simpan perubahan' : 'Buat akun'}
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
