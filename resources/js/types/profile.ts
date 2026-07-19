export type ProfileUsage = {
    durationMs: number;
    inputTokens: number | null;
    outputTokens: number | null;
    costUsd: number | null;
};

export type ProfileUploadResponse = {
    content: string;
    model: string;
    usage: ProfileUsage;
};
