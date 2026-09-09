import { ref, type Ref } from 'vue';
import { formatPrice } from '@/services/priceFormatter';
import {
    fetchPublicSettings,
    getSetting,
    type PublicSettings,
} from '@/services/settingsService';

export type UseSettingsStoreReturn = {
    settings: Readonly<Ref<PublicSettings>>;
    isLoaded: Readonly<Ref<boolean>>;
    loadSettings: (force?: boolean) => Promise<void>;
    getSettingValue: <T>(key: string, fallback: T) => T;
    currencySymbol: () => string;
    formatAmount: (amount: number) => string;
};

const settings = ref<PublicSettings>({});
const isLoaded = ref(false);

function applyBrandColors(values: PublicSettings): void {
    if (typeof document === 'undefined') {
        return;
    }

    const favicon = values['branding.favicon_url'];
    if (typeof favicon === 'string' && favicon !== '') {
        let link = document.querySelector<HTMLLinkElement>('link[rel="icon"]');
        if (!link) {
            link = document.createElement('link');
            link.rel = 'icon';
            document.head.appendChild(link);
        }
        link.href = favicon;
    }

    const primary = values['branding.primary_color'];
    const secondary = values['branding.secondary_color'];

    if (typeof primary === 'string' && primary !== '') {
        document.documentElement.style.setProperty('--brand-primary', primary);
    }

    if (typeof secondary === 'string' && secondary !== '') {
        document.documentElement.style.setProperty('--accent-navy', secondary);
    }
}

export function useSettingsStore(): UseSettingsStoreReturn {
    async function loadSettings(force = false): Promise<void> {
        if (isLoaded.value && !force) {
            return;
        }

        const values = await fetchPublicSettings(force);

        settings.value = values;
        isLoaded.value = true;

        applyBrandColors(values);
    }

    function getSettingValue<T>(key: string, fallback: T): T {
        if (!(key in settings.value)) {
            return getSetting<T>(key, fallback);
        }

        return settings.value[key] as T;
    }

    function currencySymbol(): string {
        return getSettingValue<string>('currency.symbol', 'RM');
    }

    function formatAmount(amount: number): string {
        return formatPrice(
            amount,
            getSettingValue<string>('currency.symbol', 'RM'),
            getSettingValue<number>('currency.decimals', 2),
        );
    }

    return {
        settings,
        isLoaded,
        loadSettings,
        getSettingValue,
        currencySymbol,
        formatAmount,
    };
}
