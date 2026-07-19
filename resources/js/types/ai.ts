export type AiProviderId = 'claude_cli' | 'gemini' | 'openrouter';

export type AiProviderOption = {
    id: AiProviderId;
    label: string;
};

export type AiModelOption = {
    id: string;
    label: string;
    free: boolean;
    promptPrice: number | null;
    completionPrice: number | null;
};

export type AiSettings = {
    id: number;
    provider: AiProviderId;
    model: string | null;
    created_at: string;
    updated_at: string;
};
