export type PaginatedResponse<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    meta?: { current_page: number; last_page: number; total: number };
};

export async function fetchAdminList<T>(url: string): Promise<T[]> {
    const response = await fetch(url, {
        headers: { Accept: 'application/json' },
    });

    if (!response.ok) {
        throw new Error(`Request failed: ${response.status}`);
    }

    const payload = (await response.json()) as
        | { data: T[] }
        | { data: { data: T[] } };

    if (Array.isArray((payload as { data: T[] }).data)) {
        return (payload as { data: T[] }).data;
    }

    return (payload as { data: { data: T[] } }).data.data;
}
