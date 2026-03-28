<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\KHQRService;
use KHQR\BakongKHQR;

class BakongController extends Controller
{
    public function generateKhqr(Request $request, KHQRService $khqrService): JsonResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['nullable', 'string', 'in:KHR,USD'],
            'bakong_account_id' => ['nullable', 'string'],
            'merchant_id' => ['nullable', 'string'],
            'merchant_name' => ['nullable', 'string'],
            'receiver_name' => ['nullable', 'string'],
            'merchant_city' => ['nullable', 'string'],
            'receiver_city' => ['nullable', 'string'],
            'expiration_timestamp' => ['nullable'],
        ]);

        $bakongAccountId = $validated['bakong_account_id']
            ?? $validated['merchant_id']
            ?? env('BAKONG_MERCHANT_ID')
            ?? env('BAKONG_ACCOUNT_ID');

        $merchantName = $validated['merchant_name']
            ?? $validated['receiver_name']
            ?? env('BAKONG_RECEIVER_NAME')
            ?? env('BAKONG_MERCHANT_NAME');

        $merchantCity = $validated['merchant_city']
            ?? $validated['receiver_city']
            ?? env('BAKONG_RECEIVER_CITY')
            ?? env('BAKONG_MERCHANT_CITY');

        $currency = $validated['currency'] ?? (string) env('BAKONG_CURRENCY', 'USD');

        if (!is_string($bakongAccountId) || trim($bakongAccountId) === '') {
            return response()->json([
                'message' => 'Missing Bakong merchant id. Provide bakong_account_id/merchant_id or set BAKONG_MERCHANT_ID in .env',
            ], 422);
        }

        if (!is_string($merchantName) || trim($merchantName) === '') {
            return response()->json([
                'message' => 'Missing receiver name. Provide merchant_name/receiver_name or set BAKONG_RECEIVER_NAME in .env',
            ], 422);
        }

        if (!is_string($merchantCity) || trim($merchantCity) === '') {
            return response()->json([
                'message' => 'Missing receiver city. Provide merchant_city/receiver_city or set BAKONG_RECEIVER_CITY in .env',
            ], 422);
        }

        $payload = [
            'bakong_account_id' => $bakongAccountId,
            'merchant_name' => $merchantName,
            'merchant_city' => $merchantCity,
            'currency' => $currency,
            'amount' => (float) $validated['amount'],
        ];

        if (isset($validated['expiration_timestamp'])) {
            $payload['expiration_timestamp'] = $validated['expiration_timestamp'];
        }

        $result = $khqrService->generateIndividualQR($payload);
        $qr = $result['data']['qr'] ?? null;
        $md5 = $result['data']['md5'] ?? null;

        if (!is_string($qr) || $qr === '') {
            return response()->json([
                'message' => 'Failed to generate KHQR string.',
                'error' => $result['error'] ?? null,
            ], 500);
        }

        return response()->json([
            'khqr' => $qr,
            'md5' => is_string($md5) ? $md5 : null,
            'currency' => $currency,
            'amount' => (float) $validated['amount'],
            'bakong_account_id' => $bakongAccountId,
            'merchant_name' => $merchantName,
            'merchant_city' => $merchantCity,
        ]);
    }

    public function verifyPayment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'md5' => ['nullable', 'string'],
            'full_hash' => ['nullable', 'string'],
            'short_hash' => ['nullable', 'string'],
            'amount' => ['nullable', 'numeric', 'min:0.01'],
            'currency' => ['nullable', 'string', 'in:KHR,USD'],
            'is_test' => ['sometimes', 'boolean'],
        ]);

        $token = env('BAKONG_API_KEY');
        if (!is_string($token) || trim($token) === '') {
            return response()->json([
                'message' => 'Missing Bakong API key. Set BAKONG_API_KEY in .env',
            ], 422);
        }

        $isTest = (bool) ($validated['is_test'] ?? false);

        $hasMd5 = isset($validated['md5']) && is_string($validated['md5']) && trim($validated['md5']) !== '';
        $hasFullHash = isset($validated['full_hash']) && is_string($validated['full_hash']) && trim($validated['full_hash']) !== '';
        $hasShortHash = isset($validated['short_hash']) && is_string($validated['short_hash']) && trim($validated['short_hash']) !== '';

        if (!$hasMd5 && !$hasFullHash && !$hasShortHash) {
            return response()->json([
                'message' => 'Provide md5, full_hash, or short_hash for verification.',
            ], 422);
        }

        try {
            $bakong = new BakongKHQR($token);

            if ($hasMd5) {
                $raw = $bakong->checkTransactionByMD5(trim($validated['md5']), $isTest);

                $paid = (isset($raw['responseCode']) && (int) $raw['responseCode'] === 0)
                    && (isset($raw['data']) && $raw['data'] !== null && $raw['data'] !== []);

                return response()->json([
                    'method' => 'md5',
                    'paid' => $paid,
                    'result' => $raw,
                ]);
            }

            if ($hasFullHash) {
                $raw = $bakong->checkTransactionByFullHash(trim($validated['full_hash']), $isTest);

                $paid = (isset($raw['responseCode']) && (int) $raw['responseCode'] === 0)
                    && (isset($raw['data']) && $raw['data'] !== null && $raw['data'] !== []);

                return response()->json([
                    'method' => 'full_hash',
                    'paid' => $paid,
                    'result' => $raw,
                ]);
            }

            $currency = $validated['currency'] ?? (string) env('BAKONG_CURRENCY', 'USD');
            if (!isset($validated['amount'])) {
                return response()->json([
                    'message' => 'amount is required when verifying with short_hash.',
                ], 422);
            }

            $raw = $bakong->checkTransactionByShortHash(
                trim($validated['short_hash']),
                (float) $validated['amount'],
                (string) $currency,
                $isTest
            );

            $paid = (isset($raw['responseCode']) && (int) $raw['responseCode'] === 0)
                && (isset($raw['data']) && $raw['data'] !== null && $raw['data'] !== []);

            return response()->json([
                'method' => 'short_hash',
                'paid' => $paid,
                'result' => $raw,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to verify transaction',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function verifyTransaction(Request $request): JsonResponse
    {
        return $this->verifyPayment($request);
    }
}