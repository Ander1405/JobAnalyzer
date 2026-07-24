import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

const projectFile = (path: string): string =>
    readFileSync(new URL(`../../${path}`, import.meta.url), 'utf8');

const trackingFiles = [
    'resources/js/views/Tracking/TrackingView.vue',
    'resources/js/views/Tracking/TrackedJobDetailView.vue',
    'resources/js/types/tracking.ts',
];

test('keeps Tracking surfaces on semantic design tokens', () => {
    const rawColorUtility =
        /(?:bg|text|border|ring|divide)-(?:gray|slate|blue|green|red|amber|yellow|emerald|white|black)(?:-|\/|\b)/;
    const decorativeEmoji = /[✅✕★☆📍🤖⏱💰⚠🎁💡✂🚩📄📋🛠📝🔄🎤📌☰]/u;

    for (const file of trackingFiles) {
        const source = projectFile(file);

        assert.doesNotMatch(
            source,
            rawColorUtility,
            `${file} uses a raw color`,
        );
        assert.doesNotMatch(
            source,
            /#[0-9a-f]{3,8}\b/i,
            `${file} uses a hex color`,
        );
        assert.doesNotMatch(
            source,
            /transition-all/,
            `${file} uses transition-all`,
        );
        assert.doesNotMatch(
            source,
            decorativeEmoji,
            `${file} uses emoji iconography`,
        );
    }
});

test('uses shared Tracking instruments and accessible interaction states', () => {
    const tracking = projectFile(
        'resources/js/views/Tracking/TrackingView.vue',
    );
    const detail = projectFile(
        'resources/js/views/Tracking/TrackedJobDetailView.vue',
    );
    const types = projectFile('resources/js/types/tracking.ts');

    for (const component of [
        'BaseButton',
        'BaseCard',
        'BaseSkeleton',
        'BaseTag',
        'CompanyLogo',
        'EmptyState',
        'AppIcon',
    ]) {
        assert.match(tracking, new RegExp(`<${component}`));
        assert.match(detail, new RegExp(`<${component}`));
    }

    assert.match(tracking, /<MatchScore/);
    assert.match(tracking, /aria-pressed=/);
    assert.match(tracking, /draggable="true"/);
    assert.match(tracking, /aria-describedby=/);
    assert.match(tracking, /Desplaza horizontalmente/);
    assert.match(tracking, /@click\.stop="openDetail\(trackedJob\)"/);
    assert.match(tracking, /focus-visible:outline-focus/);
    assert.match(tracking, /overflow-x-auto/);
    assert.match(tracking, /aria-live="polite"/);

    assert.match(detail, /<BaseSelect/);
    assert.match(detail, /<BaseTextarea/);
    assert.match(detail, /<MatchScore/);
    assert.match(detail, /<CompanyLogo/);
    assert.match(detail, /aria-live="polite"/);
    assert.match(detail, /focus-visible:outline-focus/);
    assert.match(detail, /<label[^>]*for="next-action"/);
    assert.match(detail, /autocomplete="off"/);
    assert.doesNotMatch(types, /COMMENT_TYPE_ICONS/);
});
