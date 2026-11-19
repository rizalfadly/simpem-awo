import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import MemberCard from '@/Components/MemberCard';
import { Button, PageHeader } from '@/Components/ui';
import { useCan } from '@/lib/can';

export default function Card({ member, qr_url, verify_url, is_own }) {
    const can = useCan();

    return (
        <AppLayout>
            <Head title={`Kartu ${member.name}`} />
            <div className="print:hidden">
                <PageHeader
                    title={is_own ? 'Kartu Saya' : 'Kartu anggota'}
                    description="Tunjukkan QR Code untuk memverifikasi keanggotaan. Pemindai akan diarahkan ke halaman verifikasi resmi."
                    actions={
                        <>
                            <Button onClick={() => window.print()}>Cetak</Button>
                            {can('members.view') && (
                                <Button variant="secondary" href={`/members/${member.id}`}>
                                    Profil anggota
                                </Button>
                            )}
                        </>
                    }
                />
            </div>

            <MemberCard member={member} qrUrl={qr_url} />

            <p className="mt-4 max-w-md break-all text-xs text-[#5B6779] print:hidden">Tautan verifikasi: {verify_url}</p>
        </AppLayout>
    );
}
