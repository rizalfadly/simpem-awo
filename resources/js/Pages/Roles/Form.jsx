import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button, Card, Field, Input, PageHeader } from '@/Components/ui';

export default function Form({ role, groups }) {
    const editing = !!role;
    const { data, setData, post, put, processing, errors } = useForm({
        name: role?.name ?? '',
        permissions: role?.permissions ?? [],
    });

    const has = (p) => data.permissions.includes(p);
    const toggle = (p) => setData('permissions', has(p) ? data.permissions.filter((x) => x !== p) : [...data.permissions, p]);
    const groupAll = (g) => g.items.every((i) => has(i.name));
    const toggleGroup = (g) => {
        const names = g.items.map((i) => i.name);
        setData(
            'permissions',
            groupAll(g) ? data.permissions.filter((p) => !names.includes(p)) : [...new Set([...data.permissions, ...names])],
        );
    };

    const submit = (e) => {
        e.preventDefault();
        editing ? put(`/roles/${role.id}`) : post('/roles');
    };

    return (
        <AppLayout>
            <Head title={editing ? 'Ubah Role' : 'Tambah Role'} />
            <PageHeader title={editing ? `Ubah role ${role.name}` : 'Tambah role'} description="Centang izin yang diberikan kepada role ini." />

            <form onSubmit={submit} noValidate className="max-w-3xl space-y-6">
                <Card className="p-6">
                    <Field label="Nama role" htmlFor="name" error={errors.name}>
                        <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} />
                    </Field>
                </Card>

                {groups.map((g) => (
                    <Card key={g.label}>
                        <div className="flex items-center justify-between border-b border-[#DDE2E9] px-5 py-3">
                            <h2 className="text-base font-bold">{g.label}</h2>
                            <button type="button" onClick={() => toggleGroup(g)} className="text-xs font-semibold text-[#B3202A] hover:underline">
                                {groupAll(g) ? 'Kosongkan' : 'Pilih semua'}
                            </button>
                        </div>
                        <div className="divide-y divide-[#EEF1F5]">
                            {g.items.map((i) => (
                                <label key={i.name} className="flex cursor-pointer items-start gap-3 px-5 py-3 text-sm hover:bg-[#F7F8FA]">
                                    <input
                                        type="checkbox"
                                        checked={has(i.name)}
                                        onChange={() => toggle(i.name)}
                                        className="mt-0.5 h-4 w-4 rounded border-[#CBD2DC] text-[#B3202A] focus:ring-[#B3202A]"
                                    />
                                    <span>
                                        <span className="block font-medium">{i.label}</span>
                                        <span className="block text-xs tabular-nums text-[#8A94A4]">{i.name}</span>
                                    </span>
                                </label>
                            ))}
                        </div>
                    </Card>
                ))}

                <div className="flex gap-2">
                    <Button type="submit" disabled={processing}>
                        {editing ? 'Simpan perubahan' : 'Buat role'}
                    </Button>
                    <Button variant="secondary" href="/roles">
                        Batal
                    </Button>
                </div>
            </form>
        </AppLayout>
    );
}
