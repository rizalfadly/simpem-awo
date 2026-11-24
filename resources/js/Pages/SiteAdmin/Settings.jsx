import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button, Card, Field, Input, PageHeader, inputCls } from '@/Components/ui';

export default function Settings({ settings, logo, hero }) {
    const { data, setData, post, processing, errors } = useForm({ _method: 'put', ...settings, logo: null, hero: null, remove_logo: false, remove_hero: false });
    const set = (k) => (e) => setData(k, e.target.value);
    const area = (k, label, rows = 3, hint) => (
        <Field label={label} htmlFor={k} error={errors[k]} hint={hint}>
            <textarea id={k} rows={rows} value={data[k]} onChange={set(k)} className={inputCls} />
        </Field>
    );
    const line = (k, label, props = {}) => (
        <Field label={label} htmlFor={k} error={errors[k]} hint={props.hint}>
            <Input id={k} value={data[k]} onChange={set(k)} {...props} hint={undefined} />
        </Field>
    );
    const image = (kind, label, current) => (
        <div>
            <Field label={label} htmlFor={kind} error={errors[kind]} hint="JPG, PNG, atau WEBP, maks. 8 MB.">
                <input id={kind} type="file" accept="image/jpeg,image/png,image/webp" onChange={(e) => setData((d) => ({ ...d, [kind]: e.target.files[0] ?? null, [`remove_${kind}`]: false }))} className={inputCls} />
            </Field>
            {current && !data[kind] && (
                <div className="mt-2 flex items-center gap-3">
                    <img src={current} alt="" className="h-14 rounded-md" />
                    <label className="flex items-center gap-2 text-sm">
                        <input type="checkbox" checked={data[`remove_${kind}`]} onChange={(e) => setData(`remove_${kind}`, e.target.checked)} className="h-4 w-4 rounded border-[#CBD2DC] text-[#B3202A] focus:ring-[#B3202A]" />
                        Hapus
                    </label>
                </div>
            )}
        </div>
    );

    return (
        <AppLayout>
            <Head title="Pengaturan Website" />
            <PageHeader title="Pengaturan website" description="Profil dan kontak yang tampil di website publik. Semua isian di halaman ini bersifat publik." />

            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    post('/site/settings', { forceFormData: true, preserveScroll: true });
                }}
                noValidate
                className="max-w-3xl space-y-6"
            >
                <Card className="space-y-5 p-6">
                    <h2 className="text-base font-bold">Identitas</h2>
                    {line('name', 'Nama organisasi')}
                    {line('tagline', 'Slogan singkat', { hint: 'Tampil di beranda dan hasil pencarian.' })}
                    <div className="grid gap-5 sm:grid-cols-2">
                        {image('logo', 'Logo (opsional)', logo)}
                        {image('hero', 'Gambar latar beranda (opsional)', hero)}
                    </div>
                </Card>

                <Card className="space-y-5 p-6">
                    <h2 className="text-base font-bold">Profil organisasi</h2>
                    {area('about', 'Tentang', 4)}
                    {area('vision', 'Visi', 3)}
                    {area('mission', 'Misi', 4)}
                    {area('history', 'Sejarah', 4)}
                </Card>

                <Card className="space-y-5 p-6">
                    <h2 className="text-base font-bold">Kontak</h2>
                    {line('address', 'Alamat')}
                    <div className="grid gap-5 sm:grid-cols-2">
                        {line('email', 'Email', { type: 'email' })}
                        {line('phone', 'Telepon', { inputMode: 'tel' })}
                        {line('whatsapp', 'WhatsApp', { inputMode: 'tel', hint: 'Dipakai untuk tautan wa.me.' })}
                        {line('instagram', 'Instagram', { placeholder: '@username' })}
                        {line('facebook', 'Facebook (URL)', { placeholder: 'https://' })}
                        {line('youtube', 'YouTube (URL)', { placeholder: 'https://' })}
                    </div>
                    {line('maps_url', 'Tautan peta (URL)', { placeholder: 'https://maps.app.goo.gl/...' })}
                    {line('hours', 'Jam layanan', { placeholder: 'Senin - Jumat, 09.00 - 16.00' })}
                </Card>

                <Button type="submit" disabled={processing}>
                    Simpan pengaturan
                </Button>
            </form>
        </AppLayout>
    );
}
