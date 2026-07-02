<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Receipt;
use App\Models\Company;

class EInvoiceService
{
    // ─────────────────────────────────────────
    // ZATCA (UAE / KSA) - TLV encoded QR
    // ─────────────────────────────────────────

    public function generateZatcaQr(
        string $sellerName,
        string $vatNumber,
        string $timestamp,
        string $total,
        string $vatAmount
    ): string {
        $tlv = '';
        $tlv .= $this->encodeTlv(1, $sellerName);
        $tlv .= $this->encodeTlv(2, $vatNumber);
        $tlv .= $this->encodeTlv(3, $timestamp);
        $tlv .= $this->encodeTlv(4, $total);
        $tlv .= $this->encodeTlv(5, $vatAmount);

        return base64_encode($tlv);
    }

    private function encodeTlv(int $tag, string $value): string
    {
        $valueBytes = mb_convert_encoding($value, 'UTF-8');
        $length     = strlen($valueBytes);
        return chr($tag) . chr($length) . $valueBytes;
    }

    public function applyZatcaToInvoice(Invoice $invoice): Invoice
    {
        $company = $invoice->company;

        $qr = $this->generateZatcaQr(
            sellerName: $company?->name ?? config('app.name'),
            vatNumber:  $company?->vat_number ?? '',
            timestamp:  $invoice->created_at->toIso8601String(),
            total:      number_format($invoice->total_amount, 2, '.', ''),
            vatAmount:  number_format($invoice->tax_amount ?? 0, 2, '.', '')
        );

        $invoice->update([
            'einvoice_qr'       => $qr,
            'einvoice_standard' => 'ZATCA',
        ]);

        return $invoice;
    }

    public function applyZatcaToReceipt(Receipt $receipt): Receipt
    {
        $company = $receipt->company;

        $qr = $this->generateZatcaQr(
            sellerName: $company?->name ?? $receipt->vendor_name ?? '',
            vatNumber:  $company?->vat_number ?? '',
            timestamp:  $receipt->receipt_date->toIso8601String(),
            total:      number_format($receipt->total_amount, 2, '.', ''),
            vatAmount:  number_format($receipt->tax_amount, 2, '.', '')
        );

        $receipt->update(['einvoice_qr' => $qr]);

        return $receipt;
    }

    // ─────────────────────────────────────────
    // FIRS (Nigeria) - UBL-style XML + QR hash
    // ─────────────────────────────────────────

    public function generateFirsXml(Invoice $invoice): string
    {
        $company  = $invoice->company;
        $items    = $invoice->items ?? collect();
        $issuedAt = $invoice->created_at->format('Y-m-d');
        $dueDate  = $invoice->due_date
                    ? \Carbon\Carbon::parse($invoice->due_date)->format('Y-m-d')
                    : $issuedAt;

        $linesXml = '';
        foreach ($items as $index => $item) {
            $linesXml .= "
            <cac:InvoiceLine>
                <cbc:ID>{$index}</cbc:ID>
                <cbc:InvoicedQuantity unitCode=\"EA\">{$item->quantity}</cbc:InvoicedQuantity>
                <cbc:LineExtensionAmount currencyID=\"{$invoice->currency}\">{$item->total}</cbc:LineExtensionAmount>
                <cac:Item>
                    <cbc:Description>{$item->description}</cbc:Description>
                </cac:Item>
                <cac:Price>
                    <cbc:PriceAmount currencyID=\"{$invoice->currency}\">{$item->unit_price}</cbc:PriceAmount>
                </cac:Price>
            </cac:InvoiceLine>";
        }

        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2"
         xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2"
         xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2">
    <cbc:UBLVersionID>2.1</cbc:UBLVersionID>
    <cbc:ID>{$invoice->invoice_number}</cbc:ID>
    <cbc:IssueDate>{$issuedAt}</cbc:IssueDate>
    <cbc:DueDate>{$dueDate}</cbc:DueDate>
    <cbc:InvoiceTypeCode>380</cbc:InvoiceTypeCode>
    <cbc:DocumentCurrencyCode>{$invoice->currency}</cbc:DocumentCurrencyCode>
    <cac:AccountingSupplierParty>
        <cac:Party>
            <cac:PartyName>
                <cbc:Name>{$company?->name}</cbc:Name>
            </cac:PartyName>
            <cac:PartyTaxScheme>
                <cbc:CompanyID>{$company?->tax_number}</cbc:CompanyID>
                <cac:TaxScheme><cbc:ID>VAT</cbc:ID></cac:TaxScheme>
            </cac:PartyTaxScheme>
        </cac:Party>
    </cac:AccountingSupplierParty>
    <cac:AccountingCustomerParty>
        <cac:Party>
            <cac:PartyName>
                <cbc:Name>{$invoice->client_name}</cbc:Name>
            </cac:PartyName>
        </cac:Party>
    </cac:AccountingCustomerParty>
    <cac:TaxTotal>
        <cbc:TaxAmount currencyID="{$invoice->currency}">{$invoice->tax_amount}</cbc:TaxAmount>
    </cac:TaxTotal>
    <cac:LegalMonetaryTotal>
        <cbc:TaxExclusiveAmount currencyID="{$invoice->currency}">{$invoice->subtotal}</cbc:TaxExclusiveAmount>
        <cbc:TaxInclusiveAmount currencyID="{$invoice->currency}">{$invoice->total_amount}</cbc:TaxInclusiveAmount>
        <cbc:PayableAmount currencyID="{$invoice->currency}">{$invoice->total_amount}</cbc:PayableAmount>
    </cac:LegalMonetaryTotal>
    {$linesXml}
</Invoice>
XML;

        return $xml;
    }

    public function applyFirsToInvoice(Invoice $invoice): Invoice
    {
        $xml = $this->generateFirsXml($invoice);

        // QR is a hash of the XML for FIRS verification
        $qrHash = base64_encode(hash('sha256', $xml, true));

        $invoice->update([
            'einvoice_xml'      => $xml,
            'einvoice_qr'       => $qrHash,
            'einvoice_standard' => 'FIRS',
        ]);

        return $invoice;
    }

    // ─────────────────────────────────────────
    // Auto-detect standard from company country
    // ─────────────────────────────────────────

    public function applyToInvoice(Invoice $invoice): Invoice
    {
        $standard = $invoice->company?->country_standard ?? 'none';

        return match ($standard) {
            'UAE'     => $this->applyZatcaToInvoice($invoice),
            'Nigeria' => $this->applyFirsToInvoice($invoice),
            default   => $invoice,
        };
    }
}