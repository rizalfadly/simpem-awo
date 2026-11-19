import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';

export const cx = (...c) => c.filter(Boolean).join(' ');

const variants = {
    primary: 'bg-[#B3202A] text-white hover:bg-[#961A23] focus-visible:outline-[#B3202A]',
    secondary: 'bg-white text-[#16202E] border border-[#CBD2DC] hover:bg-[#F2F4F7] focus-visible:outline-[#16202E]',
    danger: 'bg-white text-[#B3202A] border border-[#E7B9BC] hover:bg-[#FBEFF0] focus-visible:outline-[#B3202A]',
    ghost: 'text-[#16202E] hover:bg-[#E9EDF2] focus-visible:outline-[#16202E]',
};

export function Button({ variant = 'primary', href, className, children, size = 'md', ...props }) {
    const cls = cx(
        'inline-flex items-center justify-center gap-2 rounded-md font-semibold transition-colors',
        'focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2',
        'disabled:pointer-events-none disabled:opacity-50',
        size === 'sm' ? 'px-2.5 py-1.5 text-xs' : 'px-3.5 py-2 text-sm',
        variants[variant],
        className,
    );
    if (href) {
        return (
            <Link href={href} className={cls} {...props}>
                {children}
            </Link>
        );
    }
    return (
        <button type="button" className={cls} {...props}>
            {children}
        </button>
    );
}

export const inputCls =
    'w-full rounded-md border border-[#CBD2DC] bg-white px-3 py-2 text-sm text-[#16202E] placeholder:text-[#8A94A4] ' +
    'focus:border-[#16202E] focus:outline-none focus:ring-2 focus:ring-[#16202E]/15 disabled:bg-[#EEF1F5] disabled:text-[#5B6779]';

export function Input({ className, ...props }) {
    return <input className={cx(inputCls, className)} {...props} />;
}

export function Select({ className, children, ...props }) {
    return (
        <select className={cx(inputCls, 'pr-8', className)} {...props}>
            {children}
        </select>
    );
}

export function Field({ label, error, hint, htmlFor, children }) {
    return (
        <div>
            <label htmlFor={htmlFor} className="mb-1.5 block text-sm font-semibold text-[#16202E]">
                {label}
            </label>
            {children}
            {hint && !error && <p className="mt-1.5 text-xs text-[#5B6779]">{hint}</p>}
            {error && (
                <p role="alert" className="mt-1.5 text-xs font-medium text-[#B3202A]">
                    {error}
                </p>
            )}
        </div>
    );
}

export function Card({ className, children }) {
    return <section className={cx('rounded-lg border border-[#DDE2E9] bg-white', className)}>{children}</section>;
}

const tones = {
    neutral: 'bg-[#E9EDF2] text-[#3B4658]',
    green: 'bg-[#E1F3E8] text-[#1E6B3C]',
    red: 'bg-[#FBE5E7] text-[#8F1B24]',
    amber: 'bg-[#FDF0D5] text-[#7A5206]',
};

export function Badge({ tone = 'neutral', children }) {
    return (
        <span className={cx('inline-flex items-center rounded px-2 py-0.5 text-xs font-semibold', tones[tone])}>
            {children}
        </span>
    );
}

export function PageHeader({ title, description, actions }) {
    return (
        <div className="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 className="text-2xl font-extrabold tracking-tight text-[#16202E]">{title}</h1>
                {description && <p className="mt-1 max-w-prose text-sm text-[#5B6779]">{description}</p>}
            </div>
            {actions && <div className="flex gap-2">{actions}</div>}
        </div>
    );
}

export function Pagination({ links = [] }) {
    if (links.length <= 3) return null;
    const label = (l) => l.replace('&laquo; Previous', 'Sebelumnya').replace('Next &raquo;', 'Berikutnya');
    return (
        <nav className="flex flex-wrap gap-1 p-3" aria-label="Halaman">
            {links.map((l, i) =>
                l.url ? (
                    <Link
                        key={i}
                        href={l.url}
                        preserveScroll
                        aria-current={l.active ? 'page' : undefined}
                        className={cx(
                            'rounded px-2.5 py-1 text-sm font-medium',
                            l.active ? 'bg-[#16202E] text-white' : 'text-[#16202E] hover:bg-[#E9EDF2]',
                        )}
                    >
                        {label(l.label)}
                    </Link>
                ) : (
                    <span key={i} className="rounded px-2.5 py-1 text-sm text-[#A3ACBA]">
                        {label(l.label)}
                    </span>
                ),
            )}
        </nav>
    );
}

export function Flash() {
    const { flash } = usePage().props;
    const [copied, setCopied] = useState(false);
    if (!flash) return null;
    const gp = flash.generated_password;

    const copy = async () => {
        try {
            await navigator.clipboard.writeText(gp.password);
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        } catch {
            /* clipboard tidak tersedia; pengguna bisa menyalin manual */
        }
    };

    return (
        <div className="mb-6 space-y-3 empty:hidden">
            {flash.success && (
                <div role="status" className="rounded-md border border-[#B9DFC7] bg-[#EDF8F1] px-4 py-3 text-sm font-medium text-[#1E6B3C]">
                    {flash.success}
                </div>
            )}
            {flash.error && (
                <div role="alert" className="rounded-md border border-[#E7B9BC] bg-[#FBEFF0] px-4 py-3 text-sm font-medium text-[#8F1B24]">
                    {flash.error}
                </div>
            )}
            {gp && (
                <div className="rounded-md border border-[#16202E] bg-white px-4 py-3">
                    <p className="text-sm font-semibold">
                        Kata sandi sementara untuk {gp.name} ({gp.nomor})
                    </p>
                    <div className="mt-2 flex flex-wrap items-center gap-3">
                        <code className="rounded bg-[#EEF1F5] px-3 py-1.5 text-base font-bold tracking-wider">{gp.password}</code>
                        <Button variant="secondary" size="sm" onClick={copy}>
                            {copied ? 'Tersalin' : 'Salin'}
                        </Button>
                    </div>
                    <p className="mt-2 text-xs text-[#5B6779]">
                        Hanya ditampilkan sekali. Sampaikan ke anggota; sistem akan meminta mereka menggantinya saat login pertama.
                    </p>
                </div>
            )}
        </div>
    );
}
