import { Link } from '@inertiajs/react';
import { Spinner } from './Icons';

const VARIANTS = {
    primary: 'bg-primary text-white hover:bg-primary-dark',
    ink: 'bg-ink text-white hover:bg-black',
    saffron: 'bg-saffron text-ink hover:brightness-95',
    outline: 'border border-line bg-white text-ink hover:border-ink/40 hover:bg-paper',
    danger: 'bg-danger text-white hover:brightness-90',
    ghost: 'text-primary hover:bg-primary-soft',
};

const SIZES = {
    sm: 'px-3 py-1.5 text-sm',
    md: 'px-4 py-2 text-sm',
    lg: 'px-5 py-2.5 text-base',
};

export function buttonClass(variant = 'primary', size = 'md', extra = '') {
    return `inline-flex items-center justify-center gap-2 rounded-lg font-semibold transition disabled:opacity-60 ${VARIANTS[variant]} ${SIZES[size]} ${extra}`;
}

/** Tombol dengan spinner saat `loading`. Jika `href` diberikan, dirender sebagai Link Inertia. */
export default function Button({
    variant = 'primary',
    size = 'md',
    loading = false,
    icon: Icon,
    href,
    className = '',
    children,
    ...props
}) {
    const content = (
        <>
            {loading ? <Spinner /> : Icon && <Icon size={16} />}
            {children}
        </>
    );

    if (href) {
        return (
            <Link href={href} className={buttonClass(variant, size, className)} {...props}>
                {content}
            </Link>
        );
    }

    return (
        <button
            type="button"
            disabled={loading || props.disabled}
            className={buttonClass(variant, size, className)}
            {...props}
        >
            {content}
        </button>
    );
}
