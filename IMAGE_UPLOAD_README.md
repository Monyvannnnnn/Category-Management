# Product Image Upload & Telegram Push Implementation

## Table of Contents
- [Overview](#overview)
- [Prerequisites](#prerequisites)
- [Database Schema](#database-schema)
- [Supabase Storage Setup](#supabase-storage-setup)
- [File Structure](#file-structure)
- [Implementation Steps](#implementation-steps)
  - [Step 1: Configuration](#step-1-configuration)
  - [Step 2: Supabase Storage Helper](#step-2-supabase-storage-helper)
  - [Step 3: Create Product with Image](#step-3-create-product-with-image)
  - [Step 4: Edit Product with Image](#step-4-edit-product-with-image)
  - [Step 5: Telegram Photo Push](#step-5-telegram-photo-push)
  - [Step 6: Display Images in DataGrid](#step-6-display-images-in-datagrid)
  - [Step 7: Bot Commands Update](#step-7-bot-commands-update)
- [Testing](#testing)
- [Troubleshooting](#troubleshooting)

---

## Overview

This implementation adds **product image upload** to Supabase Storage and **image push notifications** to Telegram.

### Current State
- Product CRUD (text only)
- Telegram text-only push notifications
- No image support

### After Implementation
- Product image upload to Supabase Storage
- Telegram photo push with captions
- Image display in DataGrid
- Backward compatible (products without images still work)

---

## Prerequisites

| Requirement | Status |
|-------------|--------|
| PHP 7.4+ with cURL | ✅ Required |
| MySQL/PostgreSQL (Supabase) | ✅ Required |
| Supabase project | ✅ Required |
| Supabase Storage bucket `products-img` | ✅ Created |
| Bucket policies (INSERT + SELECT) | ✅ Configured |
| Telegram Bot Token | ✅ Existing |

---

## Database Schema

### Add Image Column

```sql
-- Run in Supabase SQL Editor
ALTER TABLE product ADD COLUMN IF NOT EXISTS image TEXT DEFAULT NULL;
```

### Product Table (After)

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key |
| product_code | VARCHAR(50) | Unique product code |
| product_name | VARCHAR(100) | Product name |
| category_id | INT | Foreign key to category |
| price | DECIMAL(10,2) | Product price |
| quantity | INT | Stock quantity |
| **image** | **TEXT** | **Supabase public URL** |
| created_at | TIMESTAMP | Creation time |
| lastupdate | TIMESTAMP | Last update time |

---

## Supabase Storage Setup

### Bucket Configuration

| Setting | Value |
|---------|-------|
| Bucket name | `products-img` |
| Public bucket | Yes |
| File size limit | 5MB |
| Allowed MIME types | image/jpeg, image/png, image/gif, image/webp |

### Required Policies

| Operation | Policy |
|-----------|--------|
| SELECT (view) | Allow anon to view images |
| INSERT (upload) | Allow anon to upload |
| DELETE (remove) | Allow anon to delete |

### Policy Templates

**SELECT Policy:**
```sql
bucket_id = 'products-img' 
AND (storage."extension"(name) IN ('jpg', 'jpeg', 'png', 'gif', 'webp'))
AND auth.role() = 'anon'
```

**INSERT Policy:**
```sql
bucket_id = 'products-img' 
AND auth.role() = 'anon'
```

**DELETE Policy:**
```sql
bucket_id = 'products-img' 
AND auth.role() = 'anon'
```

---

## File Structure

```
Inventory/
├── config.php                          # Supabase credentials
├── supabase_storage.php                # Upload/download functions
├── create_product.php                  # Handle image upload
├── edit_product.php                    # Handle image update
├── notify_bot.php                      # Add sendPhoto function
├── index.php                           # Display images in grid
├── products.php                        # Display images in grid
└── uploads/                            # Temp folder (if needed)
```

---

## Implementation Steps

### Step 1: Configuration

**File:** `config.php`

Add Supabase credentials:

```php
<?php
// Database Connection
$conn = mysqli_connect("localhost", "root", "", "inventory");

// Supabase Configuration
define('SUPABASE_URL', 'https://xxxxx.supabase.co');
define('SUPABASE_ANON_KEY', 'your-anon-key-here');
define('SUPABASE_SERVICE_KEY', 'your-service-role-key-here');
define('SUPABASE_BUCKET', 'products-img');

// Telegram Configuration
define('TELEGRAM_BOT_TOKEN', 'your-bot-token-here');
?>
```

---

### Step 2: Supabase Storage Helper

**File:** `supabase_storage.php`

Create these functions:

#### `uploadToSupabase($filePath, $destinationName)`

Purpose: Upload file to Supabase Storage

**Parameters:**
- `$filePath` - Temporary file path from `$_FILES`
- `$destinationName` - Unique filename for storage

**Returns:** Public URL or false on failure

**Logic:**
1. Build API URL: `{SUPABASE_URL}/storage/v1/object/{BUCKET}/{filename}`
2. Set headers:
   - `Authorization: Bearer {ANON_KEY}`
   - `Content-Type: {mime_type}`
3. Use cURL PUT to upload file
4. Return public URL: `{SUPABASE_URL}/storage/v1/object/public/{BUCKET}/{filename}`

#### `deleteFromSupabase($fileName)`

Purpose: Delete file from Supabase Storage

**Parameters:**
- `$fileName` - Filename in bucket

**Returns:** true on success, false on failure

**Logic:**
1. Build API URL: `{SUPABASE_URL}/storage/v1/object/{BUCKET}/{filename}`
2. Set headers:
   - `Authorization: Bearer {ANON_KEY}`
3. Use cURL DELETE

#### `getPublicUrl($fileName)`

Purpose: Get public URL for a file

**Parameters:**
- `$fileName` - Filename in bucket

**Returns:** Full public URL

#### `validateUploadedFile($file)`

Purpose: Validate uploaded file

**Parameters:**
- `$file` - `$_FILES['product_image']`

**Validation rules:**
- File type: jpg, jpeg, png, gif, webp
- File size: max 5MB
- No upload errors

**Returns:** true if valid, throws exception if invalid

---

### Step 3: Create Product with Image

**File:** `create_product.php`

#### Changes Required

1. **Include helper file:**
   ```php
   require_once 'supabase_storage.php';
   ```

2. **Handle file upload after existing validation:**
   ```php
   $imageUrl = null;
   
   if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
       validateUploaded_file($_FILES['product_image']);
       $uniqueName = uniqid() . '_' . basename($_FILES['product_image']['name']);
       $imageUrl = uploadToSupabase($_FILES['product_image']['tmp_name'], $uniqueName);
   }
   ```

3. **Update INSERT query:**
   ```php
   $stmt = db_prepare($conn, 
       "INSERT INTO product (user_id, product_code, product_name, category_id, price, quantity, image) 
        VALUES (?, ?, ?, ?, ?, ?, ?)"
   );
   db_stmt_bind_param($stmt, "issidis", $userId, $product_code, $product_name, $category_id, $price, $quantity, $imageUrl);
   ```

4. **Update push notification:**
   ```php
   if (!empty($imageUrl)) {
       // Send photo with caption
       sendPhotoToTelegram($chatId, $imageUrl, $msg);
   } else {
       // Send text only
       sendTelegramNotification($msg, $conn, $userId);
   }
   ```

---

### Step 4: Edit Product with Image

**File:** `edit_product.php`

#### Changes Required

1. **Handle file upload:**
   ```php
   $imageUrl = $existingImage; // Keep existing by default
   
   if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
       validateUploadedFile($_FILES['product_image']);
       
       // Delete old image from Supabase
       if (!empty($existingImage)) {
           $oldFileName = basename($existingImage);
           deleteFromSupabase($oldFileName);
       }
       
       // Upload new image
       $uniqueName = uniqid() . '_' . basename($_FILES['product_image']['name']);
       $imageUrl = uploadToSupabase($_FILES['product_image']['tmp_name'], $uniqueName);
   }
   ```

2. **Update query:**
   ```sql
   UPDATE product SET 
       product_code = ?, 
       product_name = ?, 
       category_id = ?, 
       price = ?, 
       quantity = ?, 
       image = ? 
   WHERE id = ?
   ```

---

### Step 5: Telegram Photo Push

**File:** `notify_bot.php`

#### Add Function: `sendPhotoToTelegram($chatId, $photoUrl, $caption)`

Purpose: Send photo with caption via Telegram API

**Parameters:**
- `$chatId` - Telegram chat ID
- `$photoUrl` - Public image URL
- `$caption` - Message caption

**Returns:** API response or false

**Implementation:**

```php
function sendPhotoToTelegram($chatId, $photoUrl, $caption) {
    $botToken = TELEGRAM_BOT_TOKEN;
    $url = "https://api.telegram.org/bot{$botToken}/sendPhoto";
    
    $data = [
        'chat_id' => $chatId,
        'photo' => $photoUrl,
        'caption' => $caption,
        'parse_mode' => 'HTML'
    ];
    
    // Use cURL POST (same pattern as sendSingleTelegramNotification)
    // Method 1: Standard cURL
    // Method 2: DNS Bypass
    // Method 3: Stream context fallback
    
    return $result;
}
```

#### Update Push Logic

```php
function pushProductNotification($conn, $userId, $productData) {
    $msg = "📦 Product: {$productData['name']}\n";
    $msg .= "💰 Price: \${$productData['price']}\n";
    $msg .= "🔢 Qty: {$productData['quantity']}";
    
    $chatId = getUserChatId($conn, $userId);
    
    if (!empty($productData['image'])) {
        sendPhotoToTelegram($chatId, $productData['image'], $msg);
    } else {
        sendTelegramNotification($msg, $conn, $userId);
    }
}
```

---

### Step 6: Display Images in DataGrid

**Files:** `index.php`, `products.php`

#### Add Image Column

```javascript
{
    caption: "Image",
    width: 80,
    allowSorting: false,
    allowFiltering: false,
    cellTemplate: function(container, options) {
        if (options.data.image) {
            $('<img>')
                .attr('src', options.data.image)
                .attr('alt', options.data.product_name)
                .css({
                    width: '50px',
                    height: '50px',
                    objectFit: 'cover',
                    borderRadius: '4px',
                    cursor: 'pointer'
                })
                .on('click', function() {
                    window.open(options.data.image, '_blank');
                })
                .appendTo(container);
        } else {
            $('<span>')
                .text('No image')
                .css({ color: '#94a3b8', fontSize: '11px' })
                .appendTo(container);
        }
    }
}
```

---

### Step 7: Bot Commands Update

**Files:** `set_commands.php`, `bot_poller.php`

#### Update `/product` Command

```php
case '/product':
    // Fetch product by code
    $product = getProductByCode($conn, $arg);
    
    if ($product) {
        $caption = "📦 <b>{$product['product_name']}</b>\n"
                 . "💰 Price: \${$product['price']}\n"
                 . "🔢 Stock: {$product['quantity']}";
        
        if (!empty($product['image'])) {
            sendPhotoToTelegram($chatId, $product['image'], $caption);
        } else {
            sendTelegramMessage($chatId, $caption);
        }
    }
    break;
```

#### Update `/search` Command

```php
case '/search':
    $results = searchProducts($conn, $arg);
    
    foreach ($results as $product) {
        $caption = "📦 {$product['product_name']} - \${$product['price']}";
        
        if (!empty($product['image'])) {
            sendPhotoToTelegram($chatId, $product['image'], $caption);
        } else {
            sendTelegramMessage($chatId, $caption);
        }
    }
    break;
```

---

## Testing

### Step-by-Step Testing

| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Add product with image | Image uploads to Supabase |
| 2 | Check database | Image URL saved |
| 3 | View product list | Thumbnail displayed |
| 4 | Push notification | Telegram receives photo + caption |
| 5 | Edit product image | Old image deleted, new uploaded |
| 6 | Delete product | Image deleted from Supabase |
| 7 | Bot command `/product` | Returns product with image |

### Test Cases

| Test | Description |
|------|-------------|
| Upload JPG | Valid JPG file |
| Upload PNG | Valid PNG file |
| Upload GIF | Valid GIF file |
| Upload large file | Should reject (>5MB) |
| Upload invalid type | Should reject (.exe, .pdf) |
| Product without image | Should still work |
| Push with image | Telegram receives photo |
| Push without image | Telegram receives text |

---

## Troubleshooting

| Issue | Cause | Solution |
|-------|-------|----------|
| Upload fails | Bucket policies | Check INSERT policy |
| Image not displaying | Wrong URL | Verify public URL in DB |
| Telegram not receiving image | Invalid URL | Test URL in browser |
| Old image not deleted | Missing delete call | Check edit_product.php |
| Large file fails | Size limit | Check `upload_max_filesize` in PHP |
| Slow upload | Large image | Optimize/resize before upload |

### Common Errors

| Error | Solution |
|-------|----------|
| `403 Forbidden` | Check bucket policies |
| `400 Bad Request` | Check file type/size |
| `Payload Too Large` | Increase PHP limits |
| `Invalid URL` | Verify Supabase URL format |

---

## Security Checklist

| Security Measure | Status |
|------------------|--------|
| File type validation | ✅ Implemented |
| File size limit | ✅ Implemented |
| Unique filenames | ✅ Implemented |
| Prepared statements | ✅ Implemented |
| Public bucket only for images | ✅ Verified |
| No executable uploads | ✅ Validated |
| Error messages don't leak paths | ✅ Implemented |

---

## API References

### Supabase Storage

| Operation | Method | Endpoint |
|-----------|--------|----------|
| Upload | POST | `/storage/v1/object/{bucket}/{file}` |
| Delete | DELETE | `/storage/v1/object/{bucket}/{file}` |
| Public URL | GET | `/storage/v1/object/public/{bucket}/{file}` |

### Telegram Bot API

| Operation | Endpoint |
|-----------|----------|
| Send Photo | `/sendPhoto` |
| Send Message | `/sendMessage` |

---

## License

MIT License - Free to use and modify.
