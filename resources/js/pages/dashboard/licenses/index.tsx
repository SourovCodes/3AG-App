import { Head, Link, router } from '@inertiajs/react';
import { Check, Copy, Eye, EyeOff, Globe, Key, MoreHorizontal, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

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
import { Progress } from '@/components/ui/progress';
import DashboardLayout from '@/layouts/dashboard-layout';
import type { License } from '@/types';

interface LicensesIndexProps {
    licenses: License[];
}

function getStatusBadgeVariant(status: string) {
    switch (status) {
        case 'active':
            return 'default' as const;
        case 'suspended':
            return 'secondary' as const;
        case 'expired':
            return 'outline' as const;
        case 'cancelled':
            return 'destructive' as const;
        default:
            return 'outline' as const;
    }
}

function formatDate(dateString: string | null): string {
    if (!dateString) return 'Never';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function LicenseKeyDisplay({ licenseKey }: { licenseKey: string }) {
    const [isVisible, setIsVisible] = useState(false);
    const [copied, setCopied] = useState(false);

    const handleCopy = async () => {
        await navigator.clipboard.writeText(licenseKey);
        setCopied(true);
        toast.success('License key copied to clipboard');
        setTimeout(() => setCopied(false), 2000);
    };

    return (
        <div className="flex items-center gap-2">
            <code className="rounded bg-muted px-2 py-1 font-mono text-sm">
                {isVisible ? licenseKey : `${licenseKey.slice(0, 8)}${'•'.repeat(24)}`}
            </code>
            <Button variant="ghost" size="icon" className="h-7 w-7" onClick={() => setIsVisible(!isVisible)}>
                {isVisible ? <EyeOff className="h-3.5 w-3.5" /> : <Eye className="h-3.5 w-3.5" />}
            </Button>
            <Button variant="ghost" size="icon" className="h-7 w-7" onClick={handleCopy}>
                {copied ? <Check className="h-3.5 w-3.5 text-green-500" /> : <Copy className="h-3.5 w-3.5" />}
            </Button>
        </div>
    );
}

export default function LicensesIndex({ licenses }: LicensesIndexProps) {
    const [deactivateDialogOpen, setDeactivateDialogOpen] = useState(false);
    const [selectedLicense, setSelectedLicense] = useState<License | null>(null);
    const [processing, setProcessing] = useState(false);

    const handleDeactivateAll = () => {
        if (!selectedLicense) return;

        setProcessing(true);
        router.post(
            `/dashboard/licenses/${selectedLicense.id}/deactivate-all`,
            {},
            {
                onFinish: () => {
                    setProcessing(false);
                    setDeactivateDialogOpen(false);
                    setSelectedLicense(null);
                },
            },
        );
    };

    return (
        <DashboardLayout breadcrumbs={[{ label: 'Licenses' }]}>
            <Head title="Licenses" />

            <div className="space-y-8">
                {/* Page Header */}
                <div>
                    <h1 className="text-3xl font-bold tracking-tight">Licenses</h1>
                    <p className="text-muted-foreground">View and manage your software licenses and domain activations.</p>
                </div>

                {/* Licenses Grid */}
                {licenses.length === 0 ? (
                    <Card>
                        <CardContent className="flex flex-col items-center justify-center py-12 text-center">
                            <Key className="mb-4 h-16 w-16 text-muted-foreground" />
                            <h3 className="mb-2 text-lg font-semibold">No licenses yet</h3>
                            <p className="mb-6 max-w-md text-muted-foreground">
                                You don't have any licenses. Subscribe to a product to receive your license keys.
                            </p>
                            <Button asChild>
                                <Link href="/products">Browse Products</Link>
                            </Button>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid gap-6">
                        {licenses.map((license) => {
                            const activationsUsed = license.active_activations_count;
                            const activationsLimit = license.domain_limit;
                            const activationsPercentage = activationsLimit ? (activationsUsed / activationsLimit) * 100 : 0;

                            return (
                                <Card key={license.id}>
                                    <CardHeader className="pb-4">
                                        <div className="flex items-start justify-between">
                                            <div>
                                                <CardTitle className="flex items-center gap-2">
                                                    {license.product.name}
                                                    <Badge variant={getStatusBadgeVariant(license.status)}>{license.status_label}</Badge>
                                                </CardTitle>
                                                <CardDescription>
                                                    {license.package.name} • Created {formatDate(license.created_at)}
                                                </CardDescription>
                                            </div>
                                            <DropdownMenu>
                                                <DropdownMenuTrigger asChild>
                                                    <Button variant="ghost" size="icon">
                                                        <MoreHorizontal className="h-4 w-4" />
                                                    </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent align="end">
                                                    <DropdownMenuItem asChild>
                                                        <Link href={`/dashboard/licenses/${license.id}`}>View Details</Link>
                                                    </DropdownMenuItem>
                                                    {license.active_activations_count > 0 && (
                                                        <>
                                                            <DropdownMenuSeparator />
                                                            <DropdownMenuItem
                                                                className="text-destructive"
                                                                onClick={() => {
                                                                    setSelectedLicense(license);
                                                                    setDeactivateDialogOpen(true);
                                                                }}
                                                            >
                                                                <Trash2 className="mr-2 h-4 w-4" />
                                                                Deactivate All Domains
                                                            </DropdownMenuItem>
                                                        </>
                                                    )}
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </div>
                                    </CardHeader>
                                    <CardContent className="space-y-4">
                                        {/* License Key */}
                                        <div>
                                            <label className="mb-2 block text-sm font-medium">License Key</label>
                                            <LicenseKeyDisplay licenseKey={license.license_key} />
                                        </div>

                                        {/* Domain Usage */}
                                        <div>
                                            <div className="mb-2 flex items-center justify-between text-sm">
                                                <span className="font-medium">Domain Activations</span>
                                                <span className="text-muted-foreground">
                                                    {activationsUsed}
                                                    {activationsLimit !== null ? ` / ${activationsLimit}` : ' (Unlimited)'}
                                                </span>
                                            </div>
                                            {activationsLimit !== null && <Progress value={activationsPercentage} className="h-2" />}
                                        </div>

                                        {/* Expiry Info */}
                                        <div className="flex items-center gap-4 text-sm text-muted-foreground">
                                            <span>Expires: {formatDate(license.expires_at)}</span>
                                            {license.last_validated_at && <span>Last validated: {formatDate(license.last_validated_at)}</span>}
                                        </div>

                                        {/* View Details Button */}
                                        <div className="pt-2">
                                            <Button asChild variant="outline" size="sm">
                                                <Link href={`/dashboard/licenses/${license.id}`}>
                                                    <Globe className="mr-2 h-4 w-4" />
                                                    Manage Activations ({activationsUsed})
                                                </Link>
                                            </Button>
                                        </div>
                                    </CardContent>
                                </Card>
                            );
                        })}
                    </div>
                )}
            </div>

            {/* Deactivate All Dialog */}
            <AlertDialog open={deactivateDialogOpen} onOpenChange={setDeactivateDialogOpen}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Deactivate All Domains?</AlertDialogTitle>
                        <AlertDialogDescription>
                            This will deactivate all {selectedLicense?.active_activations_count} active domain activations for this license. The
                            domains will need to be reactivated to use the license again.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel disabled={processing}>Cancel</AlertDialogCancel>
                        <AlertDialogAction
                            onClick={handleDeactivateAll}
                            disabled={processing}
                            className="text-destructive-foreground bg-destructive hover:bg-destructive/90"
                        >
                            {processing ? 'Deactivating...' : 'Deactivate All'}
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </DashboardLayout>
    );
}

LicensesIndex.layout = null;
