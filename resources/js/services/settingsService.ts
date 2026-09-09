export type PublicSettings = Record<string, unknown>;

let cache: PublicSettings | null = null;

export async function fetchPublicSettings(
    force = false,
): Promise<PublicSettings> {
    if (cache !== null && !force) {
        return cache;
    }

    // Avoid an "ERR_INVALID_URL" crash during server-side rendering, where no
    // browser window/origin exists to resolve the relative API path against.
    if (typeof window === 'undefined' || typeof document === 'undefined') {
        return {};
    }

    const response = await fetch('/api/v1/settings/public', {
        headers: { Accept: 'application/json' },
    });

    if (!response.ok) {
        throw new Error('Failed to load public settings.');
    }

    const values = (await response.json()) as PublicSettings;

    for (const key of ['branding.logo_url', 'branding.favicon_url']) {
        const value = values[key];
        if (typeof value === 'string' && value !== '' && !value.startsWith('http')) {
            values[key] = `/storage/${value.replace(/^\/+/, '')}`;
        }
    }

    cache = values;

    return cache;
}

export function getSetting<T>(key: string, fallback: T): T {
    if (cache === null || !(key in cache)) {
        return fallback;
    }

    return cache[key] as T;
}

export function clearSettingsCache(): void {
    cache = null;
}
