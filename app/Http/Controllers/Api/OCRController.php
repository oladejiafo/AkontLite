<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GuestSessionService;
use App\Services\ReceiptService;
use App\Services\OCRService;
use Illuminate\Http\Request;

class OCRController extends Controller
{
    public function __construct(
        private OCRService          $ocrService,
        private ReceiptService      $receiptService,
        private GuestSessionService $guestService
    ) {}

    // POST /api/ocr/extract
    // Extract data from image without saving
    public function extract(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,jpg,png,webp|max:5120',
        ]);

        $result = $this->ocrService->extractFromFile($request->file('image'));

        if ($result['error']) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data'    => $result,
        ]);
    }

    // POST /api/ocr/scan-and-save
    // Extract + save as incoming receipt in one call
    public function scanAndSave(Request $request)
    {
        $request->validate([
            'image'    => 'required|image|mimes:jpeg,jpg,png,webp|max:5120',
            'currency' => 'nullable|string|size:3',
            'category' => 'nullable|string|max:100',
            'notes'    => 'nullable|string',
        ]);

        $user  = $request->user();
        $guest = $this->guestService->resolve($request);

        if (!$user && !$guest) {
            $guest = $this->guestService->create($request);
        }

        $result = $this->receiptService->createFromScan(
            image:     $request->file('image'),
            overrides: $request->only(['currency', 'category', 'notes']),
            user:      $user,
            guest:     $guest
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], 422);
        }

        return response()->json([
            'success'     => true,
            'data'        => $result['receipt'],
            'confidence'  => $result['confidence'],
            'guest_token' => $guest?->token,
        ], 201);
    }
}