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

        // Additional products using realistic data
        $additionalProducts = [
            // Plugins
            ['name' => 'SEO Master Pro', 'slug' => 'seo-master-pro', 'type' => ProductType::Plugin, 'description' => 'Advanced SEO toolkit with AI-powered recommendations, schema markup, and competitor analysis.'],
            ['name' => 'Form Builder Elite', 'slug' => 'form-builder-elite', 'type' => ProductType::Plugin, 'description' => 'Drag-and-drop form builder with conditional logic, multi-step forms, and payment integrations.'],
            ['name' => 'Speed Optimizer', 'slug' => 'speed-optimizer', 'type' => ProductType::Plugin, 'description' => 'Boost your site performance with lazy loading, caching, and image optimization.'],
            ['name' => 'Security Shield', 'slug' => 'security-shield', 'type' => ProductType::Plugin, 'description' => 'Comprehensive security plugin with firewall, malware scanning, and login protection.'],
            ['name' => 'Backup Guardian', 'slug' => 'backup-guardian', 'type' => ProductType::Plugin, 'description' => 'Automated backups with cloud storage integration and one-click restore.'],
            ['name' => 'Analytics Dashboard', 'slug' => 'analytics-dashboard', 'type' => ProductType::Plugin, 'description' => 'Beautiful analytics dashboard with real-time stats and custom reports.'],
            ['name' => 'Email Marketing Suite', 'slug' => 'email-marketing-suite', 'type' => ProductType::Plugin, 'description' => 'Complete email marketing solution with automation, templates, and subscriber management.'],
            ['name' => 'Social Media Hub', 'slug' => 'social-media-hub', 'type' => ProductType::Plugin, 'description' => 'Schedule, publish, and analyze social media posts from one dashboard.'],

            // Themes
            ['name' => 'Corporate Pro', 'slug' => 'corporate-pro', 'type' => ProductType::Theme, 'description' => 'Professional business theme with elegant design and powerful customization options.'],
            ['name' => 'Portfolio Studio', 'slug' => 'portfolio-studio', 'type' => ProductType::Theme, 'description' => 'Showcase your work with this stunning portfolio theme featuring gallery layouts.'],
            ['name' => 'Blog starter', 'slug' => 'developer-blog', 'type' => ProductType::Theme, 'description' => 'Clean and minimalist blog theme optimized for readability and SEO.'],
            ['name' => 'Shop starter', 'slug' => 'developer-shop', 'type' => ProductType::Theme, 'description' => 'Modern eCommerce theme with WooCommerce integration and conversion optimization.'],
            ['name' => 'Magazine starter', 'slug' => 'developer-magazine', 'type' => ProductType::Theme, 'description' => 'News and magazine theme with multiple layouts and ad management.'],
            ['name' => 'Agency starter', 'slug' => 'developer-agency', 'type' => ProductType::Theme, 'description' => 'Creative agency theme with stunning animations and project showcases.'],
            ['name' => 'Restaurant starter', 'slug' => 'developer-restaurant', 'type' => ProductType::Theme, 'description' => 'Restaurant theme with menu management, reservations, and online ordering.'],

            // Source Code
            ['name' => 'Admin Panel Kit', 'slug' => 'admin-panel-kit', 'type' => ProductType::SourceCode, 'description' => 'Ready-to-use admin panel with user management, roles, and permissions.'],
            ['name' => 'API Starter Kit', 'slug' => 'api-starter-kit', 'type' => ProductType::SourceCode, 'description' => 'RESTful API boilerplate with authentication, rate limiting, and documentation.'],
            ['name' => 'Multi-tenant Kit', 'slug' => 'multi-tenant-kit', 'type' => ProductType::SourceCode, 'description' => 'Multi-tenant application boilerplate with subdomain and database separation.'],
            ['name' => 'E-commerce Kit', 'slug' => 'ecommerce-kit', 'type' => ProductType::SourceCode, 'description' => 'Complete e-commerce solution with cart, checkout, and order management.'],
            ['name' => 'CRM Starter Kit', 'slug' => 'crm-starter-kit', 'type' => ProductType::SourceCode, 'description' => 'Customer relationship management system with leads, deals, and pipelines.'],
        ];

        foreach ($additionalProducts as $index => $productData) {
            $product = Product::create([
                'name' => $productData['name'],
                'slug' => $productData['slug'],
                'description' => $productData['description'],
                'type' => $productData['type'],
                'version' => fake()->semver(),
                'download_url' => 'https://downloads.example.com/'.$productData['slug'].'.zip',
                'is_active' => fake()->boolean(90), // 90% chance of being active
                'sort_order' => $index + 4,
            ]);

            $this->createPackages($product, $this->getPackagesForType($productData['type']));
        }
    }

    /**
     * @return array<int, array{name: string, domain_limit: int|null, monthly: int, yearly: int}>
     */
    private function getPackagesForType(ProductType $type): array
    {
        return match ($type) {
            ProductType::Plugin => [
                ['name' => 'Single Site', 'domain_limit' => 1, 'monthly' => fake()->randomElement([15, 19, 25]), 'yearly' => fake()->randomElement([129, 149, 199])],
                ['name' => 'Multi Site', 'domain_limit' => 5, 'monthly' => fake()->randomElement([39, 49, 59]), 'yearly' => fake()->randomElement([349, 399, 449])],
                ['name' => 'Unlimited', 'domain_limit' => null, 'monthly' => fake()->randomElement([79, 99, 129]), 'yearly' => fake()->randomElement([699, 799, 999])],
            ],
            ProductType::Theme => [
                ['name' => 'Personal', 'domain_limit' => 1, 'monthly' => fake()->randomElement([9, 12, 15]), 'yearly' => fake()->randomElement([79, 99, 129])],
                ['name' => 'Developer', 'domain_limit' => 3, 'monthly' => fake()->randomElement([25, 29, 35]), 'yearly' => fake()->randomElement([199, 249, 299])],
                ['name' => 'Agency', 'domain_limit' => null, 'monthly' => fake()->randomElement([49, 59, 69]), 'yearly' => fake()->randomElement([449, 499, 599])],
            ],
            ProductType::SourceCode => [
                ['name' => 'Indie', 'domain_limit' => 1, 'monthly' => fake()->randomElement([59, 79, 99]), 'yearly' => fake()->randomElement([499, 599, 799])],
                ['name' => 'Startup', 'domain_limit' => 5, 'monthly' => fake()->randomElement([129, 149, 199]), 'yearly' => fake()->randomElement([999, 1199, 1499])],
                ['name' => 'Enterprise', 'domain_limit' => null, 'monthly' => fake()->randomElement([249, 299, 399]), 'yearly' => fake()->randomElement([1999, 2499, 2999])],
            ],
        };
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
