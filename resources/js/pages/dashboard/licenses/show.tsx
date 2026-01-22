import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Check, Copy, Eye, EyeOff, Globe, Monitor, MoreHorizontal, Trash2 } from 'lucide-react';
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
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Progress } from '@/components/ui/progress';
import { Separator } from '@/components/ui/separator';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import DashboardLayout from '@/layouts/dashboard-layout';
import type { LicenseActivation, LicenseWithActivations } from '@/types';

interface LicenseShowProps {
    license: LicenseWithActivations;
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
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function formatShortDate(dateString: string | null): string {
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
            <code className="flex-1 rounded bg-muted px-3 py-2 font-mono text-sm">
                {isVisible ? licenseKey : `${licenseKey.slice(0, 12)}${'•'.repeat(32)}`}
            </code>
            <Button
                variant="outline"
                size="icon"
                onClick={() => setIsVisible(!isVisible)}
                title={isVisible ? 'Hide license key' : 'Show license key'}
            >
                {isVisible ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
            </Button>
            <Button variant="outline" size="icon" onClick={handleCopy} title="Copy license key">
                {copied ? <Check className="h-4 w-4 text-green-500" /> : <Copy className="h-4 w-4" />}
            </Button>
        </div>
    );
}

export default function LicenseShow({ license }: LicenseShowProps) {
    const [deactivateDialogOpen, setDeactivateDialogOpen] = useState(false);
    const [selectedActivation, setSelectedActivation] = useState<LicenseActivation | null>(null);
    const [processing, setProcessing] = useState(false);

    const activeActivations = license.activations.filter((a) => !a.deactivated_at);
    const inactiveActivations = license.activations.filter((a) => a.deactivated_at);

    const activationsUsed = activeActivations.length;
    const activationsLimit = license.domain_limit;
    const activationsPercentage = activationsLimit ? (activationsUsed / activationsLimit) * 100 : 0;

    const handleDeactivate = () => {
        if (!selectedActivation) return;

        setProcessing(true);
        router.delete(`/dashboard/licenses/${license.id}/activations/${selectedActivation.id}`, {
            onFinish: () => {
                setProcessing(false);
                setDeactivateDialogOpen(false);
                setSelectedActivation(null);
            },
        });
    };

    return (
        <DashboardLayout breadcrumbs={[{ label: 'Licenses', href: '/dashboard/licenses' }, { label: license.product.name }]}>
            <Head title={`License - ${license.product.name}`} />

            <div className="space-y-8">
                {/* Page Header */}
                <div className="flex items-center gap-4">
                    <Button asChild variant="ghost" size="icon">
                        <Link href="/dashboard/licenses">
                            <ArrowLeft className="h-4 w-4" />
                        </Link>
                    </Button>
                    <div>
                        <h1 className="flex items-center gap-2 text-3xl font-bold tracking-tight">
                            {license.product.name}
                            <Badge variant={getStatusBadgeVariant(license.status)}>{license.status_label}</Badge>
                        </h1>
                        <p className="text-muted-foreground">
                            {license.package.name} • Created {formatShortDate(license.created_at)}
                        </p>
                    </div>
                </div>

                {/* License Details Card */}
                <Card>
                    <CardHeader>
                        <CardTitle>License Details</CardTitle>
                        <CardDescription>Your license key and usage information</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-6">
                        {/* License Key */}
                        <div>
                            <label className="mb-2 block text-sm font-medium">License Key</label>
                            <LicenseKeyDisplay licenseKey={license.license_key} />
                        </div>

                        <Separator />

                        {/* Domain Usage */}
                        <div className="grid gap-6 md:grid-cols-2">
                            <div>
                                <label className="mb-2 block text-sm font-medium">Domain Activations</label>
                                <div className="space-y-2">
                                    <div className="flex items-center justify-between text-sm">
                                        <span className="text-muted-foreground">Used</span>
                                        <span className="font-medium">
                                            {activationsUsed}
                                            {activationsLimit !== null ? ` / ${activationsLimit}` : ' (Unlimited)'}
                                        </span>
                                    </div>
                                    {activationsLimit !== null && <Progress value={activationsPercentage} className="h-2" />}
                                </div>
                            </div>

                            <div className="space-y-3">
                                <div className="flex items-center justify-between text-sm">
                                    <span className="text-muted-foreground">Expires</span>
                                    <span className="font-medium">{formatShortDate(license.expires_at)}</span>
                                </div>
                                <div className="flex items-center justify-between text-sm">
                                    <span className="text-muted-foreground">Last Validated</span>
                                    <span className="font-medium">{formatShortDate(license.last_validated_at)}</span>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Active Activations */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Globe className="h-5 w-5" />
                            Active Domains ({activeActivations.length})
                        </CardTitle>
                        <CardDescription>Domains where this license is currently active</CardDescription>
                    </CardHeader>
                    <CardContent>
                        {activeActivations.length === 0 ? (
                            <div className="flex flex-col items-center justify-center py-8 text-center">
                                <Globe className="mb-4 h-12 w-12 text-muted-foreground" />
                                <h3 className="mb-2 font-semibold">No active domains</h3>
                                <p className="text-sm text-muted-foreground">This license hasn't been activated on any domain yet.</p>
                            </div>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Domain</TableHead>
                                        <TableHead>IP Address</TableHead>
                                        <TableHead>Activated</TableHead>
                                        <TableHead>Last Checked</TableHead>
                                        <TableHead className="w-[70px]"></TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {activeActivations.map((activation) => (
                                        <TableRow key={activation.id}>
                                            <TableCell className="font-medium">
                                                <div className="flex items-center gap-2">
                                                    <Globe className="h-4 w-4 text-muted-foreground" />
                                                    {activation.domain}
                                                </div>
                                            </TableCell>
                                            <TableCell className="font-mono text-sm">{activation.ip_address ?? 'N/A'}</TableCell>
                                            <TableCell>{formatDate(activation.activated_at)}</TableCell>
                                            <TableCell>{formatDate(activation.last_checked_at)}</TableCell>
                                            <TableCell>
                                                <DropdownMenu>
                                                    <DropdownMenuTrigger asChild>
                                                        <Button variant="ghost" size="icon">
                                                            <MoreHorizontal className="h-4 w-4" />
                                                        </Button>
                                                    </DropdownMenuTrigger>
                                                    <DropdownMenuContent align="end">
                                                        <DropdownMenuItem
                                                            className="text-destructive"
                                                            onClick={() => {
                                                                setSelectedActivation(activation);
                                                                setDeactivateDialogOpen(true);
                                                            }}
                                                        >
                                                            <Trash2 className="mr-2 h-4 w-4" />
                                                            Deactivate
                                                        </DropdownMenuItem>
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

                {/* Inactive Activations (History) */}
                {inactiveActivations.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-muted-foreground">
                                <Monitor className="h-5 w-5" />
                                Deactivated Domains ({inactiveActivations.length})
                            </CardTitle>
                            <CardDescription>Previously activated domains that have been deactivated</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Domain</TableHead>
                                        <TableHead>IP Address</TableHead>
                                        <TableHead>Activated</TableHead>
                                        <TableHead>Deactivated</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {inactiveActivations.map((activation) => (
                                        <TableRow key={activation.id} className="text-muted-foreground">
                                            <TableCell>
                                                <div className="flex items-center gap-2">
                                                    <Globe className="h-4 w-4" />
                                                    {activation.domain}
                                                </div>
                                            </TableCell>
                                            <TableCell className="font-mono text-sm">{activation.ip_address ?? 'N/A'}</TableCell>
                                            <TableCell>{formatDate(activation.activated_at)}</TableCell>
                                            <TableCell>{formatDate(activation.deactivated_at)}</TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                )}
            </div>

            {/* Deactivate Dialog */}
            <AlertDialog open={deactivateDialogOpen} onOpenChange={setDeactivateDialogOpen}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Deactivate Domain?</AlertDialogTitle>
                        <AlertDialogDescription>
                            This will deactivate the license on <strong>{selectedActivation?.domain}</strong>. The domain will need to reactivate the
                            license to continue using it.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel disabled={processing}>Cancel</AlertDialogCancel>
                        <AlertDialogAction
                            onClick={handleDeactivate}
                            disabled={processing}
                            className="text-destructive-foreground bg-destructive hover:bg-destructive/90"
                        >
                            {processing ? 'Deactivating...' : 'Deactivate'}
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </DashboardLayout>
    );
}

LicenseShow.layout = null;
