import { Form, Head } from '@inertiajs/react';
import { Monitor, Smartphone } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import SessionController from '@/actions/App/Http/Controllers/Settings/SessionController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { useFlashToast } from '@/hooks/use-flash-toast';
import { edit as editSessions } from '@/routes/sessions';

type Session = {
    id: string;
    ip_address: string;
    browser: string;
    os: string;
    device_type: 'desktop' | 'mobile';
    last_active: string;
    is_current_device: boolean;
};

type Props = {
    sessions: Session[];
};

export default function Sessions({ sessions }: Props) {
    const { t } = useTranslation();
    useFlashToast();

    return (
        <>
            <Head title={t('Sessioner')} />

            <h1 className="sr-only">{t('Sessioner')}</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title={t('Aktiva sessioner')}
                    description={t(
                        'Hantera och övervaka dina aktiva sessioner på andra enheter',
                    )}
                />

                <ul className="space-y-4">
                    {sessions.map((session) => (
                        <li key={session.id} className="flex items-start gap-4">
                            <div className="text-muted-foreground">
                                {session.device_type === 'mobile' ? (
                                    <Smartphone className="h-5 w-5" />
                                ) : (
                                    <Monitor className="h-5 w-5" />
                                )}
                            </div>

                            <div className="flex-1 space-y-0.5">
                                <p className="text-sm leading-none font-medium">
                                    {session.browser} {t('på')} {session.os}
                                    {session.is_current_device && (
                                        <span className="ml-2 inline-flex items-center rounded-md bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700 ring-1 ring-green-600/20 ring-inset dark:bg-green-500/10 dark:text-green-400 dark:ring-green-500/20">
                                            {t('Denna enhet')}
                                        </span>
                                    )}
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    {session.ip_address} &middot;{' '}
                                    {session.last_active}
                                </p>
                            </div>
                        </li>
                    ))}
                </ul>
            </div>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title={t('Avsluta andra sessioner')}
                    description={t(
                        'Vid behov kan du logga ut alla andra sessioner',
                    )}
                />

                <Form
                    {...SessionController.destroy.form()}
                    options={{ preserveScroll: true }}
                    resetOnError={['password']}
                    resetOnSuccess
                    className="space-y-6"
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="password">
                                    {t('Lösenord')}
                                </Label>

                                <PasswordInput
                                    id="password"
                                    name="password"
                                    autoComplete="current-password"
                                    placeholder={t('Lösenord')}
                                />

                                <InputError message={errors.password} />
                            </div>

                            <Button
                                disabled={processing || sessions.length <= 1}
                            >
                                {t('Avsluta andra sessioner')}
                            </Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

Sessions.layout = {
    breadcrumbs: [
        {
            title: 'Sessioner',
            href: editSessions(),
        },
    ],
};
