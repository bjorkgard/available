import { Form, Head } from '@inertiajs/react';
import { Building2, Church, User as UserIcon } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register';

type SuperadminContact = {
    name: string;
    email: string;
};

type Props = {
    passwordRules: string;
};

export default function Register({ passwordRules }: Props) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('Registrera')} />
            <Form
                {...store.form()}
                resetOnSuccess={['password', 'password_confirmation']}
                disableWhileProcessing
                className="flex flex-col gap-8"
            >
                {({ processing, errors }) => {
                    let superadmins: SuperadminContact[] = [];

                    try {
                        if (errors.existing_hall_superadmins) {
                            superadmins = JSON.parse(
                                errors.existing_hall_superadmins,
                            );
                        }
                    } catch {
                        // Ignore parsing errors
                    }

                    return (
                        <>
                            {/* Section 1: User account */}
                            <fieldset className="grid gap-4">
                                <legend className="flex items-center gap-2 text-sm font-medium text-foreground">
                                    <span className="flex size-6 items-center justify-center rounded-full bg-primary text-xs font-semibold text-primary-foreground">
                                        1
                                    </span>
                                    <UserIcon className="size-4 text-muted-foreground" />
                                    {t('Ditt konto')}
                                </legend>

                                <div className="mt-2 grid gap-4 sm:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label htmlFor="name">
                                            {t('Namn')}
                                        </Label>
                                        <Input
                                            id="name"
                                            type="text"
                                            required
                                            autoFocus
                                            tabIndex={1}
                                            autoComplete="name"
                                            name="name"
                                            placeholder={t('Fullständigt namn')}
                                        />
                                        <InputError message={errors.name} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="email">
                                            {t('E-postadress')}
                                        </Label>
                                        <Input
                                            id="email"
                                            type="email"
                                            required
                                            tabIndex={2}
                                            autoComplete="email"
                                            name="email"
                                            placeholder="email@example.com"
                                        />
                                        <InputError message={errors.email} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="password">
                                            {t('Lösenord')}
                                        </Label>
                                        <PasswordInput
                                            id="password"
                                            required
                                            tabIndex={3}
                                            autoComplete="new-password"
                                            name="password"
                                            placeholder={t('Lösenord')}
                                            passwordrules={passwordRules}
                                        />
                                        <InputError message={errors.password} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="password_confirmation">
                                            {t('Bekräfta lösenord')}
                                        </Label>
                                        <PasswordInput
                                            id="password_confirmation"
                                            required
                                            tabIndex={4}
                                            autoComplete="new-password"
                                            name="password_confirmation"
                                            placeholder={t('Bekräfta lösenord')}
                                            passwordrules={passwordRules}
                                        />
                                        <InputError
                                            message={
                                                errors.password_confirmation
                                            }
                                        />
                                    </div>
                                </div>
                            </fieldset>

                            <div className="border-t border-border" />

                            {/* Section 2: Congregation */}
                            <fieldset className="grid gap-4">
                                <legend className="flex items-center gap-2 text-sm font-medium text-foreground">
                                    <span className="flex size-6 items-center justify-center rounded-full bg-primary text-xs font-semibold text-primary-foreground">
                                        2
                                    </span>
                                    <Church className="size-4 text-muted-foreground" />
                                    {t('Församling')}
                                </legend>

                                <div className="mt-2 grid gap-4 sm:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label htmlFor="congregation_name">
                                            {t('Församlingsnamn')}
                                        </Label>
                                        <Input
                                            id="congregation_name"
                                            type="text"
                                            required
                                            tabIndex={5}
                                            name="congregation_name"
                                            placeholder={t('Församlingsnamn')}
                                        />
                                        <InputError
                                            message={errors.congregation_name}
                                        />
                                        <p className="text-xs text-muted-foreground">
                                            &nbsp;
                                        </p>
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="congregation_number">
                                            {t('Församlingsnummer')}
                                        </Label>
                                        <Input
                                            id="congregation_number"
                                            type="text"
                                            required
                                            tabIndex={6}
                                            name="congregation_number"
                                            placeholder={t('Församlingsnummer')}
                                        />
                                        <InputError
                                            message={errors.congregation_number}
                                        />
                                        <p className="text-xs text-muted-foreground">
                                            {t(
                                                'Bara siffror och versaler (A–Z), max 20 tecken',
                                            )}
                                        </p>
                                    </div>
                                </div>
                            </fieldset>

                            <div className="border-t border-border" />

                            {/* Section 3: Kingdom Hall */}
                            <fieldset className="grid gap-4">
                                <legend className="flex items-center gap-2 text-sm font-medium text-foreground">
                                    <span className="flex size-6 items-center justify-center rounded-full bg-primary text-xs font-semibold text-primary-foreground">
                                        3
                                    </span>
                                    <Building2 className="size-4 text-muted-foreground" />
                                    {t('Rikets sal')}
                                </legend>

                                <div className="mt-2 grid gap-4 sm:grid-cols-2">
                                    <div className="grid gap-2 sm:col-span-2">
                                        <Label htmlFor="street_address">
                                            {t('Gatuadress')}
                                        </Label>
                                        <Input
                                            id="street_address"
                                            type="text"
                                            required
                                            tabIndex={7}
                                            name="street_address"
                                            placeholder={t('Gatuadress')}
                                        />
                                        <InputError
                                            message={errors.street_address}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="zip_code">
                                            {t('Postnummer')}
                                        </Label>
                                        <Input
                                            id="zip_code"
                                            type="text"
                                            required
                                            tabIndex={8}
                                            name="zip_code"
                                            placeholder={t('Postnummer')}
                                        />
                                        <InputError message={errors.zip_code} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="city">{t('Ort')}</Label>
                                        <Input
                                            id="city"
                                            type="text"
                                            required
                                            tabIndex={9}
                                            name="city"
                                            placeholder={t('Ort')}
                                        />
                                        <InputError message={errors.city} />
                                    </div>

                                    <div className="grid gap-2 sm:col-span-2">
                                        <Label htmlFor="country">
                                            {t('Land')}
                                        </Label>
                                        <Input
                                            id="country"
                                            type="text"
                                            required
                                            tabIndex={10}
                                            name="country"
                                            defaultValue="Sverige"
                                            placeholder={t('Land')}
                                        />
                                        <InputError message={errors.country} />
                                    </div>
                                </div>

                                {/* Existing Kingdom Hall warning */}
                                {superadmins.length > 0 && (
                                    <div className="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-900/50 dark:bg-amber-950/30">
                                        <p className="text-sm font-medium text-amber-800 dark:text-amber-200">
                                            {t(
                                                'Det finns redan en Rikets sal registrerad på denna adress.',
                                            )}
                                        </p>
                                        <p className="mt-1 text-sm text-amber-700 dark:text-amber-300">
                                            {t(
                                                'Kontakta en superadmin för att bli inbjuden till den befintliga Rikets salen:',
                                            )}
                                        </p>
                                        <ul className="mt-3 space-y-2">
                                            {superadmins.map((admin, index) => (
                                                <li
                                                    key={index}
                                                    className="flex items-center gap-2 text-sm"
                                                >
                                                    <span className="font-medium text-amber-900 dark:text-amber-100">
                                                        {admin.name}
                                                    </span>
                                                    <span className="text-amber-600 dark:text-amber-400">
                                                        —
                                                    </span>
                                                    <a
                                                        href={`mailto:${admin.email}`}
                                                        className="text-amber-700 underline underline-offset-2 hover:text-amber-900 dark:text-amber-300 dark:hover:text-amber-100"
                                                    >
                                                        {admin.email}
                                                    </a>
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                )}
                            </fieldset>

                            {/* Submit */}
                            <Button
                                type="submit"
                                className="w-full"
                                tabIndex={11}
                                data-test="register-user-button"
                            >
                                {processing && <Spinner />}
                                {t('Skapa konto')}
                            </Button>

                            <div className="text-center text-sm text-muted-foreground">
                                {t('Har du redan ett konto?')}{' '}
                                <TextLink
                                    href={login()}
                                    tabIndex={12}
                                    className="ml-2"
                                >
                                    {t('Logga in')}
                                </TextLink>
                            </div>
                        </>
                    );
                }}
            </Form>
        </>
    );
}

Register.layout = {
    title: 'Skapa ett konto',
    description:
        'Registrera dig, din församling och din Rikets sal för att komma igång',
    wide: true,
};
