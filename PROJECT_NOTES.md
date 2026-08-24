# Project Notes: DevExtreme DataGrid Category Management

A web-based Category Management dashboard utilizing a jQuery frontend powered by the DevExtreme DataGrid widget, coupled with a PHP backend using prepared SQL statements via MySQLi.

---

## 1. Project Purpose & Features
This application serves as an administrative CRUD (Create, Read, Update, Delete) panel for managing product categories. Key features include:

* **Interactive Data Table (DevExtreme DataGrid):** Provides robust client-side interactions, sorting, custom formats, time-ago cells, and column resizing.
* **Column Resizing:** Custom setup to support resizing columns dynamically, including the last column (Action column) via `"widget"` resize mode.
* **UI Preferences Persistence:** Uses browser `localStorage` under `categoryGridStateV7` to remember column configurations and preferences.
* **Touchpad Horizontal Scrolling:** Converts vertical scroll/wheel touchpad gestures over the grid container to horizontal scroll actions, allowing users to scroll left and right smoothly.
* **Custom Search Input:** Custom search bar wired to the grid using `.searchByText()`.
* **Custom Modal Dialogues:** Add actions open the DevExtreme default popup form while also integrating standard triggers.
* **Excel Export:** Multi-option excel exporter utilizing ExcelJS and FileSaver to either download the current visible page or all categories.

---

## 2. File Map & Architecture

### **Frontend & Controller Routing**
* [index.php](file:///D:/xammp/htdocs/Inventory/index.php): The primary application page. It renders the HTML framework, registers stylesheet and script assets (jQuery, DevExtreme, ExcelJS, FileSaver, and custom style.css), embeds custom grid components, and hosts the JavaScript logic initiating the DevExtreme CustomStore backend connections. It also serves as the read endpoint (`index.php?action=read`).

### **Backend PHP API Endpoints (REST-like Actions)**
* [database.php](file:///D:/xammp/htdocs/Inventory/database.php): Connects to the database and handles migrations. Loads dynamic credentials from `db_config.php`.
* [db_config.php](file:///D:/xammp/htdocs/Inventory/db_config.php) *(Uncommitted/Gitignored)*: Contains credential configurations for local and live environments.
* [create.php](file:///D:/xammp/htdocs/Inventory/create.php): Endpoint to insert new categories. Validates input uniqueness and enforces constraints using prepared statements.
* [edit.php](file:///D:/xammp/htdocs/Inventory/edit.php): Endpoint to update a category name or code by `id` query parameter. Validates changes against unique constraints.
* [delete.php](file:///D:/xammp/htdocs/Inventory/delete.php): Endpoint to delete a category by `id`.
* [check_active_ini.php](file:///D:/xammp/htdocs/Inventory/check_active_ini.php) *(Diagnostic)*: Outputs PHP configuration information, loaded php.ini location, and whether the `mysqli` extension is enabled.

### **Styles**
* [style.css](file:///D:/xammp/htdocs/Inventory/style.css): Custom CSS overlaying the DevExtreme theme. It customizes colors to create a cohesive dark theme and centers action buttons inside cells.

---

## 3. Database Schema & Migration System
The database setup initializes itself automatically upon launching [database.php](file:///D:/xammp/htdocs/Inventory/database.php).

### **Category Table Schema**
```sql
CREATE TABLE IF NOT EXISTS `category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_code` varchar(50) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `lastupdate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `category_code` (`category_code`),
  UNIQUE KEY `category_name` (`category_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

### **Auto-Migrations in database.php**
1. **Column Verification:** Checks for the presence of the `lastupdate` column and appends it dynamically if missing.
2. **Table Migration:** Renames older `categories` table to the singular `category` table if it is detected. It automatically merges records if both exist, dropping the old reference afterward.
3. **Unique Constraints & Cleaning:** Identifies duplicates in the `category_name` field, removes duplicates by preserving only the oldest record, and applies a unique key to prevent subsequent conflicts.

---

## 4. How to Run Locally (XAMPP Setup)

1. **Verify PHP and Extension Status:** Open `check_active_ini.php` in your browser. Ensure the `mysqli` extension is listed as loaded (`YES`).
2. **Launch Database:** Turn on MySQL in XAMPP. The project connects to MySQL port `3307` locally. Adjust the `$db_port` inside [db_config.php](file:///D:/xammp/htdocs/Inventory/db_config.php) if your XAMPP installation uses `3306`.
3. **Configure Database Schema:** Access PhpMyAdmin or run `database.php` via XAMPP. Opening the script creates the `inventory` database (if configured) and configures the `category` schema auto-migration rules.
4. **Access Dashboard:** Navigate to `http://localhost/Inventory/index.php` (or your corresponding virtual host endpoint) in your web browser.

---

## 5. DevExtreme Grid Setup Details

* **Column Resizing Mode:** Configured with `columnResizingMode: "widget"`, allowing header grids to push the main grid frame outward instead of taking space from adjacent columns. This enables the last Action column to resize correctly.
* **Search Integration:** Custom search field hooks into `searchInput` on input changes:
  ```js
  grid.searchByText($(this).val());
  ```
* **Excel Exporting Integration:** Uses the ExcelJS framework. The "Export Current Page" feature is executed by programmatically instantiating a temporary hidden DataGrid populated solely by the visible rows to execute a focused print.
* **State Storage:** Saved state parameters use `categoryGridStateV7` in localStorage. Old `V6` properties are ignored to prevent layout bugs.
* **Touchpad Horizontal Scrolling:** Registered a native `wheel` listener with `{ passive: false }` on the `.table-wrapper` container in [index.php](file:///D:/xammp/htdocs/Inventory/index.php). It dynamically detects horizontal and vertical scroll inputs and routes the scroll motion directly (`target.scrollLeft += delta`) to either the DevExtreme inner container (`.dx-scrollable-container`) or the parent wrapper, optimized for Windows touchpads (e.g. ASUS TUF laptops) to prevent lockups.

---

## 6. Known Limitations
* **Paging Behavior:** Handled in client memory (`loadMode: "raw"` in DevExtreme CustomStore configuration). Large datasets should ideally shift to a custom store using custom backend servers for server-side paging.
* **DevExtreme License Banner:** The CSS hiding rule has been commented out inside [style.css](file:///D:/xammp/htdocs/Inventory/style.css) for licensing compliance. DevExtreme's free non-commercial license terms specify that the non-commercial banner should remain visible in public production environments.
