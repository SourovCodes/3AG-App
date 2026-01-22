import { Head, Link, router } from '@inertiajs/react';
import { AlertCircle, CreditCard, ExternalLink, MoreHorizontal } from 'lucide-react';
import { useState } from 'react';

import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import DashboardLayout from '@/layouts/dashboard-layout';
import type { Subscription } from '@/types';

interface SubscriptionsIndexProps {
    subscriptions: Subscription[];
    billing_portal_url: string | null;
}

function getStatusBadgeVariant(status: string) {
    switch (status) {
        case 'active':
        case 'trialing':
            return 'default' as const;
        case 'canceled':
        case 'incomplete':
        case 'incomplete_expired':
            return 'destructive' as const;
        case 'past_due':
        case 'unpaid':
            return 'secondary' as const;
        default:
            return 'outline' as const;
    }
}

function formatDate(dateString: string | null): string {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
}

export default function SubscriptionsIndex({ subscriptions, billing_portal_url }: SubscriptionsIndexProps) {
    const [cancelDialogOpen, setCancelDialogOpen] = useState(false);
    const [selectedSubscription, setSelectedSubscription] = useState<Subscription | null>(null);
    const [processing, setProcessing] = useState(false);

    const handleCancelSubscription = () => {
        if (!selectedSubscription) return;

        setProcessing(true);
        router.post(
            `/dashboard/subscriptions/${selectedSubscription.id}/cancel`,
            {},
            {
                onFinish: () => {
                    setProcessing(false);
                    setCancelDialogOpen(false);
                    setSelectedSubscription(null);
                },
            },
        );
    };

    const handleResumeSubscription = (subscription: Subscription) => {
        router.post(`/dashboard/subscriptions/${subscription.id}/resume`);
    };

    return (
        <DashboardLayout breadcrumbs={[{ label: 'Subscriptions' }]}>
            <Head title="Subscriptions" />

            <div className="space-y-8">
                {/* Page Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Subscriptions</h1>
                        <p className="text-muted-foreground">Manage your subscription plans and billing settings.</p>
                    </div>
                    {billing_portal_url && (
                        <Button asChild variant="outline">
                            <a href={billing_portal_url} target="_blank" rel="noopener noreferrer">
                                <CreditCard className="mr-2 h-4 w-4" />
                                Manage Billing
                                <ExternalLink className="ml-2 h-4 w-4" />
                            </a>
                        </Button>
                    )}
                </div>

                {/* Subscriptions List */}
                <Card>
                    <CardHeader>
                        <CardTitle>Your Subscriptions</CardTitle>
                        <CardDescription>View and manage all your active and past subscriptions.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        {subscriptions.length === 0 ? (
                            <div className="flex flex-col items-center justify-center py-12 text-center">
                                <CreditCard className="mb-4 h-16 w-16 text-muted-foreground" />
                                <h3 className="mb-2 text-lg font-semibold">No subscriptions yet</h3>
                                <p className="mb-6 max-w-md text-muted-foreground">
                                    You don't have any active subscriptions. Browse our products and choose a plan that fits your needs.
                                </p>
                                <Button asChild>
                                    <Link href="/products">Browse Products</Link>
                                </Button>
                            </div>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Product / Package</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Started</TableHead>
                                        <TableHead>Next Billing</TableHead>
                                        <TableHead className="w-[70px]"></TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {subscriptions.map((subscription) => (
                                        <TableRow key={subscription.id}>
                                            <TableCell>
                                                <div>
                                                    <div className="font-medium">{subscription.product_name ?? 'Unknown Product'}</div>
                                                    <div className="text-sm text-muted-foreground">
                                                        {subscription.package_name ?? 'Unknown Package'}
                                                    </div>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex flex-col gap-1">
                                                    <Badge variant={getStatusBadgeVariant(subscription.stripe_status)}>
                                                        {subscription.stripe_status}
                                                    </Badge>
                                                    {subscription.is_on_trial && (
                                                        <span className="text-xs text-muted-foreground">
                                                            Trial ends {formatDate(subscription.trial_ends_at)}
                                                        </span>
                                                    )}
                                                    {subscription.is_on_grace_period && (
                                                        <span className="text-xs text-orange-500">Cancels {formatDate(subscription.ends_at)}</span>
                                                    )}
                                                </div>
                                            </TableCell>
                                            <TableCell>{formatDate(subscription.created_at)}</TableCell>
                                            <TableCell>
                                                {subscription.is_canceled ? 'Cancelled' : formatDate(subscription.current_period_end ?? null)}
                                            </TableCell>
                                            <TableCell>
                                                <DropdownMenu>
                                                    <DropdownMenuTrigger asChild>
                                                        <Button variant="ghost" size="icon">
                                                            <MoreHorizontal className="h-4 w-4" />
                                                            <span className="sr-only">Actions</span>
                                                        </Button>
                                                    </DropdownMenuTrigger>
                                                    <DropdownMenuContent align="end">
                                                        {billing_portal_url && (
                                                            <DropdownMenuItem asChild>
                                                                <a href={billing_portal_url} target="_blank" rel="noopener noreferrer">
                                                                    Manage in Stripe
                                                                </a>
                                                            </DropdownMenuItem>
                                                        )}
                                                        {subscription.is_on_grace_period ? (
                                                            <DropdownMenuItem onClick={() => handleResumeSubscription(subscription)}>
                                                                Resume Subscription
                                                            </DropdownMenuItem>
                                                        ) : (
                                                            subscription.is_active && (
                                                                <>
                                                                    <DropdownMenuSeparator />
                                                                    <DropdownMenuItem
                                                                        className="text-destructive"
                                                                        onClick={() => {
                                                                            setSelectedSubscription(subscription);
                                                                            setCancelDialogOpen(true);
                                                                        }}
                                                                    >
                                                                        Cancel Subscription
                                                                    </DropdownMenuItem>
                                                                </>
                                                            )
                                                        )}
                                                    </DropdownMenuContent>
                                                </DropdownMenu>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                </Card>

                {/* Info Card */}
                <Card className="border-blue-200 bg-blue-50 dark:border-blue-900 dark:bg-blue-950">
                    <CardHeader className="pb-3">
                        <CardTitle className="flex items-center gap-2 text-blue-900 dark:text-blue-100">
                            <AlertCircle className="h-5 w-5" />
                            Billing Information
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="text-sm text-blue-800 dark:text-blue-200">
                        <p>
                            Your billing is managed securely through Stripe. Click "Manage Billing" to update your payment method, view invoices, or
                            download receipts.
                        </p>
                    </CardContent>
                </Card>
            </div>

            {/* Cancel Subscription Dialog */}
            <AlertDialog open={cancelDialogOpen} onOpenChange={setCancelDialogOpen}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Cancel Subscription?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Are you sure you want to cancel your subscription to <strong>{selectedSubscription?.product_name}</strong>? You'll
                            continue to have access until the end of your current billing period.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel disabled={processing}>Keep Subscription</AlertDialogCancel>
                        <AlertDialogAction
                            onClick={handleCancelSubscription}
                            disabled={processing}
                            className="text-destructive-foreground bg-destructive hover:bg-destructive/90"
                        >
                            {processing ? 'Cancelling...' : 'Yes, Cancel'}
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </DashboardLayout>
    );
}

SubscriptionsIndex.layout = null;
