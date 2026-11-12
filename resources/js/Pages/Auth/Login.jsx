import { Head, Link, useForm } from '@inertiajs/react';
import PublicLayout from '../../Layouts/PublicLayout';
import Button from '../../Components/Button';
import { Field, inputClass } from '../../Components/Ui';
import { LoginIcon } from '../../Components/Icons';

export default function Login() {
    const form = useForm({ username: '', password: '', remember: false });

    const submit = (event) => {
        event.preventDefault();
        form.post('/login', { onFinish: () => form.reset('password') });
    };

    return (
        <>
            <Head title="Masuk" />
            <div className="mx-auto grid max-w-5xl gap-10 px-4 py-14 sm:px-6 md:grid-cols-2 md:py-20">
                <div className="flex flex-col justify-center">
                    <h1 className="font-serif text-4xl font-bold leading-tight sm:text-5xl">Selamat datang kembali.</h1>
                    <p className="mt-4 text-lg text-muted">
                        Masuk untuk melanjutkan draf, menerbitkan artikel, dan melihat tulisanmu dibaca orang lain.
                    </p>
                    <p className="mt-6 rounded-lg bg-saffron-soft px-4 py-3 text-sm text-[#6b5000]">
                        Akun demo — penulis: <strong>member / member</strong> · admin: <strong>admin / admin</strong>
                    </p>
                </div>

                <form onSubmit={submit} className="grid gap-5 rounded-2xl border border-line bg-white p-6 shadow-sm sm:p-8">
                    <Field label="Username" htmlFor="login-username" error={form.errors.username}>
                        <input
                            id="login-username"
                            name="username"
                            autoComplete="username"
                            className={inputClass}
                            value={form.data.username}
                            onChange={(e) => form.setData('username', e.target.value)}
                            autoFocus
                        />
                    </Field>

                    <Field label="Password" htmlFor="login-password" error={form.errors.password}>
                        <input
                            id="login-password"
                            name="password"
                            type="password"
                            autoComplete="current-password"
                            className={inputClass}
                            value={form.data.password}
                            onChange={(e) => form.setData('password', e.target.value)}
                        />
                    </Field>

                    <label htmlFor="login-remember" className="flex items-center gap-2 text-sm">
                        <input
                            id="login-remember"
                            type="checkbox"
                            className="h-4 w-4 accent-primary"
                            checked={form.data.remember}
                            onChange={(e) => form.setData('remember', e.target.checked)}
                        />
                        Ingat saya di perangkat ini
                    </label>

                    <button
                        id="login-submit"
                        type="submit"
                        disabled={form.processing}
                        className="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-5 py-2.5 font-semibold text-white transition hover:bg-primary-dark disabled:opacity-60"
                    >
                        {form.processing ? <span className="spinner inline-block h-4 w-4 rounded-full border-2 border-white/30 border-t-white" /> : <LoginIcon size={18} />}
                        Masuk
                    </button>

                    <p className="text-center text-sm text-muted">
                        Belum punya akun?{' '}
                        <Link href="/register" className="font-semibold text-primary hover:underline">
                            Daftar sebagai penulis
                        </Link>
                    </p>
                </form>
            </div>
        </>
    );
}

Login.layout = (page) => <PublicLayout>{page}</PublicLayout>;
