import { cx } from '@/Components/ui';

/** Foto anggota; jika belum ada, tampil inisial. className menentukan ukuran & bentuk. */
export default function Avatar({ name = '', src, className = 'h-10 w-10 rounded-full', textClass = 'text-sm' }) {
    const initials = name.split(/\s+/).filter(Boolean).slice(0, 2).map((w) => w[0]).join('').toUpperCase();

    return src ? (
        <img src={src} alt="" className={cx('shrink-0 object-cover', className)} />
    ) : (
        <span aria-hidden="true" className={cx('inline-flex shrink-0 items-center justify-center bg-[#E9EDF2] font-bold text-[#3B4658]', className, textClass)}>
            {initials}
        </span>
    );
}

export const statusTone = (status) => (status === 'active' ? 'green' : status === 'inactive' ? 'amber' : 'neutral');
