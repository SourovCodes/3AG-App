import { Head, Link } from '@inertiajs/react';
import { ExternalLink, FileText, Receipt } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import DashboardLayout from '@/layouts/dashboard-layout';
import type { Invoice } from '@/types';

interface InvoicesIndexProps {
    invoices: Invoice[];
}

function getStatusBadgeVariant(status: string) {
    switch (status) {
        case 'paid':
            return 'default' as const;
        case 'open':
            return 'secondary' as const;
        case 'draft':
            return 'outline' as const;
        case 'void':
        case 'uncollectible':
            return 'destructive' as const;
        default:
            return 'outline' as const;
    }
}

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
}

export default function InvoicesIndex({ invoices }: InvoicesIndexProps) {
    return (
        <DashboardLayout breadcrumbs={[{ label: 'Invoices' }]}>
            <Head title="Invoices" />

            <div className="space-y-8">
                {/* Page Header */}
                <div>
                    <h1 className="text-3xl font-bold tracking-tight">Invoices</h1>
                    <p className="text-muted-foreground">View and download your billing history and invoices.</p>
                </div>

                {/* Invoices List */}
                <Card>
                    <CardHeader>
                        <CardTitle>Billing History</CardTitle>
                        <CardDescription>All your past invoices and payment receipts.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        {invoices.length === 0 ? (
                            <div className="flex flex-col items-center justify-center py-12 text-center">
                                <Receipt className="mb-4 h-16 w-16 text-muted-foreground" />
                                <h3 className="mb-2 text-lg font-semibold">No invoices yet</h3>
                                <p className="mb-6 max-w-md text-muted-foreground">
                                    You don't have any invoices yet. Once you make a purchase or subscribe to a plan, your invoices will appear here.
                                </p>
                                <Button asChild>
                                    <Link href="/products">Browse Products</Link>
                                </Button>
                            </div>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Invoice</TableHead>
                                        <TableHead>Date</TableHead>
                                        <TableHead>Amount</TableHead>
                                        <TableHead>Paid</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="w-[100px]">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {invoices.map((invoice) => (
                                        <TableRow key={invoice.id}>
                                            <TableCell>
                                                <div className="flex items-center gap-2">
                                                    <FileText className="h-4 w-4 text-muted-foreground" />
                                                    <span className="font-mono text-sm">{invoice.id.substring(0, 20)}...</span>
                                                </div>
                                            </TableCell>
                                            <TableCell>{formatDate(invoice.date)}</TableCell>
                                            <TableCell className="font-medium">
                                                {invoice.is_credit_note ? (
                                                    <span className="text-green-600 dark:text-green-400">+{invoice.credit_amount} credit</span>
                                                ) : (
                                                    invoice.subtotal
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                <div className="space-y-0.5">
                                                    <div className="font-medium">{invoice.amount_paid}</div>
                                                    {invoice.credit_applied && (
                                                        <div className="text-xs text-muted-foreground">{invoice.credit_applied} credit used</div>
                                                    )}
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant={getStatusBadgeVariant(invoice.status)}>{invoice.status}</Badge>
                                            </TableCell>
                                            <TableCell>
                                                {invoice.hosted_invoice_url ? (
                                                    <Button asChild variant="outline" size="sm">
                                                        <a href={invoice.hosted_invoice_url} target="_blank" rel="noopener noreferrer">
                                                            <ExternalLink className="mr-1 h-3 w-3" />
                                                            View
                                                        </a>
                                                    </Button>
                                                ) : (
                                                    <span className="text-sm text-muted-foreground">—</span>
                                                )}
                                            </TableCell>
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

InvoicesIndex.layout = null;
