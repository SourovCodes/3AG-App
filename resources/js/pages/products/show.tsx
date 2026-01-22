import { Head, Link, router } from '@inertiajs/react';
import { CheckIcon, CreditCardIcon } from 'lucide-react';
import { useState } from 'react';

import { index as productsIndex, subscribe, swap } from '@/actions/App/Http/Controllers/ProductController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Switch } from '@/components/ui/switch';
import { cn } from '@/lib/utils';
import type { CurrentSubscription, Package, ProductDetail } from '@/types';

interface Props {
    product: ProductDetail;
    currentSubscription: CurrentSubscription | null;
}

function formatPrice(price: string): string {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(parseFloat(price));
}

function PricingCard({
    pkg,
    isYearly,
    isPopular,
    currentSubscription,
}: {
    pkg: Package;
    isYearly: boolean;
    isPopular: boolean;
    currentSubscription: CurrentSubscription | null;
}) {
    const price = isYearly ? pkg.yearly_price : pkg.monthly_price;
    const period = isYearly ? '/year' : '/month';

    const isCurrentPlan = currentSubscription?.package_id === pkg.id;
    const isCurrentBillingInterval = currentSubscription?.is_yearly === isYearly;
    const isExactCurrentPlan = isCurrentPlan && isCurrentBillingInterval;
    const hasSubscription = currentSubscription !== null;

    const handleSubscribe = () => {
        router.post(subscribe.url({ package: pkg.id }), {
            billing_interval: isYearly ? 'yearly' : 'monthly',
        });
    };

    const handleSwap = () => {
        router.post(swap.url({ package: pkg.id }), {
            billing_interval: isYearly ? 'yearly' : 'monthly',
        });
    };

    const getButtonText = () => {
        if (isExactCurrentPlan) {
            return 'Current Plan';
        }
        if (hasSubscription) {
            if (isCurrentPlan) {
                return isYearly ? 'Switch to Yearly' : 'Switch to Monthly';
            }
            return 'Switch Plan';
        }
        return 'Get Started';
    };

    const handleClick = () => {
        if (isExactCurrentPlan) return;
        if (hasSubscription) {
            handleSwap();
        } else {
            handleSubscribe();
        }
    };

    return (
        <Card className={cn('relative flex flex-col', isPopular && 'border-primary shadow-lg', isCurrentPlan && 'ring-2 ring-primary')}>
            {isCurrentPlan && (
                <div className="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-green-600 px-3 py-1 text-xs font-medium text-white">
                    Your Plan
                </div>
            )}
            {!isCurrentPlan && isPopular && (
                <div className="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-primary px-3 py-1 text-xs font-medium text-primary-foreground">
                    Most Popular
                </div>
            )}
            <CardHeader className="text-center">
                <CardTitle className="text-xl">{pkg.name}</CardTitle>
                <CardDescription>{pkg.description}</CardDescription>
                <div className="mt-4">
                    <span className="text-4xl font-bold">{formatPrice(price)}</span>
                    <span className="text-muted-foreground">{period}</span>
                </div>
                {isYearly && (
                    <p className="text-sm text-muted-foreground">
                        Save {formatPrice(String(parseFloat(pkg.monthly_price) * 12 - parseFloat(pkg.yearly_price)))}
                        /year
                    </p>
                )}
            </CardHeader>
            <CardContent className="flex-1">
                <ul className="space-y-3">
                    {pkg.features.map((feature, index) => (
                        <li key={index} className="flex items-start gap-2">
                            <CheckIcon className="mt-0.5 h-4 w-4 shrink-0 text-primary" />
                            <span className="text-sm">{feature}</span>
                        </li>
                    ))}
                    <li className="flex items-start gap-2">
                        <CheckIcon className="mt-0.5 h-4 w-4 shrink-0 text-primary" />
                        <span className="text-sm">
                            {pkg.domain_limit ? `${pkg.domain_limit} site license${pkg.domain_limit > 1 ? 's' : ''}` : 'Unlimited sites'}
                        </span>
                    </li>
                </ul>
            </CardContent>
            <CardFooter>
                <Button
                    onClick={handleClick}
                    className="w-full"
                    variant={isExactCurrentPlan ? 'secondary' : isPopular ? 'default' : 'outline'}
                    disabled={isExactCurrentPlan}
                >
                    {getButtonText()}
                </Button>
            </CardFooter>
        </Card>
    );
}

