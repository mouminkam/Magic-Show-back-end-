<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ImageService;
use App\Models\TeamMember;
use App\Models\Testimonial;
use App\Models\Brand;

class OptimizeExistingImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'images:optimize {--model=all : The model to optimize (all, team, testimonials, brands)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Compress and optimize existing images in the database';

    protected $imageService;

    /**
     * Create a new command instance.
     */
    public function __construct(ImageService $imageService)
    {
        parent::__construct();
        $this->imageService = $imageService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $model = $this->option('model');

        $this->info('🚀 Starting image optimization...');
        $this->newLine();

        if ($model === 'all' || $model === 'team') {
            $this->optimizeTeamMembers();
        }

        if ($model === 'all' || $model === 'testimonials') {
            $this->optimizeTestimonials();
        }

        if ($model === 'all' || $model === 'brands') {
            $this->optimizeBrands();
        }

        $this->newLine();
        $this->info('✅ All images have been optimized successfully!');
        
        return Command::SUCCESS;
    }

    /**
     * Optimize team member images.
     */
    protected function optimizeTeamMembers()
    {
        $this->info('📸 Optimizing Team Members images...');
        
        $members = TeamMember::whereNotNull('image')->get();
        
        if ($members->isEmpty()) {
            $this->warn('   No team member images found.');
            return;
        }

        $bar = $this->output->createProgressBar($members->count());
        $bar->start();

        $success = 0;
        $failed = 0;

        foreach ($members as $member) {
            if ($member->image) {
                if ($this->imageService->compressExisting($member->image)) {
                    $success++;
                } else {
                    $failed++;
                }
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->line("   ✓ Optimized {$success} team member images");
        
        if ($failed > 0) {
            $this->warn("   ⚠ Failed to optimize {$failed} images");
        }
    }

    /**
     * Optimize testimonial images.
     */
    protected function optimizeTestimonials()
    {
        $this->info('📸 Optimizing Testimonials images...');
        
        $testimonials = Testimonial::whereNotNull('customer_image')->get();
        
        if ($testimonials->isEmpty()) {
            $this->warn('   No testimonial images found.');
            return;
        }

        $bar = $this->output->createProgressBar($testimonials->count());
        $bar->start();

        $success = 0;
        $failed = 0;

        foreach ($testimonials as $testimonial) {
            if ($testimonial->customer_image) {
                if ($this->imageService->compressExisting($testimonial->customer_image)) {
                    $success++;
                } else {
                    $failed++;
                }
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->line("   ✓ Optimized {$success} testimonial images");
        
        if ($failed > 0) {
            $this->warn("   ⚠ Failed to optimize {$failed} images");
        }
    }

    /**
     * Optimize brand logos.
     */
    protected function optimizeBrands()
    {
        $this->info('📸 Optimizing Brand logos...');
        
        $brands = Brand::whereNotNull('logo')->get();
        
        if ($brands->isEmpty()) {
            $this->warn('   No brand logos found.');
            return;
        }

        $bar = $this->output->createProgressBar($brands->count());
        $bar->start();

        $success = 0;
        $failed = 0;

        foreach ($brands as $brand) {
            if ($brand->logo) {
                if ($this->imageService->compressExisting($brand->logo)) {
                    $success++;
                } else {
                    $failed++;
                }
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->line("   ✓ Optimized {$success} brand logos");
        
        if ($failed > 0) {
            $this->warn("   ⚠ Failed to optimize {$failed} images");
        }
    }
}
