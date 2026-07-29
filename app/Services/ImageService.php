<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductImage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImageService
{
    protected $disk;
    protected $quality;
    protected $sizes;
    protected $webpEnabled;
    protected $manager;

    protected int $siteWebpQuality;

    public function __construct()
    {
        $this->disk = config('image.disk', 'public');
        $this->quality = config('image.quality', 85);
        $this->sizes = config('image.sizes');
        $this->webpEnabled = config('image.webp.enabled', true);

        $this->siteWebpQuality = 85;
        
        // Don't initialize manager in constructor (lazy loading)
        // It will be initialized when first needed
        $this->manager = null;
    }

    /**
     * Upload image and return WebP-only paths for requested sizes.
     * This is intended for site-wide assets (banners/hero/page settings), not product gallery.
     */
    public function uploadSiteImageWebpOnly(UploadedFile $file, string $folder, array $sizesToGenerate = null): array
    {
        $sizesToGenerate = $sizesToGenerate ?? ['original', 'large'];
        $paths = [];

        $filename = uniqid() . '_' . time();
        $manager = $this->getManager();

        foreach ($sizesToGenerate as $sizeName) {
            if (!isset($this->sizes[$sizeName])) {
                continue;
            }

            $sizeConfig = $this->sizes[$sizeName];
            $image = $manager->read($file);

            $this->applyResizeNoUpscale($image, $sizeConfig['width'] ?? null, $sizeConfig['height'] ?? null);

            $webpEncoded = $image->encode(
                new \Intervention\Image\Encoders\WebpEncoder(quality: $this->siteWebpQuality)
            );

            $webpPath = "$folder/{$sizeName}/{$filename}.webp";
            Storage::disk($this->disk)->put($webpPath, $webpEncoded);
            $paths["{$sizeName}_webp"] = $webpPath;
        }

        return $paths;
    }

    /**
     * Convert an existing stored image path to WebP-only for given sizes.
     * Returns WebP paths keyed like: original_webp, banner_webp, large_webp ...
     */
    public function convertExistingToSiteWebpOnly(string $existingPath, string $folder, array $sizesToGenerate = null): ?array
    {
        if ($existingPath === '') {
            return null;
        }

        if (!Storage::disk($this->disk)->exists($existingPath)) {
            return null;
        }

        $sizesToGenerate = $sizesToGenerate ?? ['original', 'large'];
        $paths = [];

        $sourceFullPath = Storage::disk($this->disk)->path($existingPath);
        $filename = pathinfo($existingPath, PATHINFO_FILENAME);
        $manager = $this->getManager();

        foreach ($sizesToGenerate as $sizeName) {
            if (!isset($this->sizes[$sizeName])) {
                continue;
            }

            $sizeConfig = $this->sizes[$sizeName];
            $image = $manager->read($sourceFullPath);
            $this->applyResizeNoUpscale($image, $sizeConfig['width'] ?? null, $sizeConfig['height'] ?? null);

            $webpEncoded = $image->encode(
                new \Intervention\Image\Encoders\WebpEncoder(quality: $this->siteWebpQuality)
            );

            $webpPath = "$folder/{$sizeName}/{$filename}.webp";
            Storage::disk($this->disk)->put($webpPath, $webpEncoded);
            $paths["{$sizeName}_webp"] = $webpPath;
        }

        return $paths;
    }

    protected function applyResizeNoUpscale($image, ?int $width, ?int $height): void
    {
        if (!$width && !$height) {
            return;
        }

        $currentW = $image->width();
        $currentH = $image->height();

        $targetW = $width ?? (int) round($currentW * (($height ?? $currentH) / max(1, $currentH)));
        $targetH = $height ?? (int) round($currentH * (($width ?? $currentW) / max(1, $currentW)));

        if ($targetW >= $currentW && $targetH >= $currentH) {
            return;
        }

        $image->scale($width, $height);
    }

    /**
     * Initialize Image Manager (lazy loading)
     */
    protected function getManager()
    {
        if ($this->manager !== null) {
            return $this->manager;
        }

        // Try GD driver first
        if (extension_loaded('gd')) {
            $this->manager = new ImageManager(new Driver());
            return $this->manager;
        } 
        
        // Fallback to Imagick if available
        if (extension_loaded('imagick')) {
            $this->manager = new ImageManager(new \Intervention\Image\Drivers\Imagick\Driver());
            return $this->manager;
        }
        
        // If neither is available, throw exception
        throw new \Exception(
            'Neither GD nor Imagick extension is available. ' .
            'Please enable one of them in your php.ini file. ' .
            'For XAMPP: Open C:\xampp\php\php.ini and remove the semicolon before "extension=gd"'
        );
    }

    /**
     * Upload and compress image with multiple sizes
     * 
     * @param UploadedFile $file
     * @param string $folder
     * @param array|null $sizesToGenerate
     * @return array
     */
    public function upload(UploadedFile $file, string $folder, array $sizesToGenerate = null): array
    {
        $sizesToGenerate = $sizesToGenerate ?? ['original', 'medium', 'thumbnail'];
        $paths = [];

        // Generate unique filename
        $filename = uniqid() . '_' . time();
        $extension = $file->getClientOriginalExtension();

        foreach ($sizesToGenerate as $sizeName) {
            if (!isset($this->sizes[$sizeName])) {
                continue;
            }

            $sizeConfig = $this->sizes[$sizeName];

            // Read and process image
            $image = $this->getManager()->read($file);

            // Apply resizing if configured
            if ($sizeConfig['width'] || $sizeConfig['height']) {
                if ($sizeConfig['width'] && $sizeConfig['height']) {
                    // Both dimensions specified - fit to exact size
                    $image->cover($sizeConfig['width'], $sizeConfig['height']);
                } else {
                    // Scale proportionally
                    $image->scale(
                        $sizeConfig['width'],
                        $sizeConfig['height']
                    );
                }
            }

            // Encode with quality compression
            $encoded = $image->encode(new \Intervention\Image\Encoders\AutoEncoder(quality: $this->quality));

            // Save the image
            $path = "$folder/{$sizeName}/{$filename}.{$extension}";
            Storage::disk($this->disk)->put($path, $encoded);
            $paths[$sizeName] = $path;

            // Generate WebP version if enabled
            if ($this->webpEnabled) {
                try {
                    $webpPath = "$folder/{$sizeName}/{$filename}.webp";
                    $webpImage = $this->getManager()->read($file);
                    
                    if ($sizeConfig['width'] || $sizeConfig['height']) {
                        if ($sizeConfig['width'] && $sizeConfig['height']) {
                            $webpImage->cover($sizeConfig['width'], $sizeConfig['height']);
                        } else {
                            $webpImage->scale($sizeConfig['width'], $sizeConfig['height']);
                        }
                    }
                    
                    $webpEncoded = $webpImage->encode(new \Intervention\Image\Encoders\WebpEncoder(quality: config('image.webp.quality', 80)));
                    Storage::disk($this->disk)->put($webpPath, $webpEncoded);
                    $paths["{$sizeName}_webp"] = $webpPath;

                    // WebP-only policy for site-wide assets: if the folder is not product dual-version,
                    // delete the non-WebP output we just created.
                    if (!Str::startsWith($folder, ['products', 'product'])) {
                        Storage::disk($this->disk)->delete($path);
                        unset($paths[$sizeName]);
                    }
                } catch (\Exception $e) {
                    // WebP conversion failed, continue without it
                    \Log::warning("WebP conversion failed for {$path}: " . $e->getMessage());
                }
            }
        }

        return $paths;
    }

    /**
     * Generate dual WebP versions for product images:
     * - thumb: max width 600px, quality 80
     * - medium: max width 900px, quality 85
     * - full: original dimensions, quality 98
     * Stored under: products/thumbs + products/medium + products/full
     */
    public function generateProductWebpVersions(UploadedFile $file, string $baseName): array
    {
        $thumbPath = "products/thumbs/{$baseName}.webp";
        $mediumPath = "products/medium/{$baseName}.webp";
        $fullPath = "products/full/{$baseName}.webp";

        $manager = $this->getManager();

        $thumb = $manager->read($file);
        $thumb->scale(600, null);
        $thumbEncoded = $thumb->encode(new \Intervention\Image\Encoders\WebpEncoder(quality: 80));
        Storage::disk($this->disk)->put($thumbPath, $thumbEncoded);

        $medium = $manager->read($file);
        $medium->scale(900, null);
        $mediumEncoded = $medium->encode(new \Intervention\Image\Encoders\WebpEncoder(quality: 85));
        Storage::disk($this->disk)->put($mediumPath, $mediumEncoded);

        $full = $manager->read($file);
        $fullEncoded = $full->encode(new \Intervention\Image\Encoders\WebpEncoder(quality: 98));
        Storage::disk($this->disk)->put($fullPath, $fullEncoded);

        return [
            'thumb_path' => $thumbPath,
            'medium_path' => $mediumPath,
            'full_path' => $fullPath,
        ];
    }

    /**
     * Upload product gallery images and create ProductImage records.
     */
    public function uploadProductImages(Product $product, array $files): array
    {
        return DB::transaction(function () use ($product, $files) {
            $created = [];
            $currentMaxOrder = (int) $product->productImages()->max('order');

            foreach (array_values($files) as $idx => $file) {
                $baseName = time() . '_' . $idx . '_' . Str::random(10);
                $paths = $this->generateProductWebpVersions($file, $baseName);

                $image = ProductImage::create([
                    'product_id' => $product->id,
                    'filename' => $baseName . '.webp',
                    'path' => $paths['full_path'],
                    'thumb_path' => $paths['thumb_path'],
                    'medium_path' => $paths['medium_path'] ?? null,
                    'full_path' => $paths['full_path'],
                    'alt_text' => $product->name,
                    'size' => $file->getSize(),
                    'is_primary' => false,
                    'order' => $currentMaxOrder + $idx + 1,
                ]);

                $created[] = $image;
            }

            return $created;
        });
    }

    public function deleteImage(ProductImage $image): void
    {
        $image->delete();
    }

    public function setPrimaryImage(ProductImage $image): void
    {
        DB::transaction(function () use ($image) {
            ProductImage::where('product_id', $image->product_id)->update(['is_primary' => false]);
            $image->is_primary = true;
            $image->save();
        });
    }

    public function reorderImages(Product $product, array $imageIds): void
    {
        DB::transaction(function () use ($product, $imageIds) {
            foreach (array_values($imageIds) as $order => $id) {
                ProductImage::where('product_id', $product->id)
                    ->where('id', $id)
                    ->update(['order' => $order + 1]);
            }
        });
    }

    public function bulkDeleteImages(Product $product, array $imageIds): void
    {
        $images = $product->productImages()->whereIn('id', $imageIds)->get();
        foreach ($images as $img) {
            $img->delete();
        }
    }

    public function updateImageDetails(ProductImage $image, array $data): void
    {
        DB::transaction(function () use ($image, $data) {
            if (array_key_exists('alt_text', $data)) {
                $image->alt_text = $data['alt_text'];
            }

            if (array_key_exists('is_primary', $data)) {
                $makePrimary = (bool) $data['is_primary'];
                if ($makePrimary) {
                    ProductImage::where('product_id', $image->product_id)->update(['is_primary' => false]);
                    $image->is_primary = true;
                }
            }

            $image->save();
        });
    }

    /**
     * Ensure thumbnails exist for all product images (rebuild missing thumbs from full if possible).
     */
    public function generateThumbnails(Product $product): void
    {
        foreach ($product->productImages as $img) {
            if ($img->thumb_path && Storage::disk($this->disk)->exists($img->thumb_path)) {
                continue;
            }

            $source = $img->full_path ?: $img->path;
            if (!$source || !Storage::disk($this->disk)->exists($source)) {
                continue;
            }

            $baseName = pathinfo($source, PATHINFO_FILENAME);
            $thumbPath = "products/thumbs/{$baseName}.webp";

            $image = $this->getManager()->read(Storage::disk($this->disk)->path($source));
            $image->scale(600, null);
            $encoded = $image->encode(new \Intervention\Image\Encoders\WebpEncoder(quality: 80));
            Storage::disk($this->disk)->put($thumbPath, $encoded);

            $img->thumb_path = $thumbPath;
            if (!$img->full_path) {
                $img->full_path = $source;
            }
            if (!$img->path) {
                $img->path = $source;
            }
            $img->save();
        }
    }

    /**
     * Optimize legacy product image paths only. Avoid re-encoding the new dual WebP outputs.
     */
    public function optimizeImages(Product $product): void
    {
        foreach ($product->productImages as $img) {
            $legacy = $img->path;
            if (!$legacy) {
                continue;
            }

            if (Str::startsWith($legacy, ['products/thumbs/', 'products/full/'])) {
                continue;
            }

            $ext = strtolower(pathinfo($legacy, PATHINFO_EXTENSION));
            if ($ext === 'webp') {
                continue;
            }

            $this->compressExisting($legacy);
        }
    }

    /**
     * Delete image with all its sizes
     * 
     * @param string|null $path
     * @return bool
     */
    public function delete(?string $path): bool
    {
        if (!$path) {
            return false;
        }

        // Extract info from path
        $pathInfo = pathinfo($path);
        $folder = dirname($pathInfo['dirname']);
        $filename = $pathInfo['filename'];
        $extension = $pathInfo['extension'];

        // Delete all sizes
        foreach ($this->sizes as $sizeName => $config) {
            $sizePath = "$folder/{$sizeName}/{$filename}.{$extension}";
            Storage::disk($this->disk)->delete($sizePath);

            // Delete WebP version
            if ($this->webpEnabled) {
                $webpPath = "$folder/{$sizeName}/{$filename}.webp";
                Storage::disk($this->disk)->delete($webpPath);
            }
        }

        return true;
    }

    /**
     * Create quick thumbnail
     * 
     * @param UploadedFile $file
     * @param int $size
     * @return string
     */
    public function thumbnail(UploadedFile $file, int $size = 150): string
    {
        $image = $this->getManager()->read($file);
        $image->cover($size, $size);
        
        $encoded = $image->encode(new \Intervention\Image\Encoders\JpegEncoder(quality: 90));

        $filename = uniqid() . '_thumb_' . time() . '.jpg';
        $path = "thumbnails/{$filename}";
        
        Storage::disk($this->disk)->put($path, $encoded);

        return $path;
    }

    /**
     * Compress existing image
     * 
     * @param string $path
     * @param int|null $quality
     * @return bool
     */
    public function compressExisting(string $path, ?int $quality = null): bool
    {
        $quality = $quality ?? $this->quality;
        
        if (!Storage::disk($this->disk)->exists($path)) {
            return false;
        }

        try {
            $fullPath = Storage::disk($this->disk)->path($path);
            $image = $this->getManager()->read($fullPath);
            
            $extension = pathinfo($path, PATHINFO_EXTENSION);
            
            // Choose appropriate encoder based on extension
            $encoder = match(strtolower($extension)) {
                'jpg', 'jpeg' => new \Intervention\Image\Encoders\JpegEncoder(quality: $quality),
                'png' => new \Intervention\Image\Encoders\PngEncoder(),
                'gif' => new \Intervention\Image\Encoders\GifEncoder(),
                'webp' => new \Intervention\Image\Encoders\WebpEncoder(quality: $quality),
                default => new \Intervention\Image\Encoders\AutoEncoder(quality: $quality),
            };
            
            $encoded = $image->encode($encoder);
            Storage::disk($this->disk)->put($path, $encoded);

            return true;
        } catch (\Exception $e) {
            \Log::error("Failed to compress image {$path}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Resize image to specific dimensions
     * 
     * @param string $path
     * @param int|null $width
     * @param int|null $height
     * @return bool
     */
    public function resize(string $path, ?int $width, ?int $height): bool
    {
        if (!Storage::disk($this->disk)->exists($path)) {
            return false;
        }

        try {
            $fullPath = Storage::disk($this->disk)->path($path);
            $image = $this->getManager()->read($fullPath);
            
            $image->scale($width, $height);
            
            $extension = pathinfo($path, PATHINFO_EXTENSION);
            $encoded = $image->encode(new \Intervention\Image\Encoders\AutoEncoder(quality: $this->quality));
            
            Storage::disk($this->disk)->put($path, $encoded);

            return true;
        } catch (\Exception $e) {
            \Log::error("Failed to resize image {$path}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get image dimensions
     * 
     * @param string $path
     * @return array|null
     */
    public function getDimensions(string $path): ?array
    {
        if (!Storage::disk($this->disk)->exists($path)) {
            return null;
        }

        try {
            $fullPath = Storage::disk($this->disk)->path($path);
            $image = $this->getManager()->read($fullPath);
        
        return [
                'width' => $image->width(),
                'height' => $image->height(),
            ];
        } catch (\Exception $e) {
            \Log::error("Failed to get dimensions for {$path}: " . $e->getMessage());
            return null;
        }
    }
}
