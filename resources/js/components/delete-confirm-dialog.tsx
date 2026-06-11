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
                            ? 'Delete recurring booking'
                            : 'Delete booking'}
                    </AlertDialogTitle>
                    <AlertDialogDescription>
                        {isRecurring
                            ? `"${bookingName}" is part of a recurring series. How would you like to delete it?`
                            : `Are you sure you want to delete "${bookingName}"? This action cannot be undone.`}
                    </AlertDialogDescription>
                </AlertDialogHeader>

                <AlertDialogFooter>
                    <AlertDialogCancel onClick={onCancel}>
                        Cancel
                    </AlertDialogCancel>

                    {isRecurring ? (
                        <>
                            <AlertDialogAction
                                variant="outline"
                                onClick={() => onConfirm('this_only')}
                            >
                                Delete this only
                            </AlertDialogAction>
                            <AlertDialogAction
                                variant="destructive"
                                onClick={() => onConfirm('all_future')}
                            >
                                Delete all future
                            </AlertDialogAction>
                        </>
                    ) : (
                        <AlertDialogAction
                            variant="destructive"
                            onClick={() => onConfirm('all')}
                        >
                            Delete
                        </AlertDialogAction>
                    )}
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}
