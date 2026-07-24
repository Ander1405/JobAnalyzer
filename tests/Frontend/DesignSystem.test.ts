import assert from 'node:assert/strict';
import test from 'node:test';
import {
    getMatchScoreMeta,
    normalizeMatchScore,
} from '../../resources/js/lib/designSystem.ts';

test('normalizes match scores to the supported range', () => {
    assert.equal(normalizeMatchScore(null), null);
    assert.equal(normalizeMatchScore(Number.NaN), null);
    assert.equal(normalizeMatchScore(-10), 0);
    assert.equal(normalizeMatchScore(72.6), 73);
    assert.equal(normalizeMatchScore(140), 100);
});

test('maps every score boundary to its semantic tier', () => {
    assert.equal(getMatchScoreMeta(null).tier, 'unscored');
    assert.equal(getMatchScoreMeta(59).tier, 'low');
    assert.equal(getMatchScoreMeta(60).tier, 'acceptable');
    assert.equal(getMatchScoreMeta(74).tier, 'acceptable');
    assert.equal(getMatchScoreMeta(75).tier, 'very-good');
    assert.equal(getMatchScoreMeta(84).tier, 'very-good');
    assert.equal(getMatchScoreMeta(84.4).tier, 'very-good');
    assert.equal(getMatchScoreMeta(84.5).tier, 'excellent');
    assert.equal(getMatchScoreMeta(84.9).tier, 'excellent');
    assert.equal(getMatchScoreMeta(85).tier, 'excellent');
    assert.equal(getMatchScoreMeta(100).tier, 'excellent');
});
