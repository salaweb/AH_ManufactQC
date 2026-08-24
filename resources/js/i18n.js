import { createI18n } from 'vue-i18n';
import ca from '../lang/ca.json';
import es from '../lang/es.json';

const STORAGE_KEY = 'ah_manufactqc_locale';

function initialLocale() {
    try {
        const stored = localStorage.getItem(STORAGE_KEY);
        if (stored === 'ca' || stored === 'es') {
            return stored;
        }
    } catch {
        //
    }

    return 'ca';
}

export const i18n = createI18n({
    legacy: false,
    locale: initialLocale(),
    fallbackLocale: 'ca',
    messages: { ca, es },
});

export function setLocale(locale) {
    i18n.global.locale.value = locale;

    try {
        localStorage.setItem(STORAGE_KEY, locale);
    } catch {
        //
    }
}

export { STORAGE_KEY };
