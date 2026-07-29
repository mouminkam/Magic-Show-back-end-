<?php

namespace App\Console\Commands;

use App\Models\AboutSection;
use App\Models\BlogPageSetting;
use App\Models\BlogPost;
use App\Models\Brand;
use App\Models\ContactSetting;
use App\Models\HomePageSection;
use App\Models\Page;
use App\Models\ShopPageSetting;
use App\Models\StorePageSetting;
use App\Models\TeamMember;
use App\Models\Testimonial;
use App\Services\ImageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class BackfillSiteWebpImages extends Command
{
    protected $signature = 'images:backfill-webp-site {--dry-run : Show what would change without writing or deleting files}';

    protected $description = 'Convert existing site-wide images to WebP-only, update DB paths, and delete old non-WebP files';

    public function __construct(protected ImageService $imageService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->info('Starting site-wide WebP backfill...');
        if ($dryRun) {
            $this->warn('Running in dry-run mode (no DB/file changes).');
        }

        $this->backfillHomeHero($dryRun);
        $this->backfillAbout($dryRun);
        $this->backfillPageSettings($dryRun);
        $this->backfillBlogPostsAndPages($dryRun);
        $this->backfillBrandLogos($dryRun);
        $this->backfillTeamMembers($dryRun);
        $this->backfillTestimonials($dryRun);

        $this->newLine();
        $this->info('Done.');

        return Command::SUCCESS;
    }

    private function normalizeStoredPath(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $v = trim($value);
        if ($v === '') {
            return null;
        }

        $storagePos = stripos($v, '/storage/');
        if ($storagePos !== false) {
            $path = substr($v, $storagePos + strlen('/storage/'));
            $qPos = strpos($path, '?');
            if ($qPos !== false) {
                $path = substr($path, 0, $qPos);
            }
            return ltrim($path, '/');
        }

        if (str_starts_with($v, 'http://') || str_starts_with($v, 'https://')) {
            return null;
        }

        return ltrim($v, '/');
    }

    private function deleteLegacyNonWebpVariants(string $baseFolder, string $filename, string $extension): void
    {
        $extension = strtolower($extension);
        if ($extension === 'webp') {
            return;
        }

        $sizes = array_keys(config('image.sizes', []));
        foreach ($sizes as $sizeName) {
            $legacyPath = "$baseFolder/{$sizeName}/{$filename}.{$extension}";
            Storage::disk('public')->delete($legacyPath);
        }
    }

    private function convertField(object $model, string $field, string $folder, array $sizes, string $preferredKey, bool $dryRun): void
    {
        $oldRaw = $model->{$field} ?? null;
        $old = $this->normalizeStoredPath($oldRaw);
        if (!$old) {
            return;
        }

        if (str_ends_with(strtolower($old), '.webp')) {
            return;
        }

        if (!Storage::disk('public')->exists($old)) {
            $this->warn(get_class($model) . "#{$model->id} {$field}: missing file {$old}");
            return;
        }

        $paths = $this->imageService->convertExistingToSiteWebpOnly($old, $folder, $sizes);
        if (!$paths) {
            $this->warn(get_class($model) . "#{$model->id} {$field}: conversion failed for {$old}");
            return;
        }

        $newPath = $paths[$preferredKey] ?? reset($paths);
        if (!$newPath) {
            $this->warn(get_class($model) . "#{$model->id} {$field}: no output path for {$old}");
            return;
        }

        $this->line(get_class($model) . "#{$model->id} {$field}: {$old} -> {$newPath}");

        if ($dryRun) {
            return;
        }

        $model->{$field} = $newPath;
        $model->save();

        $pi = pathinfo($old);
        $baseFolder = dirname(dirname($old));
        $this->deleteLegacyNonWebpVariants($baseFolder, $pi['filename'], $pi['extension'] ?? '');
    }

    private function backfillHomeHero(bool $dryRun): void
    {
        $section = HomePageSection::getByKey('hero');
        if (!$section) {
            return;
        }

        $settings = $section->settings ?? [];
        $old = $this->normalizeStoredPath($settings['hero_image'] ?? null);
        if (!$old) {
            return;
        }

        if (str_ends_with(strtolower($old), '.webp')) {
            return;
        }

        if (!Storage::disk('public')->exists($old)) {
            $this->warn("HomePageSection(hero) settings.hero_image: missing file {$old}");
            return;
        }

        $paths = $this->imageService->convertExistingToSiteWebpOnly($old, 'home-hero', ['banner', 'large']);
        if (!$paths) {
            $this->warn("HomePageSection(hero) settings.hero_image: conversion failed for {$old}");
            return;
        }

        $newPath = $paths['banner_webp'] ?? $paths['large_webp'] ?? $paths['original_webp'] ?? reset($paths);
        $this->line("HomePageSection(hero) settings.hero_image: {$old} -> {$newPath}");

        if ($dryRun) {
            return;
        }

        $settings['hero_image'] = $newPath;
        $section->settings = $settings;
        $section->save();

        $pi = pathinfo($old);
        $baseFolder = dirname(dirname($old));
        $this->deleteLegacyNonWebpVariants($baseFolder, $pi['filename'], $pi['extension'] ?? '');
    }

    private function backfillAbout(bool $dryRun): void
    {
        $sections = AboutSection::query()->get();
        foreach ($sections as $s) {
            if ($s->section_key === 'hero') {
                $this->convertField($s, 'background_image', 'about/hero', ['banner', 'large'], 'banner_webp', $dryRun);
            }

            if ($s->section_key === 'description') {
                $this->convertField($s, 'image', 'about/description', ['original', 'medium'], 'original_webp', $dryRun);
            }
        }
    }

    private function backfillPageSettings(bool $dryRun): void
    {
        $blog = BlogPageSetting::get();
        $this->convertField($blog, 'hero_background_image', 'blog-page/hero', ['banner', 'large'], 'banner_webp', $dryRun);

        $contact = ContactSetting::get();
        $this->convertField($contact, 'hero_background_image', 'contact/hero', ['banner', 'large'], 'banner_webp', $dryRun);

        $shop = ShopPageSetting::get();
        $this->convertField($shop, 'hero_background_image', 'shop-page/hero', ['banner', 'large'], 'banner_webp', $dryRun);

        $store = StorePageSetting::get();
        $this->convertField($store, 'hero_background_image', 'store-page/hero', ['banner', 'large'], 'banner_webp', $dryRun);
    }

    private function backfillBlogPostsAndPages(bool $dryRun): void
    {
        foreach (BlogPost::whereNotNull('featured_image')->get() as $post) {
            $this->convertField($post, 'featured_image', 'blog/featured', ['banner', 'large'], 'banner_webp', $dryRun);
        }

        foreach (Page::whereNotNull('featured_image')->get() as $page) {
            $this->convertField($page, 'featured_image', 'pages/featured', ['banner', 'large'], 'banner_webp', $dryRun);
        }
    }

    private function backfillBrandLogos(bool $dryRun): void
    {
        foreach (Brand::whereNotNull('logo')->get() as $brand) {
            $old = $this->normalizeStoredPath($brand->logo);
            if (!$old || str_ends_with(strtolower($old), '.webp')) {
                continue;
            }

            if (!Storage::disk('public')->exists($old)) {
                $this->warn("Brand#{$brand->id} logo: missing file {$old}");
                continue;
            }

            $paths = $this->imageService->convertExistingToSiteWebpOnly($old, 'brands/logos', ['original', 'thumbnail']);
            if (!$paths) {
                $this->warn("Brand#{$brand->id} logo: conversion failed for {$old}");
                continue;
            }

            $newLogo = $paths['original_webp'] ?? reset($paths);
            $newThumb = $paths['thumbnail_webp'] ?? null;
            $this->line("Brand#{$brand->id} logo: {$old} -> {$newLogo}");

            if ($dryRun) {
                continue;
            }

            $brand->logo = $newLogo;
            if ($newThumb) {
                $brand->logo_thumb = $newThumb;
            }
            $brand->save();

            $pi = pathinfo($old);
            $baseFolder = dirname(dirname($old));
            $this->deleteLegacyNonWebpVariants($baseFolder, $pi['filename'], $pi['extension'] ?? '');
        }
    }

    private function backfillTeamMembers(bool $dryRun): void
    {
        foreach (TeamMember::whereNotNull('image')->get() as $member) {
            $old = $this->normalizeStoredPath($member->image);
            if (!$old || str_ends_with(strtolower($old), '.webp')) {
                continue;
            }

            if (!Storage::disk('public')->exists($old)) {
                $this->warn("TeamMember#{$member->id} image: missing file {$old}");
                continue;
            }

            $paths = $this->imageService->convertExistingToSiteWebpOnly($old, 'team-members', ['original', 'medium', 'thumbnail']);
            if (!$paths) {
                $this->warn("TeamMember#{$member->id} image: conversion failed for {$old}");
                continue;
            }

            $newOriginal = $paths['original_webp'] ?? reset($paths);
            $newMedium = $paths['medium_webp'] ?? null;
            $newThumb = $paths['thumbnail_webp'] ?? null;

            $this->line("TeamMember#{$member->id} image: {$old} -> {$newOriginal}");

            if ($dryRun) {
                continue;
            }

            $member->image = $newOriginal;
            if ($newMedium) {
                $member->image_medium = $newMedium;
            }
            if ($newThumb) {
                $member->image_thumb = $newThumb;
            }
            $member->save();

            $pi = pathinfo($old);
            $baseFolder = dirname(dirname($old));
            $this->deleteLegacyNonWebpVariants($baseFolder, $pi['filename'], $pi['extension'] ?? '');
        }
    }

    private function backfillTestimonials(bool $dryRun): void
    {
        foreach (Testimonial::whereNotNull('customer_image')->get() as $t) {
            $old = $this->normalizeStoredPath($t->customer_image);
            if (!$old || str_ends_with(strtolower($old), '.webp')) {
                continue;
            }

            if (!Storage::disk('public')->exists($old)) {
                $this->warn("Testimonial#{$t->id} customer_image: missing file {$old}");
                continue;
            }

            $paths = $this->imageService->convertExistingToSiteWebpOnly($old, 'testimonials', ['original', 'thumbnail']);
            if (!$paths) {
                $this->warn("Testimonial#{$t->id} customer_image: conversion failed for {$old}");
                continue;
            }

            $newOriginal = $paths['original_webp'] ?? reset($paths);
            $newThumb = $paths['thumbnail_webp'] ?? null;

            $this->line("Testimonial#{$t->id} customer_image: {$old} -> {$newOriginal}");

            if ($dryRun) {
                continue;
            }

            $t->customer_image = $newOriginal;
            if ($newThumb) {
                $t->customer_image_thumb = $newThumb;
            }
            $t->save();

            $pi = pathinfo($old);
            $baseFolder = dirname(dirname($old));
            $this->deleteLegacyNonWebpVariants($baseFolder, $pi['filename'], $pi['extension'] ?? '');
        }
    }
}
