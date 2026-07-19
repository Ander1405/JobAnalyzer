export type ProfileContact = {
    name: string | null;
    email: string | null;
    phone: string | null;
    location: string | null;
    linkedin: string | null;
    github: string | null;
};

export type ProfileLanguages = {
    items: string[];
    english_level: string | null;
};

export type Profile = {
    id: number;
    slug: string;
    label: string;
    contact: ProfileContact | null;
    headline: string | null;
    summary: string | null;
    experience: string[] | null;
    skills: string[] | null;
    education: string[] | null;
    languages: ProfileLanguages | null;
    certifications: string[] | null;
    raw_md: string;
    source_text: string | null;
    is_active: boolean;
};

export type ProfileSuggestionCategory = 'correction' | 'improvement';

export type ProfileSuggestionField =
    | 'headline'
    | 'summary'
    | 'english_level'
    | 'experience'
    | 'skills'
    | 'education'
    | 'certifications'
    | 'languages';

export type ProfileSuggestionAction = 'replace' | 'add' | 'remove';

export type ProfileSuggestion = {
    id: string;
    category: ProfileSuggestionCategory;
    field: ProfileSuggestionField;
    action: ProfileSuggestionAction;
    index: number | null;
    current: string | null;
    suggested: string | null;
    rationale: string;
};

export type ProfileReviewUsage = {
    durationMs: number;
    inputTokens: number | null;
    outputTokens: number | null;
    costUsd: number | null;
};
