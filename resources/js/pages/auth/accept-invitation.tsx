import { Form, Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

type Props = {
    invitation: {
        code: string;
        name: string;
        email: string;
        congregation_name: string;
        role: string;
    };
    passwordRules: string;
};

export default function AcceptInvitation({ invitation, passwordRules }: Props) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('Acceptera inbjudan')} />
            <Form
                action={`/invitations/${invitation.code}/accept`}
                method="post"
                resetOnSuccess={['password', 'password_confirmation']}
                disableWhileProcessing
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-6">
                            <p className="text-sm text-muted-foreground">
                                {t('Du har blivit inbjuden att gå med i')}{' '}
                                <span className="font-medium text-foreground">
                                    {invitation.congregation_name}
                                </span>
                                .{' '}
                                {t(
                                    'Skapa ett lösenord för att konfigurera ditt konto.',
                                )}
                            </p>

                            <div className="grid gap-2">
                                <Label htmlFor="name">{t('Namn')}</Label>
                                <Input
                                    id="name"
                                    type="text"
                                    value={invitation.name}
                                    disabled
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">
                                    {t('E-postadress')}
                                </Label>
                                <Input
                                    id="email"
                                    type="email"
                                    value={invitation.email}
                                    disabled
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password">
                                    {t('Lösenord')}
                                </Label>
                                <PasswordInput
                                    id="password"
                                    required
                                    autoFocus
                                    tabIndex={1}
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
                                    tabIndex={2}
                                    autoComplete="new-password"
                                    name="password_confirmation"
                                    placeholder={t('Bekräfta lösenord')}
                                    passwordrules={passwordRules}
                                />
                                <InputError
                                    message={errors.password_confirmation}
                                />
                            </div>

                            <Button
                                type="submit"
                                className="mt-2 w-full"
                                tabIndex={3}
                            >
                                {processing && <Spinner />}
                                {t('Skapa konto och gå med')}
                            </Button>
                        </div>
                    </>
                )}
            </Form>
        </>
    );
}

AcceptInvitation.layout = {
    title: 'Acceptera inbjudan',
    description: 'Konfigurera ditt lösenord för att gå med i församlingen',
};
