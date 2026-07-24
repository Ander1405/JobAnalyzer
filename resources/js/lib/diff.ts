export type DiffRow = {
    left: string | null;
    right: string | null;
    type: 'same' | 'removed' | 'added';
};

/**
 * Line-level LCS diff. CV-sized inputs (tens to low hundreds of lines), so the
 * O(n*m) table is trivial — no diffing library needed for this.
 */
export function lineDiff(before: string, after: string): DiffRow[] {
    const a = before.split('\n');
    const b = after.split('\n');
    const n = a.length;
    const m = b.length;

    const lcs: number[][] = Array.from({ length: n + 1 }, () =>
        new Array(m + 1).fill(0),
    );

    for (let i = n - 1; i >= 0; i--) {
        for (let j = m - 1; j >= 0; j--) {
            lcs[i][j] =
                a[i] === b[j]
                    ? lcs[i + 1][j + 1] + 1
                    : Math.max(lcs[i + 1][j], lcs[i][j + 1]);
        }
    }

    const rows: DiffRow[] = [];
    let i = 0;
    let j = 0;

    while (i < n && j < m) {
        if (a[i] === b[j]) {
            rows.push({ left: a[i], right: b[j], type: 'same' });
            i++;
            j++;
        } else if (lcs[i + 1][j] >= lcs[i][j + 1]) {
            rows.push({ left: a[i], right: null, type: 'removed' });
            i++;
        } else {
            rows.push({ left: null, right: b[j], type: 'added' });
            j++;
        }
    }

    while (i < n) {
        rows.push({ left: a[i], right: null, type: 'removed' });
        i++;
    }

    while (j < m) {
        rows.push({ left: null, right: b[j], type: 'added' });
        j++;
    }

    return rows;
}
