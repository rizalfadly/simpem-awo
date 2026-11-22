import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import PartnerProducts from '@/Components/partner/PartnerProducts';
import PartnerPromos from '@/Components/partner/PartnerPromos';
import { Badge, Button, Card, cx } from '@/Components/ui';

function Row({ label, children }) {
    return (
        <div>
            <dt className="text-xs text-[#5B6779]">{label}</dt>
            <dd className="mt-0.5 text-sm font-semibold">{children || <span className="font-normal text-[#8A94A4]">-</span>}</dd>
        </div>
    );
}

export default function Show({ partner: p, products, promotions, can }) {
    const [tab, setTab] = useState('profile');
    const tabs = [
        ['profile', 'Profil'],
        ['products', `Produk (${products.length})`],
        ['promos', `Promosi (${promotions.length})`],
    ];

    return (
        <AppLayout>
            <Head title={p.name} />

            <div className="mb-6 flex flex-wrap items-start justify-between gap-4">
                <div className="flex items-center gap-4">
                    {p.logo ? <img src={p.logo} alt="" className="h-20 w-20 rounded-lg object-cover" /> : <span className="flex h-20 w-20 items-center justify-center rounded-lg bg-[#E9EDF2] text-3xl font-extrabold text-[#3B4658]">{p.name.slice(0, 1).toUpperCase()}</span>}
                    <div>
                        <Link href="/partners" className="text-xs font-semibold text-[#5B6779] hover:underline">
                            Mitra & UMKM
                        </Link>
                        <h1 className="mt-0.5 text-2xl font-extrabold tracking-tight">{p.name}</h1>
                        <div className="mt-1.5 flex flex-wrap gap-1.5">
                            <Badge>{p.type_label}</Badge>
                            {p.sector && <Badge tone="neutral">{p.sector}</Badge>}
                            {p.status !== 'active' && <Badge tone="red">{p.status_label}</Badge>}
                            {p.is_public && <Badge tone="green">Tampil di website publik</Badge>}
                            {p.member && <Badge tone="amber">UMKM anggota</Badge>}
                        </div>
                    </div>
                </div>
                {can.manage && (
                    <div className="flex flex-wrap gap-2">
                        <Button variant="secondary" href={`/partners/${p.id}/edit`}>
                            Ubah
                        </Button>
                        <Button variant="secondary" href={`/partners/partnerships/create?partner=${p.id}`}>
                            Catat kerja sama
                        </Button>
                        <Button
                            variant="secondary"
                            onClick={() =>
                                window.confirm(p.status === 'active' ? 'Nonaktifkan mitra? Mitra juga ditarik dari website publik.' : 'Aktifkan kembali mitra?') &&
                                router.post(`/partners/${p.id}/status`, { status: p.status === 'active' ? 'inactive' : 'active' }, { preserveScroll: true })
                            }
                        >
                            {p.status === 'active' ? 'Nonaktifkan' : 'Aktifkan'}
                        </Button>
                        <Button variant="danger" onClick={() => window.confirm('Hapus mitra ini?') && router.delete(`/partners/${p.id}`)}>
                            Hapus
                        </Button>
                    </div>
                )}
            </div>

            <div role="tablist" aria-label="Bagian mitra" className="mb-6 flex flex-wrap gap-1 border-b border-[#DDE2E9]">
                {tabs.map(([key, label]) => (
                    <button
                        key={key}
                        role="tab"
                        type="button"
                        aria-selected={tab === key}
                        onClick={() => setTab(key)}
                        className={cx('-mb-px border-b-2 px-4 py-2 text-sm font-semibold', tab === key ? 'border-[#B3202A] text-[#16202E]' : 'border-transparent text-[#5B6779] hover:text-[#16202E]')}
                    >
                        {label}
                    </button>
                ))}
            </div>

            {tab === 'profile' && (
                <div className="grid gap-6 lg:grid-cols-3">
                    <Card className="p-5 lg:col-span-2">
                        {p.description && <p className="mb-5 whitespace-pre-line text-sm">{p.description}</p>}
                        <dl className="grid gap-x-6 gap-y-4 sm:grid-cols-2">
                            <Row label="Pemilik / pimpinan">{p.owner_name}</Row>
                            <Row label="Anggota AWO pemilik">
                                {p.member && (
                                    <Link href={`/members/${p.member.id}`} className="text-[#B3202A] hover:underline">
                                        {p.member.name}
                                    </Link>
                                )}
                            </Row>
                            <Row label="Alamat">{[p.address, p.city].filter(Boolean).join(', ')}</Row>
                            <Row label="Mitra sejak">{p.joined_at}</Row>
                            <Row label="Website">
                                {p.website && (
                                    <a href={p.website} target="_blank" rel="noopener noreferrer" className="break-all text-[#B3202A] hover:underline">
                                        {p.website}
                                    </a>
                                )}
                            </Row>
                            <Row label="Instagram">{p.instagram}</Row>
                            <Row label="Kontak publik">{p.public_contact}</Row>
                        </dl>
                    </Card>

                    {p.internal && (
                        <Card className="self-start border-[#E7B9BC] p-5">
                            <h2 className="text-base font-bold">Data internal</h2>
                            <p className="mt-0.5 text-xs text-[#5B6779]">Hanya terlihat oleh pengurus kemitraan.</p>
                            <dl className="mt-4 space-y-3">
                                <Row label="Nama kontak">{p.internal.contact_person}</Row>
                                <Row label="Telepon">{p.internal.contact_phone}</Row>
                                <Row label="Email">{p.internal.contact_email}</Row>
                                <Row label="Catatan">{p.internal.notes}</Row>
                            </dl>
                        </Card>
                    )}
                </div>
            )}
            {tab === 'products' && <PartnerProducts partner={p} products={products} can={can} />}
            {tab === 'promos' && <PartnerPromos partner={p} promotions={promotions} can={can} />}
        </AppLayout>
    );
}
