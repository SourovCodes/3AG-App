<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $invoices = [];

        if ($user->hasStripeId()) {
            $invoices = $user->invoices()->map(fn ($invoice) => [
                'id' => $invoice->id,
                'date' => $invoice->date()->toIso8601String(),
                'total' => $invoice->total(),
                'status' => $invoice->status,
                'hosted_invoice_url' => $invoice->hosted_invoice_url,
            ]);
        }

        return Inertia::render('dashboard/invoices/index', [
            'invoices' => $invoices,
        ]);
    }
}
