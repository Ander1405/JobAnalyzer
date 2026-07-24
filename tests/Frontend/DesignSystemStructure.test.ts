import assert from 'node:assert/strict';
import { readFileSync, readdirSync } from 'node:fs';
import test from 'node:test';

const projectFile = (path: string): string =>
    readFileSync(new URL(`../../${path}`, import.meta.url), 'utf8');

const components = [
    'BaseAvatar',
    'BaseButton',
    'BaseCard',
    'BaseDrawer',
    'BaseDropdown',
    'BaseInput',
    'BaseModal',
    'BaseSelect',
    'BaseSkeleton',
    'BaseTabs',
    'BaseTag',
    'BaseTextarea',
    'BaseToast',
    'BaseTooltip',
    'CompanyLogo',
    'EmptyState',
    'MatchScore',
];

test('exports and documents every public design-system component', () => {
    const index = projectFile('resources/js/components/ui/index.ts');
    const documentation = projectFile('DESIGN-SYSTEM.md');

    for (const component of components) {
        assert.match(
            index,
            new RegExp(`export \\{ default as ${component} \\}`),
        );
        assert.match(documentation, new RegExp('\\| `' + component + '`'));
    }

    assert.doesNotMatch(index, /BaseOverlay/);
});

test('keeps UI primitives on semantic tokens and explicit transitions', () => {
    const directory = new URL(
        '../../resources/js/components/ui/',
        import.meta.url,
    );

    for (const file of readdirSync(directory).filter((name) =>
        name.endsWith('.vue'),
    )) {
        const source = readFileSync(new URL(file, directory), 'utf8');

        assert.doesNotMatch(
            source,
            /#[0-9a-f]{3,8}\b/i,
            `${file} contains a raw color`,
        );
        assert.doesNotMatch(
            source,
            /transition-all/,
            `${file} uses transition-all`,
        );
    }
});

test('registers the showcase without changing backend routes', () => {
    const router = projectFile('resources/js/router.ts');
    const app = projectFile('resources/js/app.ts');
    const blade = projectFile('resources/views/app.blade.php');

    assert.match(router, /path: '\/profile\/design-system'/);
    assert.match(router, /DesignSystemView\.vue/);
    assert.match(app, /inertiaRouter\.on\('navigate'/);
    assert.match(app, /router\.replace\(targetPath\)/);
    assert.match(blade, /<html lang="es">/);
    assert.ok(
        blade.indexOf("localStorage.getItem('theme:preference')") <
            blade.indexOf('@vite'),
        'Theme preference must be applied before Vite mounts the app',
    );
});
