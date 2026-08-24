# Category Management Project — Fixes & Instructions MD File

**Project:** DevExtreme jQuery + PHP Category Management CRUD app  
**Stack:** jQuery 3.7.1, DevExtreme 23.1.6 (dx.dark.css), MySQLi, ExcelJS, localStorage  
**Location:** Project folder (e.g., `C:\xampp\htdocs\category-app\`)  

---

## 📌 Quick Start — For a CLI Agent (Antigravity, Claude, Cursor, etc.)

1. Open the project folder
2. Run: `Read category-management-project-fixes.SKILL.md and apply every task in order.`
3. Verify each step as instructed
4. When finished, generate `PROJECT_NOTES.md` as described in the final task

---

## 📋 Task 1 — Touchpad Horizontal Scrolling (Windows Laptops Only)

**Problem:** Two-finger vertical swipe on ASUS TUF A15 touchpad doesn't scroll the DataGrid horizontally. Chrome's `passive: false` fix is needed.

**In `index.php` — inside the `$(function() { ... })` block, AFTER the grid initialization:**

```js
// Touchpad horizontal scrolling for Windows laptops (ASUS TUF etc.)
// Targets the DevExtreme internal scrollable container.
$(".table-wrapper").each(function() {
    this.addEventListener("wheel", function(e) {
        if (Math.abs(e.deltaX) <= Math.abs(e.deltaY)) return;

        var $inner = $(this).find(
            ".dx-datagrid-rowsview .dx-scrollable-container"
        ).first();
        if (!$inner.length) return;

        var el = $inner[0];
        if (el.scrollWidth > el.clientWidth) {
            el.scrollLeft += e.deltaX;
            e.preventDefault();
        }
    }, { passive: false });
});
```

**Verify:** Two-finger vertical swipe over the grid slides it horizontally to the last columns.

**Note:** This replaces/override any earlier wheel handler — only one `.on("wheel"...)` should exist in the file.

---

## Task 2 — Action Column Color & Styling Fixes

**Problem:** Action column (edit/delete icons) may have focus outlines, different hover tints, or zebra striping that doesn't match other columns.

**Append to the END of `style.css`:**

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

/* Normalize row backgrounds — transparent action cells can show zebra striping */
.dx-datagrid-rowsview .dx-row > td {
    background-color: #292929 !important;
}
.dx-datagrid-rowsview .dx-row-alt > td {
    background-color: #202020 !important;
}

/* Icon colors — keep visible but theme-consistent */
.actions-cell .dx-link-edit   { color: #7dd3fc !important; }
.actions-cell .dx-link-delete { color: #f87171 !important; }

/* Header consistency */
.dx-datagrid-headers td[aria-label="Action"],
.dx-datagrid-headers .dx-header-row td:last-child {
    background-color: #272727 !important;
    color: #9ca3af !important;
}
```

**Also add to `index.php`** (bump cache version):
```html
<link rel="stylesheet" href="style.css?v=1.0.5">
```

**Verify:** 
- No outline/tint when clicking Action cells
- Hover color over Action column matches other columns
- Icon colors are the soft blues/maroons shown above (not bright/ neon)

---

## Task 3 — Zebra Striping Fix (if rows look "half broken")

If the Action column cells show alternating dark/light shades differently from other columns (because transparent cells let the DevExtreme zebra layer show through):

**Append to `style.css` (already included in Task 2 above):**

```css
/* Explicit solid backgrounds for every row cell — prevents layer peek-through */
.dx-datagrid-rowsview .dx-row > td {
    background-color: #292929 !important;
}
.dx-datagrid-rowsview .dx-row-alt > td {
    background-color: #202020 !important;
}

/* Keep hover consistent across ALL cells including Action */
.dx-datagrid-rowsview .dx-row:hover > td {
    background-color: #303030 !important;
}
```

**Alternative (simpler):** In `index.php`, find `rowAlternationEnabled: true` and change to `false`. All rows will then be flat `#292929`.

---

## Task 4 — Column Resizing — Last Column Works

Your code already has the fix: `columnResizingMode: localStorage.getItem("categoryGridResizeMode") || "widget"` and `storageKey: "categoryGridStateV9"`.

**Verify:**
- Drag the Action (last) column left border → it should resize the grid
- Drag a middle column → it pushes the neighbor (widget mode) or shrinks/grows the grid
- The dxSelectBox at the top lets you switch between "widget" and "nextColumn" modes
- Bumped storage key V9 ensures old saved widths don't override

**If it still doesn't work:**
Run in browser console:
```js
localStorage.removeItem("categoryGridResizeMode");
localStorage.removeItem("categoryGridStateV9");
location.reload();
```

---

## Task 5 — Export Excel Button Uses Built-in DX Icon

Your code already uses `icon: "xlsxfile"` (correct — DevExtreme built-in, not FA). No changes needed.

**Verify:** Export Excel button shows a valid spreadsheet icon (not blank/broken).

---

## Task 6 — Credential Security (db_config.php + .htaccess)

**⚠️ Important for live deployment:**

1. **Create `db_config.php`** in your project folder (NOT inside `htdocs/` if you want it truly private, OR inside `htdocs/` with the `.htaccess` below):

```php
<?php
// db_config.php — DATABASE CREDENTIALS (do NOT share or commit this file)

// ---- LOCAL DEV (XAMPP) ----
$local = [
    "host" => "127.0.0.1",
    "user" => "root",
    "pass" => "",
    "name" => "inventory",
    "port" => 3307,
];

// ---- LIVE (InfinityFree) ----
$live = [
    "host" => "sql310.infinityfree.com",
    "user" => "if0_42693065",
    "pass" => "CHANGE-ME",              // ← put your NEW InfinityFree password here
    "name" => "if0_42693065_inventory",
    "port" => 3306,
];

$is_local = (php_sapi_name() === 'cli')
    || (isset($_SERVER['HTTP_HOST']) && in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1', 'localhost:8080']))
    || (isset($_SERVER['SERVER_ADDR']) && in_array($_SERVER['SERVER_ADDR'], ['127.0.0.1', '::1']));

return $is_local ? $local : $live;
```

2. **Add `.htaccess`** in the same folder as `db_config.php`:

```
<Files "db_config.php">
    Require all denied
</Files>
```

3. **Update `database.php`** to load from `db_config.php` (already done in your updated version — it uses `$cfg = require __DIR__ . "/db_config.php";`)

4. **Change your InfinityFree password** immediately — it was exposed in plain text earlier. Use the InfinityFree control panel → MySQL Databases → change password.

**Verify:** 
- Local dev still works (`localhost/yourproject`)
- `db_config.php` cannot be opened directly in a browser (403 Forbidden)
- Live grid connects with new password

---

## Task 5 — Bootstrap Conversion (Optional)

If you want to convert page-level CSS to Bootstrap 5:

**In `index.php <head>`** — add BEFORE the DevExtreme CSS:
```html
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
```

**At the end of `<body>`** — add:
```html
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
```

**Convert page markup** (card, header, buttons, search form) using Bootstrap classes — see the `convert-bootstrap.SKILL.md` file for exact code.

**KEEP** all `.dx-*` CSS rules in `style.css` — Bootstrap cannot style DevExtreme grid internals.

**Add dark-mode patch** to the END of `style.css`:
```css
body { background:#0f172a; }
.card.bg-dark { background:#1e293b !important; }
.form-control.bg-dark:focus {
  background:#172033; border-color:#6366f1;
  color:#e2e8f0; box-shadow:0 0 0 .2rem rgba(99,102,241,.25);
}
```

**⚠️ Note:** Only do this if you want Bootstrap for page UI (buttons, modals, layout). The DataGrid itself will still look like DevExtreme dark theme.

---

## Task 7 — Generate PROJECT_NOTES.md (Final Step)

After ALL tasks above are completed, create `PROJECT_NOTES.md` in your project root documenting:

**What the app is:**
- Category Management admin CRUD table
- PHP/MySQL backend with DevExtreme front-end
- XAMPP local development, InfinityFree deployment

**File map & role of every file:**
- `index.php` — main page (HTML + ALL JS initialization)
- `database.php` — DB connection + auto-migration, read/create/edit/delete endpoints
- `create.php` — insert new category (POST)
- `edit.php` — update category (POST)
- `delete.php` — delete category (GET with id)
- `db_config.php` — credentials (local vs live, loaded by database.php)
- `style.css` — dark theme overrides, DevExtreme internals, action column fixes
- `index.php` also contains inline JS for grid, search, export, touchpad, context menu
- `.htaccess` — blocks direct access to `db_config.php`
- `PROJECT_NOTES.md` — this file

**How each endpoint works:**
- `index.php?action=read` → JSON of all categories ORDER BY id DESC
- `create.php` → receives `category_code`, `category_name` via POST; validates; inserts; returns JSON success/error
- `edit.php` → receives id via GET, `category_code`/`category_name` via POST; validates name not duplicate; updates; returns JSON
- `delete.php` → receives id via GET; deletes row; returns JSON

**Grid features summary:**
- `key: "id"` in CustomStore — unique row identifier
- `columnResizingMode: "widget"` (default) or "nextColumn" via dxSelectBox
- `stateStoring` with key `categoryGridStateV9` — saves column widths in localStorage
- Custom search via `$("#searchInput").on("input", function() { grid.searchByText($(this).val()); })`
- Add: `$("#openAddModalBtn").on("click", function() { grid.addRow(); })`
- Delete/Edit: built-in popup grid editing (mode: "popup")
- Column fixing: right-click context menu → Fix Left/Right/Sticky/Unfix
- Horizontal scroll via two-finger swipe (Windows touchpads) or wheel handler
- Excel export: ExcelJS + hidden temp div, can export all pages or current page
- 6+ date columns with various formats (dd/MM/yyyy, HH:mm:ss, time ago, etc.)

**Database schema** (`category` table, auto-created by `database.php`):
- `id` int(11) AI PK
- `category_code` varchar(50) UNIQUE
- `category_name` varchar(100) UNIQUE
- `created_at` timestamp DEFAULT current_timestamp()
- `lastupdate` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp()

**Known limitations:**
- Client-side paging (loadMode: "raw" in CustomStore means all data downloaded; paging is UI-only)
- Requires JS-enabled browser
- Password in `db_config.php` must be kept private (never commit to GitHub)
- DevExtreme free license requires banner visible on public deployments (or remove `.dx-license { display: none !important; }` comment)
- Touchpad scrolling fix only works on Windows Precision Touchpads (ASUS TUF, etc.)

**How to run locally (XAMPP):**
1. Install XAMPP, start Apache + MySQL
2. Place project folder in `C:\xampp\htdocs\category-app\`
3. Ensure `database.php` `$is_local` detects correctly (check `phpinfo()` or open the page — if it connects to `127.0.0.1:3307`, it's local)
4. Visit `http://localhost/category-app/index.php`
5. The `category` table auto-creates on first visit
6. To import existing data: use phpMyAdmin locally (XAMPP → http://localhost/phpmyadmin), export your local SQL, then import via InfinityFree's phpMyAdmin or replicate manually

**Deployment to InfinityFree:**
1. Change your DB password on InfinityFree first (control panel)
2. Update `db_config.php` `$live["pass"]` with new password
3. Upload ALL files to `htdocs/` (index.php, database.php, create.php, edit.php, delete.php, style.css, db_config.php, .htaccess, assets if any)
4. Visit `https://your-epizy.com/index.php` (your InfinityFree subdomain)
5. First visit auto-creates the `category` table
6. Test Add/Edit/Delete

---

## 🛠️ Task Order for the CLI Agent

**Apply these in order.** Each task depends on the previous ones being done first.

1. ✅ Task 1 — Touchpad horizontal scrolling (already in code, just verify `{ passive: false }` is present)
2. ✅ Task 2 — Action column color + zebra striping fix (append CSS, bump cache)
3. ✅ Task 3 — If zebra striping looks broken, either: keep the CSS above OR change `rowAlternationEnabled: false` in index.php
4. Task 4 — Verify column resizing works (V9 storage key + widget mode default)
5. ✅ Task 5 — Export button already uses `icon: "xlsxfile"` (correct)
6. ⚠️ Task 6 — Set up `db_config.php`, `.htaccess`, change DB password (CRITICAL for live)
7. Optional Task 7 — Convert to Bootstrap if desired (see convert-bootstrap.SKILL.md)

**When all are done:** Generate `PROJECT_NOTES.md` using the documentation template above.

---

## 📬 Need Help?

If you're a human reading this (not a CLI agent): 
- Open your project folder
- Copy the relevant code snippets into the correct files (index.php, style.css)
- Hard-refresh (Ctrl+F5) after each change
- Test: Add → Edit → Delete → Search → Export → Touchpad scroll → Column resize
- If something breaks, undo the last change and verify

**Most common issue:** Forgetting to bump the `storageKey` from V6/V7 to V9 — old saved column widths override new defaults. Always clear localStorage if resizing stops working:
```js
localStorage.removeItem("categoryGridResizeMode");
localStorage.removeItem("categoryGridStateV9");
location.reload();
```

---

*File generated: SESSION_DATE*  
*Project: Category Management DevExtreme PHP*  
*Based on session with user Chhourn CryMunyvann, learning jQuery + DevExtreme + PHP*