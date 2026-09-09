<?php

namespace App\Billing;

use App\Models\BillingInvoice;

final class InvoicePdf
{
    public function render(BillingInvoice $invoice): string
    {
        $seller = $invoice->seller_snapshot;
        $buyer = $invoice->buyer_snapshot;
        $content = '';

        $content .= $this->text(50, 790, 24, 'Factuur', bold: true);
        $content .= $this->text(50, 758, 11, $invoice->invoice_number, bold: true);
        $content .= $this->text(50, 740, 10, 'Factuurdatum: '.$invoice->issued_at->format('d-m-Y'));

        $content .= $this->text(50, 700, 11, 'Van', bold: true);
        $content .= $this->textLines(50, 682, [
            $seller['legal_name'],
            $seller['trade_name'],
            $seller['street'],
            $seller['postal_code'].' '.$seller['city'],
            $seller['country'],
            'KvK '.$seller['chamber_of_commerce'],
            'Btw-id '.$seller['vat_id'],
        ]);

        $buyerName = trim(($buyer['first_name'] ?? '').' '.($buyer['last_name'] ?? ''));
        $buyerLines = [($buyer['company_name'] ?? null) ?: $buyerName];

        if (($buyer['company_name'] ?? null) !== null) {
            $buyerLines[] = $buyerName;
        }

        foreach (['street', 'postal_code', 'city', 'country'] as $key) {
            if (is_string($buyer[$key] ?? null) && $buyer[$key] !== '') {
                $buyerLines[] = $buyer[$key];
            }
        }

        if (is_string($buyer['vat_id'] ?? null) && $buyer['vat_id'] !== '') {
            $buyerLines[] = 'Btw-id '.$buyer['vat_id'];
        }

        $buyerLines[] = $buyer['email'];
        $content .= $this->text(330, 700, 11, 'Aan', bold: true);
        $content .= $this->textLines(330, 682, $buyerLines);

        $content .= "0.75 w\n50 520 m 545 520 l S\n";
        $content .= $this->text(50, 500, 10, 'Omschrijving', bold: true);
        $content .= $this->text(455, 500, 10, 'Bedrag', bold: true);
        $content .= $this->text(50, 478, 10, $invoice->line_description);
        $content .= $this->text(455, 478, 10, $this->money($invoice->amount_minor));
        $content .= "0.75 w\n50 458 m 545 458 l S\n";
        $content .= $this->text(360, 432, 10, 'Totaal', bold: true);
        $content .= $this->text(455, 432, 10, $this->money($invoice->amount_minor), bold: true);
        $content .= $this->text(50, 390, 10, 'Vrijgesteld van btw wegens taalonderwijs.');
        $content .= $this->text(50, 370, 9, 'Betaald via Mollie. Er staat geen bedrag meer open.');
        $content .= $this->text(50, 70, 9, 'Vragen? support@spaansspreken.nl');

        return $this->document($content);
    }

    private function money(int $amountMinor): string
    {
        return 'EUR '.number_format($amountMinor / 100, 2, ',', '.');
    }

    /** @param list<string> $lines */
    private function textLines(int $x, int $y, array $lines): string
    {
        $content = '';

        foreach ($lines as $line) {
            foreach (explode("\n", wordwrap($line, 42, "\n", true)) as $wrapped) {
                $content .= $this->text($x, $y, 9, $wrapped);
                $y -= 15;
            }
        }

        return $content;
    }

    private function text(int $x, int $y, int $size, string $value, bool $bold = false): string
    {
        $encoded = mb_convert_encoding($value, 'Windows-1252', 'UTF-8');
        $escaped = str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', '', ' '], $encoded);
        $font = $bold ? 'F2' : 'F1';

        return "BT /{$font} {$size} Tf 1 0 0 1 {$x} {$y} Tm ({$escaped}) Tj ET\n";
    }

    private function document(string $content): string
    {
        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            3 => '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R /F2 5 0 R >> >> /Contents 6 0 R >>',
            4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
            5 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>',
            6 => "<< /Length ".strlen($content)." >>\nstream\n{$content}endstream",
        ];
        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];

        foreach ($objects as $number => $object) {
            $offsets[$number] = strlen($pdf);
            $pdf .= "{$number} 0 obj\n{$object}\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 7\n0000000000 65535 f \n";

        for ($number = 1; $number <= 6; $number++) {
            $pdf .= sprintf('%010d 00000 n ', $offsets[$number])."\n";
        }

        return $pdf."trailer\n<< /Size 7 /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF\n";
    }
}
