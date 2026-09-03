# Inventory & Category Management System — Data & Features Guide

Welcome to the **Inventory & Category Management System**. This document provides a detailed breakdown of all available features for searching, filtering, sorting, monitoring, exporting, and managing your inventory data.

---

## 📋 Overview of Data Management Modules

The project consists of two core data management interfaces powered by **DevExtreme Interactive DataGrid** with live AJAX backend synchronization:

1. **Products Management (`products.php`)**: Manages individual inventory products, pricing, stock quantities, creation timestamps, and categories.
2. **Category Management (`index.php`)**: Manages item categories, category codes, and creation history.

---

## 🔍 How Users Can Find & Check the Data They Want

### 1. Global Live Keyword Search
- **Location:** Search bar at the top of the grid (`#searchInput`).
- **How to use:** Type any keyword (Product Name, Product Code, Category, Price, or Date) into the search box.
- **Behavior:** The DataGrid filters rows instantly as you type across all visible data fields without reloading the page.

---

### 2. Multi-Column Sorting
- **Header Click:** Click any column header (e.g., *Product Name*, *Price*, *Quantity*, *Created Date*) to toggle between **Ascending (A-Z, 0-9)** and **Descending (Z-A, 9-0)** order.
- **Stock Sorting:** Click on the **Quantity** column to sort from lowest stock to highest stock to immediately identify low-stock items.
- **Price Sorting:** Click on the **Price** column to sort products by cost.
- **Smart Time Sorting:** Timestamps and created dates use custom sorting algorithms to ensure proper chronological order down to the exact second.

---

### 3. Custom Date & Time Views ("View Created As")
You can customize how date and time information is formatted and sorted:
- **How to use:** Right-click on the **Date Created** column header.
- **Available Views:**
  - 📅 **Date:** Displays formatted date (`dd/MM/yyyy`).
  - ⏰ **Time:** Displays time of day (`HH:mm:ss`).
  - 🗓️ **Month:** Groups/formats by month and year (`Month YYYY`).
  - 📆 **Year:** Displays year only (`YYYY`).
  - 📅 **Day of Week:** Displays weekday name (`Monday`, `Tuesday`, etc.).
  - 🕒 **Date & Time:** Displays full date and time stamp.
  - ⏱️ **Relative:** Displays human-readable relative time (e.g., *"5 minutes ago"*, *"2 hours ago"*).

---

### 4. Field Chooser (Show / Hide Columns)
- **Location:** Click the **"Columns"** button in the grid toolbar.
- **How to use:**
  - A panel titled **Field Chooser** opens on the right.
  - Check or uncheck columns to dynamically display only the columns you need.
  - Use the built-in search inside the Field Chooser panel to locate specific column names quickly.

---

### 5. Column Freezing (Sticky Columns)
- **How to use:** Right-click on any column header.
- **Options:**
  - 🔒 **Freeze Left:** Locks the column to the left side of the grid while scrolling horizontally.
  - 🔒 **Freeze Right:** Locks the column to the right side (default for the **Action** column).
  - 🔓 **Unfreeze:** Restores normal scrolling for the column.

---

### 6. Stock Level Monitoring & Status Badges
- **Product Code Badges:** Product codes are rendered with color-coded badges (`badge-purple`, `badge-green`, `badge-orange`, `badge-pink`, `badge-blue`) for quick visual classification.
- **Last Updated Tracker:** Shows the exact timestamp when a row was last updated along with an automatic relative time tracker (e.g., `26/10/2023 14:30 (10 mins ago)`).

---

### 7. Pagination & Grid Display Controls
- **Items Per Page Selector:** Choose between **5**, **10**, or **20** rows per page using the pager dropdown at the bottom of the grid.
- **Page Navigation:** Full page controls (First, Previous, Page Numbers, Next, Last).
- **Responsive Auto-Fit:** The grid automatically adjusts its height (`fitGridHeight`) to fit your browser window, keeping the pagination controls visible at all times.
- **Horizontal Scroll Support:** Smooth touchpad and mouse wheel horizontal scrolling support for viewing wide data tables.

---

## 📤 Data Export & Reporting Features

### 1. Excel Export (`.xlsx`)
- Export your filtered dataset directly to Microsoft Excel using **ExcelJS** and **FileSaver.js**.
- Retains column formatting, numeric values, and layout structures.

### 2. Custom PDF Export Engine
- Powered by `jsPDF`, `jsPDF AutoTable`, and `html2canvas`.
- **🇰🇭 Khmer Font Support:** Fully supports Khmer Unicode rendering (`Khmer OS Siemreap` font integrated via webfont base64 encoding).
- **Customizable PDF Options:**
  - **Orientation:** Portrait (`P`) or Landscape (`L`).
  - **Paper Size:** A4, A3, or Letter.
  - **Scope:** Export current active page or all data rows.
- **Dedicated Exporter Page:** `export_pdf.php` provides a clean, printable PDF rendering pipeline.

---

## 📲 Telegram Bot & Instant Alerts Integration

The system includes built-in Telegram Bot integration for instant notifications:

### 1. Single Product Push to Telegram
- Click the Telegram icon (<i class="fa-brands fa-telegram"></i>) inside the **Action** column of any product row.
- Instantly sends that product's code, name, price, quantity, and category to your Telegram channel/chat.

### 2. Manual Telegram Push Modal
- Click the **"Manual Push"** button in the header toolbar.
- Allows sending custom bulk alerts or category summaries to Telegram.

### 3. Automated Network Fallback
- `notify_bot.php` includes standard cURL and a **DNS IP Resolution Bypass** (for hosts like InfinityFree or local environments with DNS restrictions) to ensure 100% reliable alert delivery.

---

## 🛠️ Data Operations (CRUD Summary)

| Operation | Action | Description |
| :--- | :--- | :--- |
| **Create** | `addRow()` / Add Button | Opens dark-themed popup form modal to insert new products/categories. |
| **Read** | AJAX GET (`action=read`) | Dynamically fetches products and categories from MySQL database. |
| **Update** | `editRow()` / Edit Icon | Edit row values in-place with instant validation rules. |
| **Delete** | `deleteRow()` / Trash Icon | Deletes entry with instant AJAX database sync. |

---

## 📁 Technical File Index

- [`products.php`](file:///d:/xammp/htdocs/Inventory/products.php) — Product list, search/sort/filter grid, stock management, PDF/Excel export.
- [`index.php`](file:///d:/xammp/htdocs/Inventory/index.php) — Category management grid.
- [`export_pdf.php`](file:///d:/xammp/htdocs/Inventory/export_pdf.php) — Server-side visible PDF rendering table.
- [`notify_bot.php`](file:///d:/xammp/htdocs/Inventory/notify_bot.php) — Telegram Bot API notification engine.
- [`manual_push.php`](file:///d:/xammp/htdocs/Inventory/manual_push.php) — Manual Telegram alert trigger endpoint.
- [`database.php`](file:///d:/xammp/htdocs/Inventory/database.php) / [`db_config.php`](file:///d:/xammp/htdocs/Inventory/db_config.php) — MySQL database connection.
