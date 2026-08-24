# Task: Add Touchpad Horizontal Scrolling to Category Management Grid

You are working on the user's DevExtreme jQuery + PHP **Category Management** project
(files: `index.php`, `style.css`). Stack: jQuery 3.7.1, DevExtreme 23.1.6 dark theme.
The wide grid sits inside a `.table-wrapper` div that already has `overflow-x: auto`.

Goal: let the user reach the last columns by scrolling with the TOUCHPAD only.
Do NOT add any buttons or UI elements.

---

## Task 1 — Vertical touchpad scroll moves the table horizontally

Add this inside the existing `$(function() { ... })` block in `index.php`
(AFTER the grid initialization, near the other wiring like searchInput):

```js
// Vertical wheel/touchpad scroll over the grid = horizontal table scroll,
// so users can reach the last columns with a normal touchpad scroll.
$(".table-wrapper").on("wheel", function(e) {
    var deltaY = e.originalEvent.deltaY;
    if (deltaY !== 0) {
        this.scrollLeft += deltaY;
        e.preventDefault();
    }
});
```

## Task 2 (fallback) — Shift + scroll variant

If hijacking all vertical scrolling over the grid makes the page hard to scroll,
REPLACE the Task 1 handler body with this shift-gated version:

```js
$(".table-wrapper").on("wheel", function(e) {
    if (e.shiftKey) {
        this.scrollLeft += e.originalEvent.deltaY;
        e.preventDefault();
    }
});
```

If the agent cannot ask the user which they prefer, implement Task 1 and leave
Task 2 as a commented-out alternative directly below it.

## Constraints

- Do NOT add any buttons, arrows, or visible UI elements.
- Do NOT modify DataGrid column config, resizing settings, or state storing.
- Do NOT remove `overflow-x: auto` from `.table-wrapper`.
- Only `index.php` should be edited; no CSS changes are needed for this task.

## Verify

1. Reload the page; two-finger vertical swipe on touchpad over the grid slides it horizontally to the last columns.
2. Two-finger horizontal swipe also still works natively.
3. Page still scrolls vertically normally when cursor is OUTSIDE the grid.
