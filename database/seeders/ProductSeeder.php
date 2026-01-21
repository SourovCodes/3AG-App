<?php

namespace Database\Seeders;

use App\Enums\ProductType;
use App\Models\Package;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create WooCommerce Plugin
        $wooPlugin = Product::create([
            'name' => 'WooCommerce Booster',
            'slug' => 'woocommerce-booster',
            'description' => 'Supercharge your WooCommerce store with advanced features, performance optimization, and enhanced checkout experience.',
            'type' => ProductType::Plugin,
            'version' => '2.5.0',
            'download_url' => 'https://downloads.example.com/woocommerce-booster.zip',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->createPackages($wooPlugin, [
            ['name' => 'Starter', 'domain_limit' => 1, 'monthly' => 19, 'yearly' => 149],
            ['name' => 'Professional', 'domain_limit' => 5, 'monthly' => 49, 'yearly' => 399],
            ['name' => 'Agency', 'domain_limit' => null, 'monthly' => 99, 'yearly' => 799],
        ]);

        // Create WordPress Theme
        $theme = Product::create([
            'name' => 'Developer Theme',
            'slug' => 'developer-theme',
            'description' => 'A clean, fast, and developer-friendly WordPress theme with modern design and extensive customization options.',
            'type' => ProductType::Theme,
            'version' => '1.8.3',
            'download_url' => 'https://downloads.example.com/developer-theme.zip',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $this->createPackages($theme, [
            ['name' => 'Personal', 'domain_limit' => 1, 'monthly' => 9, 'yearly' => 79],
            ['name' => 'Business', 'domain_limit' => 3, 'monthly' => 29, 'yearly' => 249],
            ['name' => 'Unlimited', 'domain_limit' => null, 'monthly' => 59, 'yearly' => 499],
        ]);

        // Create SaaS Starter Kit
        $saas = Product::create([
            'name' => 'SaaS Starter Kit',
            'slug' => 'saas-starter-kit',
            'description' => 'Complete Laravel + React SaaS boilerplate with authentication, billing, teams, and admin panel.',
            'type' => ProductType::SourceCode,
            'version' => '3.0.0',
            'download_url' => 'https://downloads.example.com/saas-starter-kit.zip',
            'is_active' => true,
            'sort_order' => 3,
        ]);

        $this->createPackages($saas, [
            ['name' => 'Solo Developer', 'domain_limit' => 1, 'monthly' => 79, 'yearly' => 599],
            ['name' => 'Team', 'domain_limit' => 5, 'monthly' => 149, 'yearly' => 999],
            ['name' => 'Enterprise', 'domain_limit' => null, 'monthly' => 299, 'yearly' => 1999],
        ]);
    }

    /**
     * @param  array<int, array{name: string, domain_limit: int|null, monthly: int, yearly: int}>  $packages
     */
    private function createPackages(Product $product, array $packages): void
    {
        foreach ($packages as $index => $pkg) {
            $features = $this->getFeaturesForPackage($pkg['name'], $pkg['domain_limit']);

            Package::create([
                'product_id' => $product->id,
                'name' => $pkg['name'],
                'slug' => strtolower($pkg['name']),
                'description' => 'Perfect for '.(strtolower($pkg['name']) === 'agency' || strtolower($pkg['name']) === 'enterprise' ? 'agencies and large teams' : (strtolower($pkg['name']) === 'personal' || strtolower($pkg['name']) === 'starter' || strtolower($pkg['name']) === 'solo developer' ? 'individuals' : 'small to medium businesses')),
                'domain_limit' => $pkg['domain_limit'],
                'stripe_monthly_price_id' => 'price_'.fake()->regexify('[A-Za-z0-9]{24}'),
                'stripe_yearly_price_id' => 'price_'.fake()->regexify('[A-Za-z0-9]{24}'),
                'monthly_price' => $pkg['monthly'],
                'yearly_price' => $pkg['yearly'],
                'is_active' => true,
                'sort_order' => $index + 1,
                'features' => $features,
            ]);
        }
    }

    /**
     * @return array<int, string>
     */
    private function getFeaturesForPackage(string $name, ?int $domainLimit): array
    {
        $base = [
            $domainLimit ? "{$domainLimit} site license".($domainLimit > 1 ? 's' : '') : 'Unlimited sites',
            '1 year of updates',
            'Email support',
        ];

        if (in_array(strtolower($name), ['professional', 'business', 'team'])) {
            $base[] = 'Priority support';
            $base[] = 'Advanced features';
        }

        if (in_array(strtolower($name), ['agency', 'unlimited', 'enterprise'])) {
            $base[] = 'Priority support';
            $base[] = 'All features included';
            $base[] = 'White-label license';
            $base[] = 'Dedicated account manager';
        }

        return $base;
    }
}
