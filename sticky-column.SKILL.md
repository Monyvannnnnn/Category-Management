# Make a Middle Column Sticky on Scroll (DevExtreme DataGrid)

**Goal right now:** When the user right-clicks the **Zipcode** (or any middle) column
and chooses **"Fix Sticky"**, that column should follow the horizontal scroll, then
STICK at the edge of the fixed-left column — and only the other non-sticky columns
keep sliding past. This is DevExtreme `fixedPosition: "sticky"`.

## What is already in place (do NOT re-add)
- `columnFixing: { enabled: true }` is on in the grid config.
- `category_code` column has `fixed: true, fixedPosition: "left"`.
- `Action` column has `fixed: true, fixedPosition: "right"`.
- Right-click context menu already has "Fix Left", "Fix Right", "Fix Sticky", "Unfix".

## Steps

1. **Clear stale state once** (old saved mode can override the new sticky):
   In browser console (F12):
   ```js
   localStorage.removeItem("categoryGridStateV9");
   location.reload();
   ```

2. **Right-click the middle column header** (e.g. Zipcode / State / any date column) →
   choose **"Fix Sticky"**.

3. **Test the scroll:** scroll left with the touchpad. The sticky column moves WITH the
   content until it reaches the right edge of the fixed-left `category_code` column,
   then it stops there. Only the unfixed columns keep sliding away. Scroll right and it
   behaves symmetrically toward the fixed-right `Action` column.

## To make a column sticky by default (no right-click)
In `index.php`, add these two props to that column's definition:
```js
fixed: true,
fixedPosition: "sticky",
```

## If sticky "feels the same as Fix Left"
Run step 1 again — the saved `categoryGridStateV9` is overriding. Sticky only looks
distinct on a MIDDLE column between a fixed-left and fixed-right column.

## Limitation
Do not stick every column — then nothing scrolls. Keep at least one fixed-left, one
fixed-right, and let the middle ones be sticky/unfixed.
