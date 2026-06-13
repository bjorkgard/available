import { Form, Head, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';

const LOCALE_LABELS: Record<string, string> = {
    sv: 'Svenska',
    en: 'English',
};

export default function SetupWizard() {
    const { t } = useTranslation();
    const { supportedLocales } = usePage().props;
    const locales = (supportedLocales as string[]) ?? ['sv', 'en'];
    const [selectedLocale, setSelectedLocale] = useState('sv');

    return (
        <>
            <Head title={t('Konfigurera din rikets sal')} />
            <Form
                action="/setup"
                method="post"
                disableWhileProcessing
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <div className="grid gap-6">
                        <div className="grid gap-2">
                            <Label htmlFor="street_address">
                                {t('Gatuadress')}
                            </Label>
                            <Input
                                id="street_address"
                                type="text"
                                required
                                autoFocus
                                tabIndex={1}
                                autoComplete="street-address"
                                name="street_address"
                                placeholder={t('Gatuadress')}
                            />
                            <InputError message={errors.street_address} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="zip_code">{t('Postnummer')}</Label>
                            <Input
                                id="zip_code"
                                type="text"
                                required
                                tabIndex={2}
                                autoComplete="postal-code"
                                name="zip_code"
                                placeholder={t('Postnummer')}
                            />
                            <InputError message={errors.zip_code} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="city">{t('Stad')}</Label>
                            <Input
                                id="city"
                                type="text"
                                required
                                tabIndex={3}
                                autoComplete="address-level2"
                                name="city"
                                placeholder={t('Stad')}
                            />
                            <InputError message={errors.city} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="number_of_rooms">
                                {t('Antal rum')}
                            </Label>
                            <Input
                                id="number_of_rooms"
                                type="number"
                                required
                                tabIndex={4}
                                name="number_of_rooms"
                                min={1}
                                max={50}
                                placeholder="1"
                            />
                            <InputError message={errors.number_of_rooms} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="locale">{t('Språk')}</Label>
                            <Select
                                name="locale"
                                value={selectedLocale}
                                onValueChange={setSelectedLocale}
                            >
                                <SelectTrigger
                                    id="locale"
                                    tabIndex={5}
                                    className="w-full"
                                >
                                    <SelectValue
                                        placeholder={t('Välj språk')}
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    {locales.map((loc) => (
                                        <SelectItem key={loc} value={loc}>
                                            {LOCALE_LABELS[loc] ?? loc}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.locale} />
                        </div>

                        <Button
                            type="submit"
                            className="mt-2 w-full"
                            tabIndex={6}
                        >
                            {processing && <Spinner />}
                            {t('Slutför konfiguration')}
                        </Button>
                    </div>
                )}
            </Form>
        </>
    );
}

SetupWizard.layout = {
    title: 'Konfigurera din rikets sal',
    description: 'Konfigurera den fysiska platsen där din församling möts.',
};
