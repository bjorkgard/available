import { useTranslation } from 'react-i18next';
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
    const { t } = useTranslation();

    const handleOpenChange = (nextOpen: boolean) => {
        if (!nextOpen) {
            onCancel();
        }
    };

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {t('Redigera återkommande bokning')}
                    </DialogTitle>
                    <DialogDescription>
                        {t(
                            'Den här bokningen är del av en återkommande serie. Hur vill du tillämpa dina ändringar?',
                        )}
                    </DialogDescription>
                </DialogHeader>

                <DialogFooter className="gap-2 sm:gap-0">
                    <DialogClose asChild>
                        <Button variant="secondary">{t('Avbryt')}</Button>
                    </DialogClose>

                    <Button
                        variant="outline"
                        onClick={() => onSelect('this_only')}
                    >
                        {t('Bara denna')}
                    </Button>

                    <Button onClick={() => onSelect('this_and_future')}>
                        {t('Denna och alla framtida')}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
