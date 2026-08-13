import assert from 'node:assert/strict';
import test from 'node:test';

await import('../../public/js/tdrs/forms/prl/prl-print-paginator.js');

const { packByHeight, planPages, planFillerRows } = globalThis.PrlPrintPaginator;

function item(id, height) {
    return { id, height };
}

test('packs variable-height rows without exceeding the page budget', () => {
    const pages = packByHeight([
        item('short-1', 120),
        item('tall', 430),
        item('short-2', 180),
    ], 600);

    assert.deepEqual(pages.map((page) => page.map(({ id }) => id)), [
        ['short-1', 'tall'],
        ['short-2'],
    ]);
});

test('moves an ordered suffix to a final page that reserves stamp space', () => {
    const pages = planPages([
        item('row-1', 300),
        item('row-2', 300),
    ], 700, 400);

    assert.deepEqual(pages.map((page) => page.map(({ id }) => id)), [
        ['row-1'],
        ['row-2'],
    ]);
});

test('moves only the minimum final group instead of emptying the first page', () => {
    const pages = planPages([
        item('manual-and-first-row', 65),
        item('middle-rows', 300),
        item('last-row', 300),
    ], 700, 620);

    assert.deepEqual(pages.map((page) => page.map(({ id }) => id)), [
        ['manual-and-first-row', 'middle-rows'],
        ['last-row'],
    ]);
});

test('uses a stamps-only final page instead of clipping an oversized row', () => {
    const pages = planPages([item('oversized-row', 900)], 1000, 500);

    assert.deepEqual(pages.map((page) => page.map(({ id }) => id)), [
        ['oversized-row'],
        [],
    ]);
});

test('fills the final-page remainder with complete blank grid rows', () => {
    const heights = planFillerRows(118.5, 32);

    assert.equal(heights.length, 3);
    assert.deepEqual(heights, [32, 32, 54.5]);
    assert.equal(heights.reduce((total, height) => total + height, 0), 118.5);
});
