import malaysiaCities from '@/data/malaysia-cities.json';

export type MalaysiaCity = {
    id: string;
    lat: string;
    long: string;
    name: string;
    isIn: string;
};

const citiesTable = malaysiaCities as Record<string, MalaysiaCity[]>;

export function getMalaysiaCities(stateName: string): string[] {
    return (citiesTable[stateName] ?? []).map((city) => city.name);
}

export function isMalaysiaCityInState(city: string, stateName: string): boolean {
    return getMalaysiaCities(stateName).includes(city);
}
