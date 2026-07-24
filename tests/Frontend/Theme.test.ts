import assert from 'node:assert/strict';
import test from 'node:test';
import {
    getStoredThemePreference,
    resolveTheme,
    setThemePreference,
    THEME_CHANGE_EVENT,
} from '../../resources/js/lib/theme.ts';

test('resolves explicit and system theme preferences', () => {
    assert.equal(resolveTheme('light', true), 'light');
    assert.equal(resolveTheme('dark', false), 'dark');
    assert.equal(resolveTheme('system', true), 'dark');
    assert.equal(resolveTheme('system', false), 'light');
});

test('persists explicit choices and applies them to the document', () => {
    const storage = new Map<string, string>();
    let darkClassApplied = false;
    let themeColor = '';
    let dispatchedPreference = '';

    class TestCustomEvent {
        type: string;
        detail: string;

        constructor(type: string, init: { detail: string }) {
            this.type = type;
            this.detail = init.detail;
        }
    }

    Object.assign(globalThis, {
        window: {
            localStorage: {
                getItem: (key: string) => storage.get(key) ?? null,
                setItem: (key: string, value: string) =>
                    storage.set(key, value),
                removeItem: (key: string) => storage.delete(key),
            },
            matchMedia: () => ({ matches: false }),
            dispatchEvent: (event: TestCustomEvent) => {
                assert.equal(event.type, THEME_CHANGE_EVENT);
                dispatchedPreference = event.detail;
            },
        },
        document: {
            querySelector: () => ({
                setAttribute: (_name: string, value: string) => {
                    themeColor = value;
                },
            }),
            documentElement: {
                classList: {
                    toggle: (_className: string, force: boolean) => {
                        darkClassApplied = force;
                    },
                },
                dataset: {},
            },
        },
        CustomEvent: TestCustomEvent,
    });

    try {
        setThemePreference('dark');
        assert.equal(getStoredThemePreference(), 'dark');
        assert.equal(darkClassApplied, true);
        assert.equal(themeColor, '#08101f');
        assert.equal(dispatchedPreference, 'dark');

        setThemePreference('system');
        assert.equal(getStoredThemePreference(), 'system');
        assert.equal(darkClassApplied, false);
        assert.equal(themeColor, '#f2f5fb');
        assert.equal(dispatchedPreference, 'system');
    } finally {
        Reflect.deleteProperty(globalThis, 'window');
        Reflect.deleteProperty(globalThis, 'document');
        Reflect.deleteProperty(globalThis, 'CustomEvent');
    }
});

test('falls back safely when browser storage is unavailable', () => {
    Object.assign(globalThis, {
        window: {
            localStorage: {
                getItem: () => {
                    throw new Error('Storage unavailable');
                },
                setItem: () => {
                    throw new Error('Storage unavailable');
                },
            },
            matchMedia: () => ({ matches: false }),
            dispatchEvent: () => true,
        },
        document: {
            querySelector: () => null,
            documentElement: {
                classList: { toggle: () => undefined },
                dataset: {},
            },
        },
        CustomEvent: class {
            constructor(_type: string, _init: { detail: string }) {}
        },
    });

    try {
        assert.equal(getStoredThemePreference(), 'system');
        assert.doesNotThrow(() => setThemePreference('dark'));
    } finally {
        Reflect.deleteProperty(globalThis, 'window');
        Reflect.deleteProperty(globalThis, 'document');
        Reflect.deleteProperty(globalThis, 'CustomEvent');
    }
});
