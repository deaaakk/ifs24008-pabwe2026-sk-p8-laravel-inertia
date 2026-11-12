import { Head, useForm } from '@inertiajs/react';
import AppLayout, { PageHeader } from '../../Layouts/AppLayout';
import Button from '../../Components/Button';
import { Avatar, Field, inputClass } from '../../Components/Ui';

export default function Edit({ profile }) {
    const form = useForm({
        name: profile.name,
        username: profile.username,
        password: '',
        password_confirmation: '',
        photo: null,
    });

    const submit = (event) => {
        event.preventDefault();
        form.transform(({ photo, ...rest }) => ({ ...rest, ...(photo ? { photo } : {}) }));
        form.put('/profile', {
            preserveScroll: true,
            onSuccess: () => form.reset('password', 'password_confirmation', 'photo'),
        });
    };

    return (
        <>
            <Head title="Profil" />
            <PageHeader title="Profil" description="Perbarui identitas, foto, dan kata sandi akunmu." />

            <form onSubmit={submit} className="grid max-w-2xl gap-5 rounded-xl border border-line bg-white p-6 sm:p-8">
                <div className="flex items-center gap-4">
                    <Avatar user={profile} size={64} />
                    <Field label="Foto profil" htmlFor="profile-photo" error={form.errors.photo}>
                        <input id="profile-photo" type="file" accept="image/jpeg,image/png,image/webp" onChange={(e) => form.setData('photo', e.target.files[0] ?? null)} className="block text-sm file:mr-3 file:rounded-md file:border-0 file:bg-primary-soft file:px-3 file:py-1.5 file:font-semibold file:text-primary" />
                    </Field>
                </div>

                <Field label="Nama" htmlFor="profile-name" error={form.errors.name}>
                    <input id="profile-name" className={inputClass} value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
                </Field>
                <Field label="Username" htmlFor="profile-username" error={form.errors.username}>
                    <input id="profile-username" className={inputClass} value={form.data.username} onChange={(e) => form.setData('username', e.target.value)} />
                </Field>
                <Field label="Password baru" htmlFor="profile-password" error={form.errors.password} hint="Kosongkan jika tidak ingin mengubah.">
                    <input id="profile-password" type="password" autoComplete="new-password" className={inputClass} value={form.data.password} onChange={(e) => form.setData('password', e.target.value)} />
                </Field>
                <Field label="Ulangi password baru" htmlFor="profile-password-confirmation">
                    <input id="profile-password-confirmation" type="password" autoComplete="new-password" className={inputClass} value={form.data.password_confirmation} onChange={(e) => form.setData('password_confirmation', e.target.value)} />
                </Field>

                <div className="flex justify-end">
                    <Button type="submit" loading={form.processing}>Simpan Profil</Button>
                </div>
            </form>
        </>
    );
}

Edit.layout = (page) => <AppLayout>{page}</AppLayout>;
