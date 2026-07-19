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
    is_active: boolean;
};

export type ProfileShowResponse = {
    content: string;
    profile: Profile | null;
};

export type ProfileImportResponse = {
    content: string;
    profile: Profile;
};
