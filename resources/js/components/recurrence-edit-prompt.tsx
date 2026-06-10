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

export type RecurrenceEditScope = 'this_only' | 'this_and_future';

type Props = {
    open: boolean;
    onSelect: (scope: RecurrenceEditScope) => void;
    onCancel: () => void;
};

export default function RecurrenceEditPrompt({
    open,
    onSelect,
    onCancel,
}: Props) {
    const handleOpenChange = (nextOpen: boolean) => {
        if (!nextOpen) {
            onCancel();
        }
    };

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Edit recurring booking</DialogTitle>
                    <DialogDescription>
                        This booking is part of a recurring series. How would
                        you like to apply your changes?
                    </DialogDescription>
                </DialogHeader>

                <DialogFooter className="gap-2 sm:gap-0">
                    <DialogClose asChild>
                        <Button variant="secondary">Cancel</Button>
                    </DialogClose>

                    <Button
                        variant="outline"
                        onClick={() => onSelect('this_only')}
                    >
                        This occurrence only
                    </Button>

                    <Button onClick={() => onSelect('this_and_future')}>
                        This and all future
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
