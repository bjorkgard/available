import { Form, Head } from '@inertiajs/react';
import { Monitor, Smartphone } from 'lucide-react';
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
    useFlashToast();

    return (
        <>
            <Head title="Sessions" />

            <h1 className="sr-only">Sessions</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Active sessions"
                    description="Manage and monitor your active sessions on other browsers and devices"
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
                                    {session.browser} on {session.os}
                                    {session.is_current_device && (
                                        <span className="ml-2 inline-flex items-center rounded-md bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700 ring-1 ring-green-600/20 ring-inset dark:bg-green-500/10 dark:text-green-400 dark:ring-green-500/20">
                                            This device
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
                    title="Terminate other sessions"
                    description="If needed, you can log out all your other sessions"
                />

                <Form
                    {...SessionController.destroy.form()}
                    options={{ preserveScroll: true }}
                    resetOnError={['password']}
                    className="space-y-6"
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="password">Password</Label>

                                <PasswordInput
                                    id="password"
                                    name="password"
                                    className="mt-1 block w-full"
                                    autoComplete="current-password"
                                    placeholder="Password"
                                />

                                <InputError message={errors.password} />
                            </div>

                            <Button
                                disabled={processing || sessions.length <= 1}
                            >
                                Terminate other sessions
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
            title: 'Sessions',
            href: editSessions(),
        },
    ],
};
