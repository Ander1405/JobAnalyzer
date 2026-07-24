export type MarketplaceSort = 'match' | 'recent' | 'salary';

export type MarketplaceFilters = {
    source: string;
    workMode: string;
    seniority: string;
    language: string;
    minMatch: number;
    search: string;
    hasSalaryOnly: boolean;
    hideTracked: boolean;
    sort: MarketplaceSort;
};

export function defaultMarketplaceFilters(): MarketplaceFilters {
    return {
        source: '',
        workMode: '',
        seniority: '',
        language: '',
        minMatch: 0,
        search: '',
        hasSalaryOnly: false,
        hideTracked: false,
        sort: 'match',
    };
}

export const WORK_MODE_OPTIONS = ['Remoto', 'Híbrido', 'Presencial'];
export const SENIORITY_OPTIONS = ['Junior', 'Mid', 'Senior', 'Lead'];
export const LANGUAGE_OPTIONS = ['Español', 'Inglés', 'Ambos'];
