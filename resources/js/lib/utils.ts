import { clsx } from 'clsx';
import type { ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function formatDuration(ms: number): string {
    if (ms < 1000) {
        return `${ms}ms`;
    }

    if (ms < 60_000) {
        return `${(ms / 1000).toFixed(1)}s`;
    }

    const minutes = Math.floor(ms / 60_000);
    const seconds = Math.round((ms % 60_000) / 1000);

    return `${minutes}m ${seconds}s`;
}

export function formatCost(cost: number | null): string {
    if (cost === null) {
        return 'Gratis / N/A';
    }

    if (cost === 0) {
        return 'Gratis';
    }

    return `$${cost.toFixed(6)}`;
}

export function formatRelativeTime(dateString: string | null): string {
    if (!dateString) {
        return '';
    }

    const diffDays = Math.floor(
        (Date.now() - new Date(dateString).getTime()) / 86_400_000,
    );

    if (diffDays <= 0) {
        return 'hoy';
    }

    if (diffDays === 1) {
        return 'hace 1 día';
    }

    if (diffDays < 30) {
        return `hace ${diffDays} días`;
    }

    if (diffDays < 365) {
        const months = Math.floor(diffDays / 30);

        return `hace ${months} mes${months === 1 ? '' : 'es'}`;
    }

    const years = Math.floor(diffDays / 365);

    return `hace ${years} año${years === 1 ? '' : 's'}`;
}
