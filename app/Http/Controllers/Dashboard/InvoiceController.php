<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Cashier\Cashier;

class InvoiceController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $invoices = [];

        if ($user->hasStripeId()) {
            $invoices = $user->invoices()->map(function ($invoice) {
                $stripeInvoice = $invoice->asStripeInvoice();
                $rawSubtotal = $stripeInvoice->subtotal ?? 0;
                $rawAppliedBalance = $invoice->rawAppliedBalance();

                // Determine if this is a credit note (negative subtotal)
                $isCreditNote = $rawSubtotal < 0;

                // Credit applied is negative in Stripe (negative balance = customer credit)
                // Only show for regular invoices, not credit notes
                $creditApplied = null;
                if (! $isCreditNote && $rawAppliedBalance < 0) {
                    $creditApplied = Cashier::formatAmount(abs($rawAppliedBalance));
                }

                return [
                    'id' => $invoice->id,
                    'date' => $invoice->date()->toIso8601String(),
                    'subtotal' => $isCreditNote ? null : $invoice->subtotal(),
                    'amount_paid' => $invoice->amountPaid(),
                    'credit_applied' => $creditApplied,
                    'is_credit_note' => $isCreditNote,
                    'credit_amount' => $isCreditNote ? Cashier::formatAmount(abs($rawSubtotal)) : null,
                    'status' => $invoice->status,
                    'hosted_invoice_url' => $invoice->hosted_invoice_url,
                ];
            });
        }

        return Inertia::render('dashboard/invoices/index', [
            'invoices' => $invoices,
        ]);
    }
}
