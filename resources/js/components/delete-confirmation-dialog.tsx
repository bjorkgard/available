import { router } from '@inertiajs/react';
import { TriangleAlertIcon } from 'lucide-react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { toast } from 'sonner';

import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    title: string;
    description: string;
    action: string;
    confirmationInput?: {
        label: string;
        expectedValue: string;
    };
    warning?: string;
};

export default function DeleteConfirmationDialog({
    open,
    onOpenChange,
    title,
    description,
    action,
    confirmationInput,
    warning,
}: Props) {
    const { t } = useTranslation();
    const [confirmValue, setConfirmValue] = useState('');
    const [processing, setProcessing] = useState(false);

    const handleOpenChange = (nextOpen: boolean) => {
        onOpenChange(nextOpen);

        if (!nextOpen) {
            setConfirmValue('');
        }
    };

    const isConfirmDisabled =
        processing ||
        (confirmationInput
            ? confirmValue !== confirmationInput.expectedValue
            : false);

    const handleConfirm = () => {
        setProcessing(true);

        router.delete(action, {
            onSuccess: () => {
                toast.success(t('Borttaget.'));
                onOpenChange(false);
            },
            onError: () => {
                toast.error(t('Något gick fel. Försök igen.'));
            },
            onFinish: () => {
                setProcessing(false);
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                    <DialogDescription>{description}</DialogDescription>
                </DialogHeader>

                {warning && (
                    <div className="flex items-start gap-3 rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-200">
                        <TriangleAlertIcon className="mt-0.5 size-4 shrink-0" />
                        <span>{warning}</span>
                    </div>
                )}

                {confirmationInput && (
                    <div className="grid gap-2">
                        <Label htmlFor="delete-confirmation-input">
                            {confirmationInput.label}
                        </Label>
                        <Input
                            id="delete-confirmation-input"
                            type="text"
                            value={confirmValue}
                            onChange={(e) => setConfirmValue(e.target.value)}
                            autoComplete="off"
                        />
                    </div>
                )}

                <DialogFooter className="gap-2">
                    <DialogClose asChild>
                        <Button variant="secondary">{t('Avbryt')}</Button>
                    </DialogClose>

                    <Button
                        variant="destructive"
                        disabled={isConfirmDisabled}
                        onClick={handleConfirm}
                    >
                        {t('Bekräfta')}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
