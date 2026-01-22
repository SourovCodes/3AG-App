import type { User } from './auth';

export interface Subscription {
    id: number;
    stripe_id: string;
    stripe_status: string;
    stripe_price: string;
    quantity: number;
    trial_ends_at: string | null;
    ends_at: string | null;
    created_at: string;
    updated_at: string;
    // Computed/additional fields
    product_name?: string;
    package_name?: string;
    is_active?: boolean;
    is_on_trial?: boolean;
    is_canceled?: boolean;
    is_on_grace_period?: boolean;
    current_period_end?: string;
}

export interface License {
    id: number;
    license_key: string;
    domain_limit: number | null;
    status: 'active' | 'suspended' | 'expired' | 'cancelled';
    status_label: string;
    expires_at: string | null;
    last_validated_at: string | null;
    created_at: string;
    updated_at: string;
    product: {
        id: number;
        name: string;
        slug: string;
    };
    package: {
        id: number;
        name: string;
        slug: string;
    };
    activations_count: number;
    active_activations_count: number;
}

export interface LicenseActivation {
    id: number;
    license_id: number;
    domain: string;
    ip_address: string | null;
    user_agent: string | null;
    last_checked_at: string | null;
    activated_at: string;
    deactivated_at: string | null;
    created_at: string;
    updated_at: string;
}

export interface LicenseWithActivations extends License {
    activations: LicenseActivation[];
}

export interface DashboardStats {
    total_subscriptions: number;
    active_subscriptions: number;
    total_licenses: number;
    active_licenses: number;
    total_activations: number;
    credit_balance: string;
}

export interface DashboardOverview {
    user: User;
    stats: DashboardStats;
    recent_licenses: License[];
    subscriptions: Subscription[];
}

export interface Invoice {
    id: string;
    date: string;
    total: string;
    status: string;
    hosted_invoice_url: string | null;
}
