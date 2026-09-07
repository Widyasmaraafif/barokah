export type PublicSettings = Record<string, unknown>;

let cache: PublicSettings | null = null;

export async function fetchPublicSettings(
    force = false,
): Promise<PublicSettings> {
    if (cache !== null && !force) {
        return cache;
    }

    const response = await fetch('/api/v1/settings/public', {
        headers: { Accept: 'application/json' },
    });

    if (!response.ok) {
        throw new Error('Failed to load public settings.');
    }

    cache = (await response.json()) as PublicSettings;

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
