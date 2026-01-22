export interface Package {
    id: number;
    name: string;
    slug: string;
    description: string;
    domain_limit: number | null;
    monthly_price: string;
    yearly_price: string;
    features: string[];
}

export interface Product {
    id: number;
    name: string;
    slug: string;
    description: string;
    type: 'plugin' | 'theme' | 'source_code';
    type_label: string;
    version: string;
}

export interface ProductDetail extends Product {
    packages: Package[];
}

export interface CurrentSubscription {
    id: number;
    package_id: number | null;
    package_slug: string;
    package_name: string;
    stripe_price: string;
    is_yearly: boolean;
    ends_at: string | null;
    on_grace_period: boolean;
}
