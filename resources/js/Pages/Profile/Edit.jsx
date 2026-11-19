import { Head, usePage, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button, Card, Field, Input, PageHeader } from '@/Components/ui';

export default function Edit({ account }) {
    const { auth } = usePage().props;
    const forced = auth.user.must_change_password;
    const { data, setData, put, processing, errors, reset } = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const submit = (e) => {
        e.preventDefault();
        put('/password', { onError: () => reset('password', 'password_confirmation') , onSuccess: () => reset() });
    };

    return (
        <AppLayout>
            <Head title="Profil" />
            <PageHeader title="Profil" description={forced ? 'Anda menggunakan kata sandi sementara. Buat kata sandi baru untuk melanjutkan.' : undefined} />

            <div className="grid max-w-4xl gap-6 md:grid-cols-[1fr_1.3fr]">
                <Card className="self-start p-5">
                    <h2 className="text-base font-bold">Data akun</h2>
                    <dl className="mt-3 space-y-2.5 text-sm">
                        <div>
                            <dt className="text-xs text-[#5B6779]">Nomor Anggota</dt>
                            <dd className="font-semibold tabular-nums">{account.nomor_anggota}</dd>
                        </div>
                        <div>
                            <dt className="text-xs text-[#5B6779]">Nama</dt>
                            <dd className="font-semibold">{account.name}</dd>
                        </div>
                        <div>
                            <dt className="text-xs text-[#5B6779]">Email</dt>
                            <dd className="font-semibold">{account.email ?? '-'}</dd>
                        </div>
                        <div>
                            <dt className="text-xs text-[#5B6779]">Login terakhir</dt>
                            <dd className="font-semibold">{account.last_login_at ?? '-'}</dd>
                        </div>
                    </dl>
                    <p className="mt-4 text-xs text-[#5B6779]">Perubahan nama atau data diri dilakukan oleh pengurus.</p>
                </Card>

                <form onSubmit={submit} noValidate>
                    <Card className="space-y-5 p-6">
                        <h2 className="text-base font-bold">Ganti kata sandi</h2>
                        <Field label="Kata sandi saat ini" htmlFor="current_password" error={errors.current_password}>
                            <Input id="current_password" type="password" autoComplete="current-password" value={data.current_password} onChange={(e) => setData('current_password', e.target.value)} />
                        </Field>
                        <Field label="Kata sandi baru" htmlFor="password" error={errors.password} hint="Minimal 8 karakter, kombinasi huruf dan angka.">
                            <Input id="password" type="password" autoComplete="new-password" value={data.password} onChange={(e) => setData('password', e.target.value)} />
                        </Field>
                        <Field label="Ulangi kata sandi baru" htmlFor="password_confirmation">
                            <Input id="password_confirmation" type="password" autoComplete="new-password" value={data.password_confirmation} onChange={(e) => setData('password_confirmation', e.target.value)} />
                        </Field>
                        <Button type="submit" disabled={processing}>
                            Simpan kata sandi
                        </Button>
                    </Card>
                </form>
            </div>
        </AppLayout>
    );
}
