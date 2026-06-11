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
                            ? 'Ta bort återkommande bokning'
                            : 'Ta bort bokning'}
                    </AlertDialogTitle>
                    <AlertDialogDescription>
                        {isRecurring
                            ? `"${bookingName}" är del av en återkommande serie. Hur vill du ta bort den?`
                            : `Är du säker på att du vill ta bort "${bookingName}"? Denna åtgärd kan inte ångras.`}
                    </AlertDialogDescription>
                </AlertDialogHeader>

                <AlertDialogFooter>
                    <AlertDialogCancel onClick={onCancel}>
                        Avbryt
                    </AlertDialogCancel>

                    {isRecurring ? (
                        <>
                            <AlertDialogAction
                                variant="outline"
                                onClick={() => onConfirm('this_only')}
                            >
                                Bara denna
                            </AlertDialogAction>
                            <AlertDialogAction
                                variant="destructive"
                                onClick={() => onConfirm('all_future')}
                            >
                                Alla framtida
                            </AlertDialogAction>
                        </>
                    ) : (
                        <AlertDialogAction
                            variant="destructive"
                            onClick={() => onConfirm('all')}
                        >
                            Ta bort
                        </AlertDialogAction>
                    )}
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}
