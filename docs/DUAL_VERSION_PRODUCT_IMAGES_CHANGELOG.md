# Dual-Version Product Images (Thumb + Full WebP) — Change Log
This document lists exactly what was implemented to generate and expose dual WebP image versions (thumb/full) for product images.

## Goal
- **Listings (Shop/Home/etc.)** use **thumb** images for extreme performance.
- **Product details** use **full** images for high fidelity.
- Storage is clean: deleting an image deletes both versions.

---

## Database changes (Migrations)
### 1) `product_images` table
**File:** `database/migrations/2026_02_18_000010_add_thumb_and_full_paths_to_product_images_table.php`
- Added nullable columns:
  - `thumb_path` (string, nullable)
  - `full_path` (string, nullable)
- Backfilled:
  - `full_path = path` for existing rows (backward compatibility)

### 2) `products` table (featured image)
**File:** `database/migrations/2026_02_18_000011_add_featured_image_thumb_and_full_to_products_table.php`
- Added nullable columns:
  - `featured_image_thumb` (string, nullable)
  - `featured_image_full` (string, nullable)
- Backfilled:
  - `featured_image_full = featured_image` for existing rows

### Migration execution
- Ran: `php artisan migrate`
- Output confirms both migrations executed successfully.

---

## Storage structure (public disk)
Generated WebP images are stored under:
- `storage/app/public/products/thumbs/{basename}.webp`
- `storage/app/public/products/full/{basename}.webp`

---

## Image processing (Intervention Image)
### `ImageService`
**File:** `app/Services/ImageService.php`
Added/updated methods to become the single source of truth (and to reconcile Admin controller calls):
- `generateProductWebpVersions(UploadedFile $file, string $baseName)`
  - thumb: max width 600px, WebP quality **80**
  - full: original dimensions, WebP quality **98**
  - returns `{ thumb_path, full_path }`
- `uploadProductImages(Product $product, array $files)`
  - creates `ProductImage` records with `thumb_path/full_path` and keeps `path` pointing to the full path for backward compatibility
- `deleteImage(ProductImage $image)`
- `setPrimaryImage(ProductImage $image)`
- `reorderImages(Product $product, array $imageIds)`
- `bulkDeleteImages(Product $product, array $imageIds)`
- `updateImageDetails(ProductImage $image, array $data)`
  - implemented because `Admin/ImageController` calls it
- `generateThumbnails(Product $product)`
  - rebuilds missing thumbs from existing full image when possible
- `optimizeImages(Product $product)`
  - only optimizes **legacy** non-webp paths; skips `products/thumbs/*` and `products/full/*` to avoid double-compression

---

## Model changes
### 1) `ProductImage` model
**File:** `app/Models/ProductImage.php`
- Updated `$fillable` to include:
  - `thumb_path`, `full_path`
- Added accessors:
  - `thumb_url` (fallbacks: thumb_path → full_path → path → `/images/no-image.png`)
  - `full_url` (fallbacks: full_path → path → thumb_url)
  - `image_urls` returns `{ thumb, full }`
  - `url` now returns `full_url` (keeps legacy field working)
- Updated deleting hook:
  - deletes `thumb_path`, `full_path`, and legacy `path`

### 2) `Product` model
**File:** `app/Models/Product.php`
- Updated `$fillable` to include:
  - `featured_image_thumb`, `featured_image_full`
- Added accessors:
  - `featured_image_thumb_url`
  - `featured_image_full_url`
  - `featured_image_urls` returns `{ thumb, full }`
  - `primary_image_thumb_url`
- Updated `featured_image_url` and `primary_image_url` logic to prefer the new `*_full_url` and fall back safely.

---

## Dashboard upload pipeline
### `ProductService`
**File:** `app/Services/ProductService.php`
- Injected `ImageService` via constructor.
- **Create product:** featured image now uses `generateProductWebpVersions` and stores:
  - `featured_image_thumb`, `featured_image_full`
  - and sets legacy `featured_image = featured_image_full`
- **Update product:** deletes old featured thumb/full/path then regenerates thumb/full.
- Gallery images:
  - replaced manual `storeAs('products/{id}', ...)` with `ImageService::uploadProductImages(...)`
- Deletion of gallery images:
  - now relies on `ProductImage` model delete hook (removes thumb/full/path).

---

## API output changes (sync)
### 1) Product images in product details
**File:** `app/Http/Resources/ProductImageResource.php`
- Added `image_urls` to each image object:
  - `{ thumb: "...", full: "..." }`
- Kept legacy `url`.

### 2) Featured image URLs in product details
**File:** `app/Http/Resources/ProductResource.php`
- Added `featured_image_urls`:
  - `{ thumb: "...", full: "..." }`
- Updated the featured image entry in `images[]` to include `image_urls`.

### 3) Listings use thumb for performance
Updated to prefer `primary_image_thumb_url ?? primary_image_url`:
- **Shop grid**
  - `app/Http/Controllers/Api/ShopController.php` (`products()` mapping `image`)
- **Home page sections**
  - `app/Http/Controllers/Api/HomeController.php` (`mapProductForHome/Featured/OnSale`)
- **Related products**
  - `app/Http/Controllers/Api/ProductController.php` (`related()` mapping)
- **Wishlist**
  - `app/Http/Controllers/Api/WishlistController.php` (`index()` mapping)
- **Cart**
  - `app/Http/Controllers/Api/CartController.php` (`index()` mapping)
- **Reviews**
  - `app/Http/Controllers/Api/ReviewController.php` (`index()` mapping `productImage`)

---

## Notes / Backward compatibility
- Legacy fields are preserved:
  - `products.featured_image` still points to the full WebP.
  - `product_images.path` still points to the full WebP.
- Fallbacks ensure:
  - if `full` missing → fall back to `thumb`
  - if both missing → fall back to `/images/no-image.png`

---

## What you should do next (quick test)
- Upload a product featured image + a couple gallery images from Dashboard.
- Verify generated files exist:
  - `storage/app/public/products/thumbs/*.webp`
  - `storage/app/public/products/full/*.webp`
- Verify API:
  - Shop listings return `image` that is a thumb.
  - Product details return `images[].image_urls.thumb/full`.
- Delete an image from dashboard → both versions should disappear from storage.