export default function ProductShow({ product, currentSubscription }: Props) {
    const [isYearly, setIsYearly] = useState(currentSubscription?.is_yearly ?? true);
    const packages = product.packages ?? [];

    return (
        <>
            <Head title={product.name} />

            <div className="container mx-auto px-4 py-12">
                {/* Breadcrumb */}
                <nav className="mb-8 text-sm text-muted-foreground">
                    <Link href={productsIndex.url()} className="hover:text-foreground">
                        Products
                    </Link>
                    <span className="mx-2">/</span>
                    <span className="text-foreground">{product.name}</span>
                </nav>

                {/* Current Subscription Banner */}
                {currentSubscription && (
                    <div className="mb-8 rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-900 dark:bg-green-950">
                        <div className="flex items-center gap-3">
                            <CreditCardIcon className="h-5 w-5 text-green-600 dark:text-green-400" />
                            <div>
                                <p className="font-medium text-green-800 dark:text-green-200">
                                    You're subscribed to {currentSubscription.package_name}
                                    <span className="ml-2 text-sm font-normal text-green-600 dark:text-green-400">
                                        ({currentSubscription.is_yearly ? 'Yearly' : 'Monthly'})
                                    </span>
                                </p>
                                {currentSubscription.on_grace_period && (
                                    <p className="text-sm text-green-600 dark:text-green-400">
                                        Your subscription is cancelled but active until the end of the billing period.
                                    </p>
                                )}
                            </div>
                        </div>
                    </div>
                )}

                {/* Product Header */}
                <div className="mb-12 text-center">
                    <Badge variant="secondary" className="mb-4">
                        {product.type_label}
                    </Badge>
                    <h1 className="mb-4 text-4xl font-bold">{product.name}</h1>
                    <p className="mx-auto max-w-2xl text-lg text-muted-foreground">{product.description}</p>
                    <p className="mt-2 text-sm text-muted-foreground">Current version: {product.version}</p>
                </div>

                {/* Pricing Toggle */}
                <div className="mb-8 flex items-center justify-center gap-4">
                    <span className={cn('text-sm', !isYearly && 'font-medium')}>Monthly</span>
                    <Switch checked={isYearly} onCheckedChange={setIsYearly} />
                    <span className={cn('text-sm', isYearly && 'font-medium')}>
                        Yearly <span className="text-xs text-primary">(Save up to 30%)</span>
                    </span>
                </div>

                {/* Pricing Cards */}
                <div
                    className={cn(
                        'mx-auto grid max-w-5xl gap-6',
                        packages.length === 1 && 'max-w-md',
                        packages.length === 2 && 'max-w-2xl md:grid-cols-2',
                        packages.length >= 3 && 'md:grid-cols-3',
                    )}
                >
                    {packages.map((pkg, index) => (
                        <PricingCard
                            key={pkg.id}
                            pkg={pkg}
                            isYearly={isYearly}
                            isPopular={packages.length >= 3 ? index === 1 : index === packages.length - 1}
                            currentSubscription={currentSubscription}
                        />
                    ))}
                </div>

                {packages.length === 0 && (
                    <div className="py-12 text-center">
                        <p className="text-muted-foreground">No pricing packages available at the moment.</p>
                    </div>
                )}

                {/* Back Link */}
                <div className="mt-12 text-center">
                    <Link href={productsIndex.url()}>
                        <Button variant="ghost">← Back to all products</Button>
                    </Link>
                </div>
            </div>
        </>
    );
}
