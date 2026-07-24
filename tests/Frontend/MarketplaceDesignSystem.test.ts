import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

const projectFile = (path: string): string =>
    readFileSync(new URL(`../../${path}`, import.meta.url), 'utf8');

const marketplaceFiles = [
    'resources/js/views/Marketplace/MarketplaceView.vue',
    'resources/js/views/Marketplace/JobDetailView.vue',
    'resources/js/components/MarketplaceFilters.vue',
    'resources/js/components/JobsTable.vue',
    'resources/js/components/JobCard.vue',
    'resources/js/components/AiProviderSelector.vue',
    'resources/js/components/DiffViewer.vue',
];

test('keeps Marketplace surfaces on semantic design tokens', () => {
    const rawColorUtility =
        /(?:bg|text|border|ring)-(?:gray|slate|blue|green|red|amber|yellow|emerald|white|black)(?:-|\/|\b)/;
    const decorativeEmoji = /[✅✕★☆📍🤖⏱💰⚠🎁💡✂🚩📄📋🛠]/u;

    for (const file of marketplaceFiles) {
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

test('uses shared Marketplace instruments and accessible async feedback', () => {
    const marketplace = projectFile(
        'resources/js/views/Marketplace/MarketplaceView.vue',
    );
    const detail = projectFile(
        'resources/js/views/Marketplace/JobDetailView.vue',
    );
    const table = projectFile('resources/js/components/JobsTable.vue');
    const card = projectFile('resources/js/components/JobCard.vue');
    const layout = projectFile('resources/js/layouts/AppLayout.vue');

    assert.match(marketplace, /aria-live="polite"/);
    assert.match(marketplace, /if \(!response\.ok\)/);
    assert.match(detail, /<MatchScore/);
    assert.match(detail, /role="alert"/);
    assert.match(table, /<MatchScore/);
    assert.match(card, /<MatchScore/);
    assert.match(card, /<CompanyLogo/);
    assert.match(
        layout,
        /class="flex min-h-screen min-w-0 flex-1 flex-col"/,
        'The app shell must allow wide Marketplace content to scroll locally',
    );
});
