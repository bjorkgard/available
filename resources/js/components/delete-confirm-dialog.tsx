import { useTranslation } from 'react-i18next';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';

export type DeleteScope = 'this_only' | 'all_future' | 'all';

type Props = {
    open: boolean;
    bookingName: string;
    isRecurring: boolean;
    onConfirm: (scope: DeleteScope) => void;
    onCancel: () => void;
};

export default function DeleteConfirmDialog({
    open,
    bookingName,
    isRecurring,
    onConfirm,
    onCancel,
}: Props) {
    const { t } = useTranslation();

    const handleOpenChange = (nextOpen: boolean) => {
        if (!nextOpen) {
            onCancel();
        }
    };

    return (
        <AlertDialog open={open} onOpenChange={handleOpenChange}>
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>
                        {isRecurring
                            ? t('Ta bort återkommande bokning')
                            : t('Ta bort bokning')}
                    </AlertDialogTitle>
                    <AlertDialogDescription>
                        {isRecurring
                            ? t(
                                  '":name" är del av en återkommande serie. Hur vill du ta bort den?',
                                  { name: bookingName },
                              )
                            : t(
                                  'Är du säker på att du vill ta bort ":name"? Denna åtgärd kan inte ångras.',
                                  { name: bookingName },
                              )}
                    </AlertDialogDescription>
                </AlertDialogHeader>

                <AlertDialogFooter>
                    <AlertDialogCancel onClick={onCancel}>
                        {t('Avbryt')}
                    </AlertDialogCancel>

                    {isRecurring ? (
                        <>
                            <AlertDialogAction
                                variant="outline"
                                onClick={() => onConfirm('this_only')}
                            >
                                {t('Bara denna')}
                            </AlertDialogAction>
                            <AlertDialogAction
                                variant="destructive"
                                onClick={() => onConfirm('all_future')}
                            >
                                {t('Alla framtida')}
                            </AlertDialogAction>
                        </>
                    ) : (
                        <AlertDialogAction
                            variant="destructive"
                            onClick={() => onConfirm('all')}
                        >
                            {t('Ta bort')}
                        </AlertDialogAction>
                    )}
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}
