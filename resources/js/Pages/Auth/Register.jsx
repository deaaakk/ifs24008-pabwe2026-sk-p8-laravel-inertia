import { Head, Link, useForm } from '@inertiajs/react';
import PublicLayout from '../../Layouts/PublicLayout';
import { Field, inputClass } from '../../Components/Ui';
import { UserPlusIcon } from '../../Components/Icons';

export default function Register() {
    const form = useForm({ name: '', username: '', password: '', password_confirmation: '' });

    const submit = (event) => {
        event.preventDefault();
        form.post('/register', { onFinish: () => form.reset('password', 'password_confirmation') });
    };

    return (
        <>
            <Head title="Daftar" />
            <div className="mx-auto grid max-w-5xl gap-10 px-4 py-14 sm:px-6 md:grid-cols-2 md:py-20">
                <div className="flex flex-col justify-center">
                    <h1 className="font-serif text-4xl font-bold leading-tight sm:text-5xl">Mulai menulis hari ini.</h1>
                    <p className="mt-4 text-lg text-muted">
                        Gratis. Tulis dengan Markdown, simpan sebagai draf, ekspor ke Excel, atau minta AI menyiapkan kerangka tulisan.
                    </p>
                </div>

                <form onSubmit={submit} className="grid gap-5 rounded-2xl border border-line bg-white p-6 shadow-sm sm:p-8">
                    <Field label="Nama lengkap" htmlFor="register-name" error={form.errors.name}>
                        <input id="register-name" autoComplete="name" className={inputClass} value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} autoFocus />
                    </Field>

                    <Field label="Username" htmlFor="register-username" error={form.errors.username} hint="Huruf, angka, strip, dan garis bawah.">
                        <input id="register-username" autoComplete="username" className={inputClass} value={form.data.username} onChange={(e) => form.setData('username', e.target.value)} />
                    </Field>

                    <Field label="Password" htmlFor="register-password" error={form.errors.password} hint="Minimal 8 karakter.">
                        <input id="register-password" type="password" autoComplete="new-password" className={inputClass} value={form.data.password} onChange={(e) => form.setData('password', e.target.value)} />
                    </Field>

                    <Field label="Ulangi password" htmlFor="register-password-confirmation">
                        <input id="register-password-confirmation" type="password" autoComplete="new-password" className={inputClass} value={form.data.password_confirmation} onChange={(e) => form.setData('password_confirmation', e.target.value)} />
                    </Field>

                    <button
                        id="register-submit"
                        type="submit"
                        disabled={form.processing}
                        className="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-5 py-2.5 font-semibold text-white transition hover:bg-primary-dark disabled:opacity-60"
                    >
                        {form.processing ? <span className="spinner inline-block h-4 w-4 rounded-full border-2 border-white/30 border-t-white" /> : <UserPlusIcon size={18} />}
                        Buat akun
                    </button>

                    <p className="text-center text-sm text-muted">
                        Sudah punya akun?{' '}
                        <Link href="/login" className="font-semibold text-primary hover:underline">
                            Masuk
                        </Link>
                    </p>
                </form>
            </div>
        </>
    );
}

Register.layout = (page) => <PublicLayout>{page}</PublicLayout>;
