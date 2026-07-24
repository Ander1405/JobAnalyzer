import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

const profile = readFileSync(
    new URL(
        '../../resources/js/views/Profile/ProfileView.vue',
        import.meta.url,
    ),
    'utf8',
);

test('keeps Profile on semantic Signal Desk tokens', () => {
    const rawColorUtility =
        /(?:bg|text|border|ring)-(?:gray|slate|blue|green|red|amber|yellow|emerald|white|black)(?:-|\/|\b)/;
    const decorativeEmoji = /[✅✕★☆📍🤖⏱💰⚠🎁💡✂🚩📄📋🛠✓]/u;

    assert.doesNotMatch(profile, rawColorUtility, 'Profile uses a raw color');
    assert.doesNotMatch(
        profile,
        /#[0-9a-f]{3,8}\b/i,
        'Profile uses a hex color',
    );
    assert.doesNotMatch(
        profile,
        /transition-all/,
        'Profile uses transition-all',
    );
    assert.doesNotMatch(
        profile,
        decorativeEmoji,
        'Profile uses decorative emoji',
    );
});

test('uses shared Profile primitives and accessible feedback', () => {
    const sharedComponents = [
        'BaseTabs',
        'BaseCard',
        'BaseButton',
        'BaseInput',
        'BaseSelect',
        'BaseTextarea',
        'BaseTag',
        'EmptyState',
        'BaseSkeleton',
        'MatchScore',
        'DiffViewer',
        'AppIcon',
    ];

    for (const component of sharedComponents) {
        assert.match(profile, new RegExp(`<${component}\\b`));
    }

    assert.doesNotMatch(profile, /<button\b/);
    assert.doesNotMatch(profile, /<select\b/);
    assert.doesNotMatch(profile, /<textarea\b/);
    assert.match(profile, /role="alert"/);
    assert.match(profile, /role="status"/);
    assert.match(profile, /aria-live="polite"/);
    assert.match(profile, /aria-expanded="newVariantOpen"/);
    assert.match(profile, /loading-label=/);
    assert.match(profile, />\s*Actual\s*</);
    assert.match(profile, />\s*Sugerencia\s*</);
});
