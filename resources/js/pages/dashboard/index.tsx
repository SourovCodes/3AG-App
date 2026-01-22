import { Head, Link } from '@inertiajs/react';
import { CreditCard, ExternalLink, Key, RefreshCw, TrendingUp } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import DashboardLayout from '@/layouts/dashboard-layout';
import type { DashboardOverview } from '@/types';

interface OverviewProps {
    user: DashboardOverview['user'];
    stats: DashboardOverview['stats'];
    recent_licenses: DashboardOverview['recent_licenses'];
    subscriptions: DashboardOverview['subscriptions'];
}

function getStatusBadgeVariant(status: string) {
    switch (status) {
        case 'active':
            return 'default';
        case 'suspended':
            return 'secondary';
        case 'expired':
            return 'outline';
        case 'cancelled':
            return 'destructive';
        default:
            return 'outline';
    }
}

function getSubscriptionBadgeVariant(status: string) {
    switch (status) {
        case 'active':
        case 'trialing':
            return 'default';
        case 'canceled':
        case 'incomplete':
        case 'incomplete_expired':
            return 'destructive';
        case 'past_due':
        case 'unpaid':
            return 'secondary';
        default:
            return 'outline';
    }
}

export default function Overview({ stats, recent_licenses, subscriptions }: OverviewProps) {
    return (
        <DashboardLayout>
            <Head title="Dashboard" />

            <div className="space-y-8">
                {/* Page Header */}
                <div>
                    <h1 className="text-3xl font-bold tracking-tight">Dashboard</h1>
                    <p className="text-muted-foreground">Welcome back! Here's an overview of your account.</p>
                </div>

                {/* Stats Cards */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium">Active Subscriptions</CardTitle>
                            <CreditCard className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.active_subscriptions}</div>
                            <p className="text-xs text-muted-foreground">{stats.total_subscriptions} total</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium">Active Licenses</CardTitle>
                            <Key className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.active_licenses}</div>
                            <p className="text-xs text-muted-foreground">{stats.total_licenses} total</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium">Total Activations</CardTitle>
                            <RefreshCw className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.total_activations}</div>
                            <p className="text-xs text-muted-foreground">across all licenses</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium">Account Status</CardTitle>
                            <TrendingUp className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.active_subscriptions > 0 ? 'Active' : 'Free'}</div>
                            <p className="text-xs text-muted-foreground">
                                {stats.active_subscriptions > 0 ? 'Subscribed' : 'No active subscriptions'}
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {/* Active Subscriptions */}
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between">
                        <div>
                            <CardTitle>Active Subscriptions</CardTitle>
                            <CardDescription>Manage your subscription plans and billing</CardDescription>
                        </div>
                        <Button asChild variant="outline" size="sm">
                            <Link href="/dashboard/subscriptions">
                                View All
                                <ExternalLink className="ml-2 h-4 w-4" />
                            </Link>
                        </Button>
                    </CardHeader>
                    <CardContent>
                        {subscriptions.length === 0 ? (
                            <div className="flex flex-col items-center justify-center py-8 text-center">
                                <CreditCard className="mb-4 h-12 w-12 text-muted-foreground" />
                                <h3 className="mb-2 font-semibold">No active subscriptions</h3>
                                <p className="mb-4 text-sm text-muted-foreground">Subscribe to a plan to get started with our products.</p>
                                <Button asChild>
                                    <Link href="/products">Browse Products</Link>
                                </Button>
                            </div>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Product</TableHead>
                                        <TableHead>Package</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Next Billing</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {subscriptions.slice(0, 5).map((subscription) => (
                                        <TableRow key={subscription.id}>
                                            <TableCell className="font-medium">{subscription.product_name ?? 'N/A'}</TableCell>
                                            <TableCell>{subscription.package_name ?? 'N/A'}</TableCell>
                                            <TableCell>
                                                <Badge variant={getSubscriptionBadgeVariant(subscription.stripe_status)}>
                                                    {subscription.stripe_status}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                {subscription.current_period_end
                                                    ? new Date(subscription.current_period_end).toLocaleDateString()
                                                    : 'N/A'}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                </Card>

                {/* Recent Licenses */}
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between">
                        <div>
                            <CardTitle>Recent Licenses</CardTitle>
                            <CardDescription>Your most recently created license keys</CardDescription>
                        </div>
                        <Button asChild variant="outline" size="sm">
                            <Link href="/dashboard/licenses">
                                View All
                                <ExternalLink className="ml-2 h-4 w-4" />
                            </Link>
                        </Button>
                    </CardHeader>
                    <CardContent>
                        {recent_licenses.length === 0 ? (
                            <div className="flex flex-col items-center justify-center py-8 text-center">
                                <Key className="mb-4 h-12 w-12 text-muted-foreground" />
                                <h3 className="mb-2 font-semibold">No licenses yet</h3>
                                <p className="text-sm text-muted-foreground">Licenses will appear here once you subscribe to a product.</p>
                            </div>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Product</TableHead>
                                        <TableHead>License Key</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Activations</TableHead>
                                        <TableHead>Expires</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {recent_licenses.map((license) => (
                                        <TableRow key={license.id}>
                                            <TableCell className="font-medium">{license.product.name}</TableCell>
                                            <TableCell>
                                                <code className="rounded bg-muted px-2 py-1 font-mono text-sm">
                                                    {license.license_key.slice(0, 12)}...
                                                </code>
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant={getStatusBadgeVariant(license.status)}>{license.status_label}</Badge>
                                            </TableCell>
                                            <TableCell>
                                                {license.active_activations_count}
                                                {license.domain_limit !== null && ` / ${license.domain_limit}`}
                                            </TableCell>
                                            <TableCell>{license.expires_at ? new Date(license.expires_at).toLocaleDateString() : 'Never'}</TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                </Card>
            </div>
        </DashboardLayout>
    );
}

Overview.layout = null;
