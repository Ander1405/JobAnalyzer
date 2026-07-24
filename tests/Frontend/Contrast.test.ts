import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

const css = readFileSync(
    new URL('../../resources/css/app.css', import.meta.url),
    'utf8',
);
const lightTokens = css.slice(css.indexOf(':root'), css.indexOf('.dark {'));
const darkTokens = css.slice(css.indexOf('.dark {'), css.indexOf('@theme'));

function token(block: string, name: string): string {
    const match = block.match(
        new RegExp(`--jh-${name}:\\s*(#[0-9a-f]{6})`, 'i'),
    );

    assert.ok(match, `Missing color token --jh-${name}`);

    return match[1];
}

function luminance(hex: string): number {
    const channels = [1, 3, 5].map(
        (offset) => Number.parseInt(hex.slice(offset, offset + 2), 16) / 255,
    );
    const linearChannels = channels.map((channel) =>
        channel <= 0.04045
            ? channel / 12.92
            : ((channel + 0.055) / 1.055) ** 2.4,
    );

    return (
        linearChannels[0] * 0.2126 +
        linearChannels[1] * 0.7152 +
        linearChannels[2] * 0.0722
    );
}

function contrast(foreground: string, background: string): number {
    const lighter = Math.max(luminance(foreground), luminance(background));
    const darker = Math.min(luminance(foreground), luminance(background));

    return (lighter + 0.05) / (darker + 0.05);
}

for (const [mode, tokens] of [
    ['light', lightTokens],
    ['dark', darkTokens],
] as const) {
    test(`${mode} semantic text colors meet WCAG AA`, () => {
        const pairs = [
            ['text', 'surface'],
            ['text-muted', 'surface'],
            ['text-subtle', 'surface'],
            ['text-subtle', 'canvas'],
            ['primary-contrast', 'primary'],
            ['secondary', 'surface'],
            ['secondary-contrast', 'secondary'],
            ['error-contrast', 'error'],
            ['score-excellent', 'score-excellent-surface'],
            ['score-very-good', 'score-very-good-surface'],
            ['score-acceptable', 'score-acceptable-surface'],
            ['score-low', 'score-low-surface'],
        ] as const;

        for (const [foreground, background] of pairs) {
            assert.ok(
                contrast(
                    token(tokens, foreground),
                    token(tokens, background),
                ) >= 4.5,
                `${mode}: ${foreground} on ${background} must reach 4.5:1`,
            );
        }
    });

    test(`${mode} control boundaries and focus indicators meet WCAG AA`, () => {
        const pairs = [
            ['border-strong', 'surface'],
            ['focus', 'surface'],
            ['focus', 'canvas'],
        ] as const;

        for (const [foreground, background] of pairs) {
            assert.ok(
                contrast(
                    token(tokens, foreground),
                    token(tokens, background),
                ) >= 3,
                `${mode}: ${foreground} on ${background} must reach 3:1`,
            );
        }
    });
}
