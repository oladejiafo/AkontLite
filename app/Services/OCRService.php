<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class OCRService
{
    private string $apiKey;
    private string $model = 'gpt-4o';

    public function __construct()
    {
        $this->apiKey = config('services.openai.key');
    }

    // public function extractFromFile(UploadedFile $file): array
    // {
    //     // store temp image
    //     $path = $file->store('ocr-temp', 's3');
    //     $base64 = base64_encode(file_get_contents($file->getRealPath()));
    //     $mimeType = $file->getMimeType();

    //     $result = $this->extractFromBase64($base64, $mimeType);

    //     // clean up temp file
    //     Storage::disk('s3')->delete($path);

    //     return $result;
    // }

    public function extractFromFile(UploadedFile $file): array
    {
        // store temp file locally instead of S3
        $base64   = base64_encode(file_get_contents($file->getRealPath()));
        $mimeType = $file->getMimeType();

        return $this->extractFromBase64($base64, $mimeType);
    }

    public function extractFromBase64(string $base64, string $mimeType = 'image/jpeg'): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type'  => 'application/json',
            ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
                'model'      => $this->model,
                'max_tokens' => 1000,
                'messages'   => [
                    [
                        'role'    => 'user',
                        'content' => [
                            [
                                'type' => 'image_url',
                                'image_url' => [
                                    'url'    => "data:{$mimeType};base64,{$base64}",
                                    'detail' => 'high',
                                ],
                            ],
                            [
                                'type' => 'text',
                                'text' => $this->buildPrompt(),
                            ],
                        ],
                    ],
                ],
            ]);

            if ($response->failed()) {
                Log::error('OCR API error', ['response' => $response->body()]);
                return $this->emptyResult('API request failed');
            }

            $content = $response->json('choices.0.message.content');
            return $this->parseResponse($content);

        } catch (\Exception $e) {
            Log::error('OCR extraction failed', ['error' => $e->getMessage()]);
            return $this->emptyResult($e->getMessage());
        }
    }

    private function buildPrompt(): string
    {
        return <<<PROMPT
You are a receipt data extraction assistant.

Extract all available data from this receipt image and return ONLY a valid JSON object with no markdown, no explanation, no code fences.

Return exactly this structure:
{
  "vendor_name": "",
  "receipt_number": "",
  "receipt_date": "",
  "subtotal": 0.00,
  "tax_amount": 0.00,
  "tax_rate": 0.00,
  "discount_amount": 0.00,
  "total_amount": 0.00,
  "currency": "",
  "items": [
    {
      "description": "",
      "quantity": 1,
      "unit_price": 0.00,
      "total": 0.00
    }
  ],
  "confidence": 0.00,
  "notes": ""
}

Rules:
- receipt_date must be in YYYY-MM-DD format
- All amounts must be numbers, not strings
- currency should be the 3-letter ISO code (e.g. USD, AED, NGN)
- confidence is your confidence score from 0 to 1
- If a field is not found, use null for strings and 0 for numbers
- items array should be empty [] if no line items are readable
PROMPT;
    }

    private function parseResponse(string $content): array
    {
        // strip any accidental markdown fences
        $clean = preg_replace('/```json|```/', '', $content);
        $clean = trim($clean);

        $data = json_decode($clean, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('OCR JSON parse failed', ['raw' => $content]);
            return $this->emptyResult('Could not parse OCR response');
        }

        return array_merge($this->emptyResult(), [
            'vendor_name'     => $data['vendor_name'] ?? null,
            'receipt_number'  => $data['receipt_number'] ?? null,
            'receipt_date'    => $data['receipt_date'] ?? now()->format('Y-m-d'),
            'subtotal'        => (float) ($data['subtotal'] ?? 0),
            'tax_amount'      => (float) ($data['tax_amount'] ?? 0),
            'tax_rate'        => (float) ($data['tax_rate'] ?? 0),
            'discount_amount' => (float) ($data['discount_amount'] ?? 0),
            'total_amount'    => (float) ($data['total_amount'] ?? 0),
            'currency'        => $data['currency'] ?? 'USD',
            'items'           => $data['items'] ?? [],
            'confidence'      => (float) ($data['confidence'] ?? 0),
            'notes'           => $data['notes'] ?? null,
            'error'           => null,
        ]);
    }

    private function emptyResult(string $error = null): array
    {
        return [
            'vendor_name'     => null,
            'receipt_number'  => null,
            'receipt_date'    => now()->format('Y-m-d'),
            'subtotal'        => 0.00,
            'tax_amount'      => 0.00,
            'tax_rate'        => 0.00,
            'discount_amount' => 0.00,
            'total_amount'    => 0.00,
            'currency'        => 'USD',
            'items'           => [],
            'confidence'      => 0.00,
            'notes'           => null,
            'error'           => $error,
        ];
    }
}