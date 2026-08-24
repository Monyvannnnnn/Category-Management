# Task: Fix Action Column Colors in Category Management Grid (DevExtreme)

You are working on the user's DevExtreme jQuery + PHP **Category Management** project
(files: `index.php`, `style.css`). Stack: jQuery 3.7.1, DevExtreme 23.1.6 dark theme
(dx.dark.css) with heavy custom CSS overrides.

Goal: make the Action column (edit/delete icons) look visually identical to the other
columns — no focus outlines, no different hover tint, header matching.

---

## Task 1 — Remove DevExtreme's focus/hover treatment on Action cells

Append to the END of `style.css` (so it wins over existing overrides):

```css
/* ---- ACTION COLUMN: match other columns ---- */

/* Remove focus outline & tint on cells containing buttons */
.dx-datagrid .dx-cell-focus-disabled {
    outline: none !important;
    box-shadow: none !important;
    background-color: transparent !important;
}

/* Hover behaves exactly like every other cell */
.dx-datagrid-rowsview .dx-row:hover td,
.dx-datagrid-rowsview .dx-row:hover .actions-cell {
    background-color: #303030 !important;
}
```

## Task 2 — Normalize icon colors

Keep icons visible but consistent with theme; append to `style.css`:

```css
.actions-cell .dx-link-edit   { color: #7dd3fc !important; }
.actions-cell .dx-link-delete { color: #f87171 !important; }
```

(If the user later wants neutral gray icons, swap to #94a3b8 for both.)

## Task 3 — Header consistency

```css
.dx-datagrid-headers td[aria-label="Action"],
.dx-datagrid-headers .dx-header-row td:last-child {
    background-color: #272727 !important;
    color: #9ca3af !important;
}
```

## Task 4 — Bump cache version in index.php

Change:
```html
<link rel="stylesheet" href="style.css?v=1.0.4">
```
(increment from whatever ?v= currently exists).

## Constraints

- Only edit `style.css` and the one `<link>` line in `index.php`.
- Do NOT touch grid JS config, columns, or PHP files.
- Do NOT remove existing dark-theme rules; only append new ones at file end.

## Verify

1. Reload with hard refresh (Ctrl+F5).
2. Action column cells show NO outline/tint when clicked.
3. Row hover color over the Action column matches hover over other columns.
4. "Action" header background identical to other headers.
