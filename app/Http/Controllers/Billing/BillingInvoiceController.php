<?php

namespace App\Http\Controllers\Billing;

use App\Billing\InvoicePdf;
use App\Http\Controllers\Controller;
use App\Models\BillingInvoice;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class BillingInvoiceController extends Controller
{
    public function __invoke(
        Request $request,
        BillingInvoice $billingInvoice,
        InvoicePdf $pdf,
    ): Response {
        abort_unless($billingInvoice->subscription->user_id === $request->user()->getKey(), 404);

        return response($pdf->render($billingInvoice), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$billingInvoice->invoice_number.'.pdf"',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }
}
