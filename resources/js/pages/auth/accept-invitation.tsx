import { Form, Head } from '@inertiajs/react';

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
    return (
        <>
            <Head title="Accept Invitation" />
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
                                You've been invited to join{' '}
                                <span className="font-medium text-foreground">
                                    {invitation.congregation_name}
                                </span>
                                . Create a password to set up your account.
                            </p>

                            <div className="grid gap-2">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    type="text"
                                    value={invitation.name}
                                    disabled
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Email address</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    value={invitation.email}
                                    disabled
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password">Password</Label>
                                <PasswordInput
                                    id="password"
                                    required
                                    autoFocus
                                    tabIndex={1}
                                    autoComplete="new-password"
                                    name="password"
                                    placeholder="Password"
                                    passwordrules={passwordRules}
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password_confirmation">
                                    Confirm password
                                </Label>
                                <PasswordInput
                                    id="password_confirmation"
                                    required
                                    tabIndex={2}
                                    autoComplete="new-password"
                                    name="password_confirmation"
                                    placeholder="Confirm password"
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
                                Create account & join
                            </Button>
                        </div>
                    </>
                )}
            </Form>
        </>
    );
}

AcceptInvitation.layout = {
    title: 'Accept invitation',
    description: 'Set up your password to join the congregation',
};
