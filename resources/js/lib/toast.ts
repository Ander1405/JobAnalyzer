import { reactive } from 'vue';

export type ToastType = 'success' | 'error' | 'info';

export type Toast = {
    id: number;
    type: ToastType;
    message: string;
};

let nextId = 1;
const toasts = reactive<Toast[]>([]);

function push(message: string, type: ToastType, duration = 5000): void {
    const id = nextId++;
    toasts.push({ id, type, message });

    setTimeout(() => dismiss(id), duration);
}

function dismiss(id: number): void {
    const index = toasts.findIndex((toast) => toast.id === id);

    if (index !== -1) {
        toasts.splice(index, 1);
    }
}

export function useToast() {
    return {
        toasts,
        error: (message: string) => push(message, 'error'),
        success: (message: string) => push(message, 'success'),
        info: (message: string) => push(message, 'info'),
        dismiss,
    };
}
