<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Review;
use App\Models\Customer;
use App\Models\Product;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some customers and products
        $customers = Customer::limit(5)->get();
        $products = Product::limit(10)->get();

        if ($customers->isEmpty() || $products->isEmpty()) {
            $this->command->warn('No customers or products found. Skipping review seeder.');
            return;
        }

        $reviews = [
            [
                'rating' => 5,
                'title' => 'Excellent Quality!',
                'comment' => 'Amazing product! The quality exceeded my expectations. Highly recommended!',
                'is_featured' => true,
                'is_verified_purchase' => true,
            ],
            [
                'rating' => 5,
                'title' => 'Beautiful Design',
                'comment' => 'The design is absolutely stunning. Worth every penny!',
                'is_featured' => true,
                'is_verified_purchase' => true,
            ],
            [
                'rating' => 4,
                'title' => 'Great Purchase',
                'comment' => 'Very satisfied with this purchase. Fast shipping and great quality.',
                'is_featured' => true,
                'is_verified_purchase' => true,
            ],
            [
                'rating' => 5,
                'title' => 'Perfect Gift',
                'comment' => 'Bought this as a gift and they loved it! Beautiful packaging too.',
                'is_featured' => false,
                'is_verified_purchase' => true,
            ],
            [
                'rating' => 4,
                'title' => 'Good Value',
                'comment' => 'Good quality for the price. Would buy again.',
                'is_featured' => false,
                'is_verified_purchase' => true,
            ],
        ];

        foreach ($reviews as $index => $reviewData) {
            Review::create([
                'customer_id' => $customers[$index % $customers->count()]->id,
                'product_id' => $products[$index % $products->count()]->id,
                'rating' => $reviewData['rating'],
                'title' => $reviewData['title'],
                'comment' => $reviewData['comment'],
                'is_featured' => $reviewData['is_featured'],
                'is_verified_purchase' => $reviewData['is_verified_purchase'],
                'helpful_count' => rand(0, 20),
            ]);
        }

        $this->command->info('Reviews seeded successfully!');
    }
}
