import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AppearanceTabs from '@/components/appearance-tabs';
import Heading from '@/components/heading';
import { edit as editAppearance } from '@/routes/appearance';

export default function Appearance() {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('Utseendeinställningar')} />

            <h1 className="sr-only">{t('Utseendeinställningar')}</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title={t('Utseendeinställningar')}
                    description={t(
                        'Uppdatera utseendeinställningarna för ditt konto',
                    )}
                />
                <AppearanceTabs />
            </div>
        </>
    );
}

Appearance.layout = {
    breadcrumbs: [
        {
            title: 'Utseendeinställningar',
            href: editAppearance(),
        },
    ],
};
