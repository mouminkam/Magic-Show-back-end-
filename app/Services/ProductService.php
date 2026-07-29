<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Category;
use App\Models\Color;
use App\Models\ProductAttributeValue;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductService
{
    protected ImageService $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    private function normalizeColorCode(?string $incoming): ?string
    {
        if (!$incoming) {
            return null;
        }

        $hex = strtoupper(ltrim($incoming, '#'));
        if (strlen($hex) !== 6 || !ctype_xdigit($hex)) {
            return null;
        }

        return '#' . $hex;
    }

    private function syncProductColorsFromRequest(Product $product, Request $request): void
    {
        if ($request->has('color_ids') && is_array($request->color_ids)) {
            $colorIds = collect($request->color_ids)
                ->filter(fn($id) => is_numeric($id) && (int) $id > 0)
                ->map(fn($id) => (int) $id)
                ->unique()
                ->values();

            if ($colorIds->isNotEmpty()) {
                $payload = [];
                foreach ($colorIds as $index => $colorId) {
                    $payload[$colorId] = [
                        'is_primary' => $index === 0,
                        'sort_order' => $index,
                    ];
                }
                $product->colors()->sync($payload);
                return;
            }
        }

        if (!$request->has('colors') || !is_array($request->colors)) {
            return;
        }

        $colorsData = [];

        foreach ($request->colors as $index => $colorData) {
            if (!is_array($colorData)) {
                continue;
            }

            $isPrimary = isset($colorData['is_primary']) && $colorData['is_primary'];
            $sortOrder = $index;

            if (isset($colorData['color_id']) && !empty($colorData['color_id'])) {
                $colorId = $colorData['color_id'];
                $colorsData[$colorId] = [
                    'is_primary' => $isPrimary,
                    'sort_order' => $sortOrder,
                ];
                continue;
            }

            $colorCode = $this->normalizeColorCode($colorData['color_code'] ?? $colorData['hex_code'] ?? null);
            if (!$colorCode) {
                continue;
            }

            $name = isset($colorData['name']) && is_string($colorData['name']) ? trim($colorData['name']) : '';
            if ($name === '') {
                $name = $colorCode;
            }

            $color = Color::query()->firstOrCreate(
                ['color_code' => $colorCode],
                [
                    'name' => $name,
                    'hex_code' => $colorCode,
                    'category' => 'أخرى',
                    'is_active' => true,
                    'sort_order' => 0,
                ]
            );

            if ($color->name !== $name) {
                $color->name = $name;
                $color->save();
            }

            $colorsData[$color->id] = [
                'is_primary' => $isPrimary,
                'sort_order' => $sortOrder,
            ];
        }

        $product->colors()->sync($colorsData);
        \Log::info('Colors synced for product', [
            'product_id' => $product->id,
            'colors_data' => $colorsData,
        ]);
    }

    /**
     * Get products with filters and pagination
     */
    public function getProducts(Request $request)
    {
        $query = Product::with(['categories', 'attributeValues.attribute']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Category filter
        if ($request->filled('category_id')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('categories.id', $request->category_id);
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $isActive = $request->status === 'active' ? 1 : 0;
            $query->where('is_active', $isActive);
        }

        // Price range filter
        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        // Stock status filter
        if ($request->filled('stock_status')) {
            switch ($request->stock_status) {
                case 'in_stock':
                    $query->where('quantity', '>', 0);
                    break;
                case 'low_stock':
                    $query->where('quantity', '>', 0)->where('quantity', '<=', 10);
                    break;
                case 'out_of_stock':
                    $query->where('quantity', 0);
                    break;
            }
        }

        // Sort functionality
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate(20);
    }

    /**
     * Create a new product
     */
    public function createProduct(Request $request)
    {
        \Log::info('ProductService::createProduct started', [
            'request_data' => $request->all(),
            'has_files' => $request->hasFile('featured_image'),
            'files_count' => $request->hasFile('images') ? count($request->file('images')) : 0,
        ]);

        return DB::transaction(function () use ($request) {
            try {
                // Validate request
                $validated = $request->validate([
                'name' => 'nullable|string|max:255',
                'name_ar' => 'nullable|string|max:255',
                'name_en' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'description_ar' => 'nullable|string',
                'description_en' => 'nullable|string',
                'short_description' => 'nullable|string',
                'short_description_ar' => 'nullable|string|max:500',
                'short_description_en' => 'nullable|string|max:500',
                'sku' => 'required|string|unique:products,sku',
                'price' => 'required|numeric|min:0',
                'sale_price' => 'nullable|numeric|min:0|max:' . ($request->input('price', 999999)),
                'quantity' => 'required|integer|min:0',
                'brand_id' => 'nullable|exists:brands,id',
                'material_id' => 'nullable|exists:materials,id',
                'model' => 'nullable|string|max:255',
                'weight' => 'nullable|numeric|min:0',
                'dimensions' => 'nullable|string|max:255',
                'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
                'meta_title' => 'nullable|string|max:255',
                'meta_description' => 'nullable|string|max:500',
                'categories' => 'array',
                'categories.*' => 'exists:categories,id',
                'size_ids' => 'required|array|min:1',
                'size_ids.*' => 'exists:sizes,id',
                'season_ids' => 'required|array|min:1',
                'season_ids.*' => 'exists:seasons,id',
                'color_ids' => 'required|array|min:1',
                'color_ids.*' => 'exists:colors,id',
                'attributes' => 'nullable|array',
                'attributes.*' => 'nullable|string|max:255',
                'images' => 'array',
                'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:10240',
            ], [
                'sale_price.max' => 'سعر التخفيض لا يمكن أن يكون أكبر من السعر الأساسي',
                'sale_price.min' => 'سعر التخفيض يجب أن يكون أكبر من أو يساوي صفر',
                'sale_price.numeric' => 'سعر التخفيض يجب أن يكون رقماً صحيحاً',
                'price.required' => 'السعر الأساسي مطلوب',
                'price.numeric' => 'السعر الأساسي يجب أن يكون رقماً صحيحاً',
                'price.min' => 'السعر الأساسي يجب أن يكون أكبر من أو يساوي صفر',
            ]);

            // Derive name from name_ar/name_en for backward compatibility
            $validated['name'] = $validated['name_en'] ?? $validated['name_ar'] ?? $validated['name'] ?? 'Product';
            $validated['description'] = $validated['description_en'] ?? $validated['description_ar'] ?? $validated['description'] ?? null;
            $validated['short_description'] = $validated['short_description_en'] ?? $validated['short_description_ar'] ?? $validated['short_description'] ?? null;

            // Generate SKU if not provided
            if (empty($validated['sku'])) {
                $validated['sku'] = $this->generateSKU($validated['name']);
            }

            // Generate unique slug
            $validated['slug'] = $this->generateUniqueSlug($validated['name']);

            // Handle boolean fields properly
            $validated['is_active'] = $request->has('is_active') ? (bool) $request->input('is_active') : false;
            $validated['is_featured'] = $request->has('is_featured') ? (bool) $request->input('is_featured') : false;
            $validated['requires_shipping'] = $request->has('requires_shipping') ? (bool) $request->input('requires_shipping') : false;

            // Handle sale_price properly
            if ($request->has('sale_price') && $request->sale_price !== null && $request->sale_price !== '') {
                $salePrice = (float) $request->sale_price;
                $validated['sale_price'] = $salePrice > 0 ? $salePrice : null;
            } else {
                $validated['sale_price'] = null;
            }

            // التحقق الأمثل لسعر التخفيض
            if (isset($validated['sale_price']) && $validated['sale_price'] !== null) {
                // التحقق من أن السعر الأساسي محدد
                if (!isset($validated['price']) || $validated['price'] === null || $validated['price'] <= 0) {
                    throw new \InvalidArgumentException('يجب تحديد السعر الأساسي أولاً قبل إدخال سعر التخفيض');
                }
                
                // التحقق من أن سعر التخفيض أكبر من صفر
                if ($validated['sale_price'] <= 0) {
                    throw new \InvalidArgumentException('سعر التخفيض يجب أن يكون أكبر من صفر');
                }
                
                // التحقق من أن سعر التخفيض أقل من السعر الأساسي
                if ($validated['sale_price'] >= $validated['price']) {
                    throw new \InvalidArgumentException('سعر التخفيض (' . number_format($validated['sale_price'], 2) . ' ر.س) يجب أن يكون أقل من السعر الأساسي (' . number_format($validated['price'], 2) . ' ر.س)');
                }
                
                // التحقق من أن سعر التخفيض منطقي (لا يقل عن 10% من السعر الأساسي)
                $minimumSalePrice = $validated['price'] * 0.1;
                if ($validated['sale_price'] < $minimumSalePrice) {
                    throw new \InvalidArgumentException('سعر التخفيض (' . number_format($validated['sale_price'], 2) . ' ر.س) منخفض جداً. الحد الأدنى المسموح: ' . number_format($minimumSalePrice, 2) . ' ر.س');
                }
            }

            // Debug logging
            \Log::info('Product creation data:', [
                'request_data' => $request->all(),
                'is_active_has' => $request->has('is_active'),
                'is_active_input' => $request->input('is_active'),
                'is_active_value' => $validated['is_active'],
                'is_featured_has' => $request->has('is_featured'),
                'is_featured_input' => $request->input('is_featured'),
                'is_featured_value' => $validated['is_featured'],
                'requires_shipping_has' => $request->has('requires_shipping'),
                'requires_shipping_input' => $request->input('requires_shipping'),
                'requires_shipping_value' => $validated['requires_shipping'],
                'sale_price_has' => $request->has('sale_price'),
                'sale_price_input' => $request->input('sale_price'),
                'sale_price_type' => gettype($request->input('sale_price')),
                'sale_price_value' => $validated['sale_price']
            ]);

            // Handle featured image upload FIRST
            if ($request->hasFile('featured_image')) {
                $featuredImage = $request->file('featured_image');
                $baseName = time() . '_featured_' . Str::random(10);
                $paths = $this->imageService->generateProductWebpVersions($featuredImage, $baseName);
                $validated['featured_image_thumb'] = $paths['thumb_path'];
                $validated['featured_image_medium'] = $paths['medium_path'] ?? null;
                $validated['featured_image_full'] = $paths['full_path'];
                // Backward compatibility
                $validated['featured_image'] = $paths['full_path'];
                \Log::info('Featured image saved (thumb/full)', $paths);
            }

            // Create product
            $product = Product::create($validated);

            // Attach categories - fix this
            if ($request->has('categories') && is_array($request->categories)) {
                $categoryIds = array_filter($request->categories, function($id) {
                    return !empty($id) && is_numeric($id);
                });
                
                if (!empty($categoryIds)) {
                    $product->categories()->attach($categoryIds);
                    \Log::info('Categories attached to product', [
                        'product_id' => $product->id,
                        'category_ids' => $categoryIds
                    ]);
                }
            }

            // Handle additional images
            if ($request->hasFile('images')) {
                $this->handleImageUpload($product, $request->file('images'));
                \Log::info('Additional images processed', [
                    'product_id' => $product->id,
                    'image_count' => count($request->file('images'))
                ]);
            }

            // Handle attributes
            if (isset($validated['attributes'])) {
                $this->handleAttributeValues($product, $validated['attributes']);
            }

            // Handle colors
            $this->syncProductColorsFromRequest($product, $request);
            $product->sizes()->sync($request->input('size_ids', []));
            $product->seasons()->sync($request->input('season_ids', []));

            // Create default inventory for the product
            $this->createDefaultInventory($product);

            CacheService::invalidateProducts();
            return $product;
            } catch (\Exception $e) {
                \Log::error('ProductService::createProduct failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'request_data' => $request->all(),
                ]);
                throw $e; // Re-throw to be caught by controller
            }
        });
    }

    /**
     * Update an existing product
     */
    public function updateProduct(Product $product, Request $request)
    {
        return DB::transaction(function () use ($product, $request) {
            // Validate request (name_ar/name_en used by form; name derived below)
            $validated = $request->validate([
                'name' => 'nullable|string|max:255',
                'name_ar' => 'nullable|string|max:255',
                'name_en' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'description_ar' => 'nullable|string',
                'description_en' => 'nullable|string',
                'short_description' => 'nullable|string',
                'short_description_ar' => 'nullable|string|max:500',
                'short_description_en' => 'nullable|string|max:500',
                'sku' => 'required|string|unique:products,sku,' . $product->id,
                'price' => 'required|numeric|min:0',
                'sale_price' => 'nullable|numeric|min:0|max:' . ($request->input('price', $product->price ?? 999999)),
                'quantity' => 'required|integer|min:0',
                'brand_id' => 'nullable|exists:brands,id',
                'material_id' => 'nullable|exists:materials,id',
                'model' => 'nullable|string|max:255',
                'weight' => 'nullable|numeric|min:0',
                'dimensions' => 'nullable|string|max:255',
                'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
                'meta_title' => 'nullable|string|max:255',
                'meta_description' => 'nullable|string|max:500',
                'categories' => 'array',
                'categories.*' => 'exists:categories,id',
                'size_ids' => 'required|array|min:1',
                'size_ids.*' => 'exists:sizes,id',
                'season_ids' => 'required|array|min:1',
                'season_ids.*' => 'exists:seasons,id',
                'color_ids' => 'required|array|min:1',
                'color_ids.*' => 'exists:colors,id',
                'attributes' => 'nullable|array',
                'attributes.*' => 'nullable|string|max:255',
                'images' => 'array',
                'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:10240',
                'deleted_images' => 'nullable|array',
                'deleted_images.*' => 'integer',
            ], [
                'sale_price.max' => 'سعر التخفيض لا يمكن أن يكون أكبر من السعر الأساسي',
                'sale_price.min' => 'سعر التخفيض يجب أن يكون أكبر من أو يساوي صفر',
                'sale_price.numeric' => 'سعر التخفيض يجب أن يكون رقماً صحيحاً',
                'price.required' => 'السعر الأساسي مطلوب',
                'price.numeric' => 'السعر الأساسي يجب أن يكون رقماً صحيحاً',
                'price.min' => 'السعر الأساسي يجب أن يكون أكبر من أو يساوي صفر',
            ]);

            // Derive name, description, short_description from _ar/_en
            $validated['name'] = $validated['name_en'] ?? $validated['name_ar'] ?? $validated['name'] ?? $product->name;
            $validated['description'] = $validated['description_en'] ?? $validated['description_ar'] ?? $validated['description'] ?? $product->description;
            $validated['short_description'] = $validated['short_description_en'] ?? $validated['short_description_ar'] ?? $validated['short_description'] ?? $product->short_description;

            // Generate slug if name changed
            if ($product->name !== $validated['name']) {
                $validated['slug'] = $this->generateUniqueSlug($validated['name'], $product->id);
            }

            // Handle boolean fields properly
            $validated['is_active'] = $request->has('is_active') ? (bool) $request->input('is_active') : false;
            $validated['is_featured'] = $request->has('is_featured') ? (bool) $request->input('is_featured') : false;
            $validated['requires_shipping'] = $request->has('requires_shipping') ? (bool) $request->input('requires_shipping') : false;

            // Handle sale_price properly
            if ($request->has('sale_price') && $request->sale_price !== null && $request->sale_price !== '') {
                $salePrice = (float) $request->sale_price;
                $validated['sale_price'] = $salePrice > 0 ? $salePrice : null;
            } else {
                $validated['sale_price'] = null;
            }

            // التحقق الأمثل لسعر التخفيض
            if (isset($validated['sale_price']) && $validated['sale_price'] !== null) {
                // التحقق من أن السعر الأساسي محدد
                if (!isset($validated['price']) || $validated['price'] === null || $validated['price'] <= 0) {
                    throw new \InvalidArgumentException('يجب تحديد السعر الأساسي أولاً قبل إدخال سعر التخفيض');
                }
                
                // التحقق من أن سعر التخفيض أكبر من صفر
                if ($validated['sale_price'] <= 0) {
                    throw new \InvalidArgumentException('سعر التخفيض يجب أن يكون أكبر من صفر');
                }
                
                // التحقق من أن سعر التخفيض أقل من السعر الأساسي
                if ($validated['sale_price'] >= $validated['price']) {
                    throw new \InvalidArgumentException('سعر التخفيض (' . number_format($validated['sale_price'], 2) . ' ر.س) يجب أن يكون أقل من السعر الأساسي (' . number_format($validated['price'], 2) . ' ر.س)');
                }
                
                // التحقق من أن سعر التخفيض منطقي (لا يقل عن 10% من السعر الأساسي)
                $minimumSalePrice = $validated['price'] * 0.1;
                if ($validated['sale_price'] < $minimumSalePrice) {
                    throw new \InvalidArgumentException('سعر التخفيض (' . number_format($validated['sale_price'], 2) . ' ر.س) منخفض جداً. الحد الأدنى المسموح: ' . number_format($minimumSalePrice, 2) . ' ر.س');
                }
            }

            // Handle featured image update
            if ($request->hasFile('featured_image')) {
                // Delete old featured image if exists
                $featuredImage = $request->file('featured_image');

                $oldPaths = array_values(array_unique(array_filter([
                    $product->featured_image_thumb,
                    $product->featured_image_medium,
                    $product->featured_image_full,
                    $product->featured_image,
                ], fn ($p) => is_string($p) && trim($p) !== '')));

                foreach ($oldPaths as $oldPath) {
                    Storage::disk('public')->delete($oldPath);
                }

                $baseName = time() . '_featured_' . Str::random(10);
                $paths = $this->imageService->generateProductWebpVersions($featuredImage, $baseName);
                $validated['featured_image_thumb'] = $paths['thumb_path'];
                $validated['featured_image_medium'] = $paths['medium_path'] ?? null;
                $validated['featured_image_full'] = $paths['full_path'];
                // Backward compatibility
                $validated['featured_image'] = $paths['full_path'];
                \Log::info('Featured image updated (thumb/full)', $paths);
            }

            // Debug logging
            \Log::info('Product update data:', [
                'product_id' => $product->id,
                'request_data' => $request->all(),
                'is_active_has' => $request->has('is_active'),
                'is_active_input' => $request->input('is_active'),
                'is_active_value' => $validated['is_active'],
                'is_featured_has' => $request->has('is_featured'),
                'is_featured_input' => $request->input('is_featured'),
                'is_featured_value' => $validated['is_featured'],
                'requires_shipping_has' => $request->has('requires_shipping'),
                'requires_shipping_input' => $request->input('requires_shipping'),
                'requires_shipping_value' => $validated['requires_shipping'],
                'sale_price_has' => $request->has('sale_price'),
                'sale_price_input' => $request->input('sale_price'),
                'sale_price_type' => gettype($request->input('sale_price')),
                'sale_price_value' => $validated['sale_price']
            ]);

            // Update product
            $product->update($validated);

            // Update categories - fix this
            if ($request->has('categories')) {
                $categoryIds = [];
                if (is_array($request->categories)) {
                    $categoryIds = array_filter($request->categories, function($id) {
                        return !empty($id) && is_numeric($id);
                    });
                }
                
                $product->categories()->sync($categoryIds);
                \Log::info('Categories synced for product', [
                    'product_id' => $product->id,
                    'category_ids' => $categoryIds,
                    'old_categories' => $product->categories->pluck('id')->toArray()
                ]);
            }

            // Handle colors
            $this->syncProductColorsFromRequest($product, $request);
            $product->sizes()->sync($request->input('size_ids', []));
            $product->seasons()->sync($request->input('season_ids', []));

            // Handle image deletion
            if ($request->has('deleted_images') && is_array($request->deleted_images)) {
                $this->handleImageDeletion($product, $request->deleted_images);
                \Log::info('Images deleted', [
                    'product_id' => $product->id,
                    'deleted_image_ids' => $request->deleted_images
                ]);
            }

            // Handle new images
            if ($request->hasFile('images')) {
                $this->handleImageUpload($product, $request->file('images'));
                \Log::info('New images added', [
                    'product_id' => $product->id,
                    'image_count' => count($request->file('images'))
                ]);
            }

            // Handle attributes
            if (isset($validated['attributes'])) {
                $this->handleAttributeValues($product, $validated['attributes']);
            }

            // Sync inventory quantities if quantity was updated
            if ($request->has('quantity')) {
                $this->syncInventoryQuantities($product);
            }

            CacheService::invalidateProducts();
            return $product;
        });
    }

    /**
     * Delete a product
     */
    public function deleteProduct(Product $product)
    {
        return DB::transaction(function () use ($product) {
            // Delete images
            $this->deleteProductImages($product);

            // Delete attribute values
            $product->attributeValues()->delete();

            // Detach categories and colors
            $product->categories()->detach();
            $product->colors()->detach();

            // Delete product
            $product->delete();
            CacheService::invalidateProducts();
        });
    }

    /**
     * Bulk delete products
     */
    public function bulkDeleteProducts(array $productIds)
    {
        return DB::transaction(function () use ($productIds) {
            $products = Product::whereIn('id', $productIds)->get();
            
            foreach ($products as $product) {
                $this->deleteProduct($product);
            }
        });
    }

    /**
     * Bulk update product status
     */
    public function bulkUpdateStatus(array $productIds, string $status)
    {
        $result = Product::whereIn('id', $productIds)->update(['is_active' => $status === 'active']);
        CacheService::invalidateProducts();
        return $result;
    }

    private function generateUniqueSlug(string $name, ?int $excludeId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 2;

        while (Product::where('slug', $slug)->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))->exists()) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Generate unique SKU
     */
    private function generateSKU(string $name): string
    {
        $base = Str::upper(Str::substr(Str::slug($name), 0, 8));
        $counter = 1;
        
        do {
            $sku = $base . str_pad($counter, 3, '0', STR_PAD_LEFT);
            $counter++;
        } while (Product::where('sku', $sku)->exists());
        
        return $sku;
    }

    /**
     * Handle image upload
     */
    private function handleImageUpload(Product $product, array $images)
    {
        $this->imageService->uploadProductImages($product, $images);
    }

    /**
     * Handle image deletion
     */
    private function handleImageDeletion(Product $product, array $imageIds)
    {
        \Log::info('Starting image deletion', [
            'product_id' => $product->id,
            'image_ids' => $imageIds
        ]);
        
        $images = $product->productImages()->whereIn('id', $imageIds)->get();
        
        \Log::info('Found images to delete', [
            'product_id' => $product->id,
            'found_images_count' => $images->count(),
            'found_image_ids' => $images->pluck('id')->toArray()
        ]);
        
        foreach ($images as $image) {
            // Delete image record (model hook deletes thumb/full/path)
            $image->delete();
            \Log::info('Image record deleted', ['image_id' => $image->id]);
        }
    }

    /**
     * Delete all product images
     */
    private function deleteProductImages(Product $product)
    {
        $images = $product->productImages;
        
        foreach ($images as $image) {
            // Model hook deletes thumb/full/path
            $image->delete();
        }
    }


    /**
     * Create default inventory for a product
     */
    private function createDefaultInventory(Product $product): void
    {
        try {
            // Get default locations
            $defaultBranch = $this->getOrCreateDefaultBranch();
            $defaultWarehouse = $this->getOrCreateDefaultWarehouse();
            
            // Check if inventory already exists for this product
            $existingInventory = \App\Models\Inventory::where('product_id', $product->id)->exists();
            
            if ($existingInventory) {
                \Log::info('Inventory already exists for product, skipping creation', [
                    'product_id' => $product->id,
                    'product_name' => $product->name
                ]);
                return;
            }
            
            // Create branch inventory
            \App\Models\Inventory::create([
                'product_id' => $product->id,
                'location_type' => 'App\Models\Branch',
                'location_id' => $defaultBranch->id,
                'quantity' => $product->quantity,
                'reserved_quantity' => 0,
                'available_quantity' => $product->quantity,
                'min_quantity' => $product->min_quantity ?? 10,
                'max_quantity' => null,
                'cost_price' => $product->cost_price,
                'batch_number' => 'BR-' . strtoupper(substr($product->sku ?? 'PROD', 0, 8)) . '-' . date('Ymd'),
                'expiry_date' => null,
                'rack_location' => 'A-01',
                'shelf_location' => 'S-01',
                'notes' => "Auto-created for product: {$product->name}",
                'is_active' => $product->is_active,
            ]);
            
            // Create warehouse inventory (20% of branch quantity)
            $warehouseQuantity = max(1, intval($product->quantity * 0.2));
            \App\Models\Inventory::create([
                'product_id' => $product->id,
                'location_type' => 'App\Models\Warehouse',
                'location_id' => $defaultWarehouse->id,
                'quantity' => $warehouseQuantity,
                'reserved_quantity' => 0,
                'available_quantity' => $warehouseQuantity,
                'min_quantity' => $product->min_quantity ?? 20,
                'max_quantity' => null,
                'cost_price' => $product->cost_price,
                'batch_number' => 'WH-' . strtoupper(substr($product->sku ?? 'PROD', 0, 8)) . '-' . date('Ymd'),
                'expiry_date' => null,
                'rack_location' => 'W-01',
                'shelf_location' => 'WS-01',
                'notes' => "Warehouse stock for product: {$product->name}",
                'is_active' => $product->is_active,
            ]);
            
            \Log::info('Default inventory created for product', [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'branch_quantity' => $product->quantity,
                'warehouse_quantity' => $warehouseQuantity,
                'branch_id' => $defaultBranch->id,
                'warehouse_id' => $defaultWarehouse->id,
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Failed to create default inventory for product', [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Don't throw the error to avoid breaking product creation
            // The product will still be created, but without inventory
        }
    }

    /**
     * Get or create default branch
     */
    private function getOrCreateDefaultBranch(): object
    {
        $branch = \App\Models\Branch::where('code', 'MAIN')->first();
        
        if (!$branch) {
            $branch = \App\Models\Branch::create([
                'name' => 'الفرع الرئيسي',
                'code' => 'MAIN',
                'description' => 'الفرع الرئيسي - تم إنشاؤه تلقائياً',
                'address' => 'الرياض، المملكة العربية السعودية',
                'city' => 'الرياض',
                'state' => 'منطقة الرياض',
                'country' => 'المملكة العربية السعودية',
                'postal_code' => '12345',
                'phone' => '+966-11-123-4567',
                'email' => 'main@magicshoe.com',
                'manager_name' => 'مدير الفرع الرئيسي',
                'is_active' => true,
                'opening_time' => '09:00:00',
                'closing_time' => '22:00:00',
                'working_days' => ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'],
            ]);
        }
        
        return $branch;
    }

    /**
     * Get or create default warehouse
     */
    private function getOrCreateDefaultWarehouse(): object
    {
        $warehouse = \App\Models\Warehouse::where('code', 'MAIN-WH')->first();
        
        if (!$warehouse) {
            $warehouse = \App\Models\Warehouse::create([
                'name' => 'المستودع الرئيسي',
                'code' => 'MAIN-WH',
                'description' => 'المستودع الرئيسي - تم إنشاؤه تلقائياً',
                'address' => 'الرياض، المملكة العربية السعودية',
                'city' => 'الرياض',
                'state' => 'منطقة الرياض',
                'country' => 'المملكة العربية السعودية',
                'postal_code' => '12345',
                'phone' => '+966-11-987-6543',
                'email' => 'warehouse@magicshoe.com',
                'manager_name' => 'مدير المستودع',
                'is_active' => true,
                'type' => 'main',
                'capacity' => 10000,
                'capacity_unit' => 'sqft',
            ]);
        }
        
        return $warehouse;
    }

    /**
     * Sync inventory quantities with product quantity
     */
    private function syncInventoryQuantities(Product $product): void
    {
        try {
            $inventoryRecords = \App\Models\Inventory::where('product_id', $product->id)->get();
            
            if ($inventoryRecords->isEmpty()) {
                \Log::info('No inventory records found for product, creating default inventory', [
                    'product_id' => $product->id,
                    'product_name' => $product->name
                ]);
                $this->createDefaultInventory($product);
                return;
            }
            
            // Update branch inventory (primary location)
            $branchInventory = $inventoryRecords->where('location_type', 'App\Models\Branch')->first();
            if ($branchInventory) {
                $oldQuantity = $branchInventory->quantity;
                $branchInventory->update([
                    'quantity' => $product->quantity,
                    'available_quantity' => $product->quantity - $branchInventory->reserved_quantity,
                ]);
                
                \Log::info('Branch inventory quantity synced', [
                    'product_id' => $product->id,
                    'inventory_id' => $branchInventory->id,
                    'old_quantity' => $oldQuantity,
                    'new_quantity' => $product->quantity,
                    'available_quantity' => $branchInventory->available_quantity,
                ]);
            }
            
            // Update warehouse inventory (keep proportion)
            $warehouseInventory = $inventoryRecords->where('location_type', 'App\Models\Warehouse')->first();
            if ($warehouseInventory) {
                $warehouseQuantity = max(1, intval($product->quantity * 0.2));
                $oldQuantity = $warehouseInventory->quantity;
                $warehouseInventory->update([
                    'quantity' => $warehouseQuantity,
                    'available_quantity' => $warehouseQuantity - $warehouseInventory->reserved_quantity,
                ]);
                
                \Log::info('Warehouse inventory quantity synced', [
                    'product_id' => $product->id,
                    'inventory_id' => $warehouseInventory->id,
                    'old_quantity' => $oldQuantity,
                    'new_quantity' => $warehouseQuantity,
                    'available_quantity' => $warehouseInventory->available_quantity,
                ]);
            }
            
        } catch (\Exception $e) {
            \Log::error('Failed to sync inventory quantities', [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_quantity' => $product->quantity,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Clean and validate attribute data
     */
    private function cleanAttributeData(array $attributes): array
    {
        $cleaned = [];
        
        foreach ($attributes as $attributeId => $value) {
            // Skip if attribute ID is not numeric
            if (!is_numeric($attributeId)) {
                \Log::warning('Invalid attribute ID, skipping', [
                    'attribute_id' => $attributeId,
                    'value' => $value
                ]);
                continue;
            }
            
            // Skip if value is empty or null
            if (empty($value) || is_null($value)) {
                continue;
            }
            
            // Trim and validate value
            $value = trim($value);
            if (strlen($value) > 255) {
                $value = substr($value, 0, 255);
                \Log::warning('Attribute value truncated', [
                    'attribute_id' => $attributeId,
                    'original_length' => strlen($value),
                    'truncated_value' => $value
                ]);
            }
            
            $cleaned[$attributeId] = $value;
        }
        
        return $cleaned;
    }

    /**
     * Handle attribute values - Fixed version
     */
    private function handleAttributeValues(Product $product, array $attributes)
    {
        // Clean and filter attributes
        $attributes = $this->cleanAttributeData($attributes);
        
        \Log::info('Starting handleAttributeValues', [
            'product_id' => $product->id,
            'original_attributes' => $attributes,
            'cleaned_attributes' => $attributes
        ]);

        // Skip if no attributes to process
        if (empty($attributes)) {
            \Log::info('No attributes to process, skipping');
            return;
        }

        // Detach all existing attribute values from the product
        $product->attributeValues()->detach();
        
        // Process each attribute
        foreach ($attributes as $attributeId => $value) {
            if (!empty($value)) {
                try {
                    // Check if this value already exists for this attribute
                    $existingValue = \App\Models\ProductAttributeValue::where('product_attribute_id', $attributeId)
                        ->where('value', $value)
                        ->first();
                    
                    if ($existingValue) {
                        // Use existing value
                        $product->attributeValues()->attach($existingValue->id);
                        \Log::info('Using existing attribute value', [
                            'product_id' => $product->id,
                            'attribute_id' => $attributeId,
                            'value' => $value,
                            'existing_value_id' => $existingValue->id
                        ]);
                    } else {
                        // Create new value
                        $newValue = \App\Models\ProductAttributeValue::create([
                            'product_attribute_id' => $attributeId,
                            'value' => $value,
                            'display_value' => $value,
                            'is_active' => true,
                        ]);
                        
                        // Attach to product
                        $product->attributeValues()->attach($newValue->id);
                        
                        \Log::info('Created new attribute value', [
                            'product_id' => $product->id,
                            'attribute_id' => $attributeId,
                            'value' => $value,
                            'new_value_id' => $newValue->id
                        ]);
                    }
                } catch (\Illuminate\Database\QueryException $e) {
                    // Handle duplicate entry error
                    if ($e->getCode() == 23000) {
                        \Log::warning('Duplicate attribute value detected, trying to find existing', [
                            'product_id' => $product->id,
                            'attribute_id' => $attributeId,
                            'value' => $value,
                            'error' => $e->getMessage()
                        ]);
                        
                        // Try to find the existing value again
                        $existingValue = \App\Models\ProductAttributeValue::where('product_attribute_id', $attributeId)
                            ->where('value', $value)
                            ->first();
                        
                        if ($existingValue) {
                            // Check if already attached to prevent duplicate pivot entries
                            $alreadyAttached = $product->attributeValues()
                                ->wherePivot('product_attribute_value_id', $existingValue->id)
                                ->exists();
                            
                            if (!$alreadyAttached) {
                                $product->attributeValues()->attach($existingValue->id);
                                \Log::info('Successfully attached existing value after duplicate error', [
                                    'product_id' => $product->id,
                                    'attribute_id' => $attributeId,
                                    'value' => $value,
                                    'existing_value_id' => $existingValue->id
                                ]);
                            } else {
                                \Log::info('Value already attached to product, skipping', [
                                    'product_id' => $product->id,
                                    'attribute_id' => $attributeId,
                                    'value' => $value,
                                    'existing_value_id' => $existingValue->id
                                ]);
                            }
                        }
                    } else {
                        // Log other database errors but don't fail the entire process
                        \Log::error('Database error in attribute handling', [
                            'product_id' => $product->id,
                            'attribute_id' => $attributeId,
                            'value' => $value,
                            'error' => $e->getMessage(),
                            'error_code' => $e->getCode()
                        ]);
                        
                        // Continue with next attribute instead of failing completely
                        continue;
                    }
                } catch (\Exception $e) {
                    // Log unexpected errors but don't fail the entire process
                    \Log::error('Unexpected error in attribute handling', [
                        'product_id' => $product->id,
                        'attribute_id' => $attributeId,
                        'value' => $value,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    
                    // Continue with next attribute
                    continue;
                }
            }
        }
        
        \Log::info('Completed handleAttributeValues', [
            'product_id' => $product->id,
            'attached_values_count' => $product->attributeValues()->count()
        ]);
    }

    /**
     * Get product statistics
     */
    public function getProductStats()
    {
        return [
            'total_products' => Product::count(),
            'active_products' => Product::where('is_active', true)->count(),
            'inactive_products' => Product::where('is_active', false)->count(),
            'low_stock_products' => Product::where('quantity', '>', 0)->where('quantity', '<=', 10)->count(),
            'out_of_stock_products' => Product::where('quantity', 0)->count(),
            'total_categories' => Category::count(),
        ];
    }
}
