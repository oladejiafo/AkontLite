<?php

namespace App\Services;

use App\Models\Receipt;
use App\Models\GuestSession;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class ReceiptService
{
    public function __construct(
        private OCRService      $ocr,
        private EInvoiceService $einvoice
    ) {}

    public function createFromData(
        array $data,
        ?User $user,
        ?GuestSession $guest
    ): Receipt {
        $receipt = Receipt::create([
            'company_id'      => $user?->activeCompany()?->id,
            'user_id'         => $user?->id,
            'guest_token'     => $guest?->token,
            'type'            => $data['type'] ?? 'outgoing',
            'receipt_number'  => $data['receipt_number'] ?? $this->generateNumber(),
            'vendor_name'     => $data['vendor_name'] ?? null,
            'customer_name'   => $data['customer_name'] ?? null,
            'receipt_date'    => $data['receipt_date'] ?? now()->format('Y-m-d'),
            'subtotal'        => $data['subtotal'] ?? 0,
            'tax_amount'      => $data['tax_amount'] ?? 0,
            'discount_amount' => $data['discount_amount'] ?? 0,
            'total_amount'    => $data['total_amount'] ?? 0,
            'currency'        => $data['currency'] ?? 'USD',
            'tax_rate'        => $data['tax_rate'] ?? 0,
            'category'        => $data['category'] ?? null,
            'notes'           => $data['notes'] ?? null,
            'status'          => 'confirmed',
        ]);

        // save line items
        if (!empty($data['items'])) {
            foreach ($data['items'] as $index => $item) {
                $receipt->items()->create([
                    'description' => $item['description'],
                    'quantity'    => $item['quantity'] ?? 1,
                    'unit_price'  => $item['unit_price'] ?? 0,
                    'tax_rate'    => $item['tax_rate'] ?? 0,
                    'sort_order'  => $index,
                ]);
            }
        }

        // apply e-invoice if company is set
        if ($receipt->company) {
            $this->einvoice->applyZatcaToReceipt($receipt);
        }

        return $receipt->load('items');
    }

    public function createFromScan(
        UploadedFile $image,
        array $overrides,
        ?User $user,
        ?GuestSession $guest
    ): array {
        // extract data via OCR
        $extracted = $this->ocr->extractFromFile($image);

        if ($extracted['error']) {
            return ['success' => false, 'error' => $extracted['error']];
        }

        // store the original image permanently
        $imagePath = $image->store(
            'receipts/' . now()->format('Y/m'),
            'local'
        );

        // merge extracted with any manual overrides from user
        $data = array_merge($extracted, $overrides, [
            'type'           => 'incoming',
            'image_path'     => $imagePath,
            'ocr_confidence' => $extracted['confidence'],
            'ocr_raw'        => $extracted,
            'status'         => 'confirmed',
        ]);

        $receipt = $this->createFromData($data, $user, $guest);

        return [
            'success'    => true,
            'receipt'    => $receipt,
            'extracted'  => $extracted,
            'confidence' => $extracted['confidence'],
        ];
    }

    private function generateNumber(): string
    {
        return 'RCP-' . strtoupper(substr(uniqid(), -6));
    }
}