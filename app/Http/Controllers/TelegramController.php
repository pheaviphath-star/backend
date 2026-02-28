<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramController extends Controller
{
    private string $botToken;
    private string $chatId;

    public function __construct()
    {
        $botToken = env('TELEGRAM_BOT_TOKEN');
        $chatId = env('TELEGRAM_CHAT_ID');

        $this->botToken = is_string($botToken) ? $botToken : '';
        $this->chatId = is_string($chatId) ? $chatId : '';
    }

    public function sendReservationNotification(array $reservationData): bool
    {
        if ($this->botToken === '' || $this->chatId === '') {
            Log::warning('Telegram notification skipped (missing configuration)', [
                'has_bot_token' => $this->botToken !== '',
                'has_chat_id' => $this->chatId !== '',
                'reservation_id' => $reservationData['id'] ?? null,
            ]);
            return false;
        }

        try {
            $message = $this->formatReservationMessage($reservationData);
            
            $response = Http::timeout(10)->post("https://api.telegram.org/bot{$this->botToken}/sendMessage", [
                'chat_id' => $this->chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ]);

            if ($response->successful()) {
                Log::info('Telegram notification sent successfully', [
                    'reservation_id' => $reservationData['id'] ?? null,
                    'chat_id' => $this->chatId,
                ]);
                return true;
            }

            Log::error('Failed to send Telegram notification', [
                'response' => $response->json(),
                'status' => $response->status(),
                'reservation_id' => $reservationData['id'] ?? null,
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('Exception sending Telegram notification', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'reservation_id' => $reservationData['id'] ?? null,
            ]);
            return false;
        }
    }

    public function sendStatusUpdateNotification(array $reservationData, string $oldStatus = null): bool
    {
        if ($this->botToken === '' || $this->chatId === '') {
            Log::warning('Telegram status update skipped (missing configuration)', [
                'has_bot_token' => $this->botToken !== '',
                'has_chat_id' => $this->chatId !== '',
                'reservation_id' => $reservationData['id'] ?? null,
            ]);
            return false;
        }

        try {
            $message = $this->formatStatusUpdateMessage($reservationData, $oldStatus);
            
            $response = Http::timeout(10)->post("https://api.telegram.org/bot{$this->botToken}/sendMessage", [
                'chat_id' => $this->chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Exception sending status update notification', [
                'error' => $e->getMessage(),
                'reservation_id' => $reservationData['id'] ?? null,
            ]);
            return false;
        }
    }

    public function sendCancellationNotification(array $reservationData): bool
    {
        if ($this->botToken === '' || $this->chatId === '') {
            Log::warning('Telegram cancellation skipped (missing configuration)', [
                'has_bot_token' => $this->botToken !== '',
                'has_chat_id' => $this->chatId !== '',
                'reservation_id' => $reservationData['id'] ?? null,
            ]);
            return false;
        }

        try {
            $message = $this->formatCancellationMessage($reservationData);
            
            $response = Http::timeout(10)->post("https://api.telegram.org/bot{$this->botToken}/sendMessage", [
                'chat_id' => $this->chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Exception sending cancellation notification', [
                'error' => $e->getMessage(),
                'reservation_id' => $reservationData['id'] ?? null,
            ]);
            return false;
        }
    }

    private function formatReservationMessage(array $data): string
    {
        $guestName = $data['guest']['name'] ?? 'N/A';
        $roomName = $data['room']['number'] ?? 'N/A';
        $roomfloor = $data['room']['floor'] ?? 'N/A';
        $roomtype = $data['room']['type'] ?? 'N/A';
        $price = $data['room']['price'] ?? 0;
        $total = $data['total'] ?? 0;
        $checkIn = $data['check_in'] ?? 'N/A';
        $checkOut = $data['check_out'] ?? 'N/A';
        $status = $data['status'] ?? 'Pending';

        $priceText = number_format($price, 2) . ' $';
        $totalText = number_format($total, 2) . ' $';

        return trim(
            "📋 <b>New Reservation Created</b>\n" .
            "━━━━━━━━━━━━━━━━━━━━━\n" .
            "👤 <b>Guest:</b> {$guestName}\n" .
            "🏨 <b>Room:</b> {$roomName}\n" .
            "📊 <b>Floor:</b> {$roomfloor}\n" .
            "🏷️  <b>Type:</b> {$roomtype}\n" .
            "💰 <b>Price per night:</b> {$priceText}\n" .
            "💵 <b>Total amount:</b> {$totalText}\n" .
            "📅 <b>Check-in:</b> {$checkIn}\n" .
            "📅 <b>Check-out:</b> {$checkOut}\n" .
            "🔄 <b>Status:</b> {$status}\n" .
            "⏰ <b>Created at:</b> " . now()->toDateTimeString()
        );
    }

    private function formatStatusUpdateMessage(array $data, string $oldStatus = null): string
    {
        $guestName = $data['guest']['name'] ?? 'N/A';
        $roomName = $data['room']['number'] ?? 'N/A';
        $roomfloor = $data['room']['floor'] ?? 'N/A';
        $roomtype = $data['room']['type'] ?? 'N/A';
        $newStatus = $data['status'] ?? 'N/A';
        $reservationId = $data['id'] ?? 'N/A';

        $statusIcon = match ($newStatus) {
            'Confirmed' => '✅',
            'Checked In' => '🔑',
            'Checked Out' => '🚶‍♂️',
            'Cancelled' => '❌',
            'No Show' => '👻',
            'Pending' => '⏳',
            default => '🔄',
        };

        $message = "{$statusIcon} <b>Reservation Updated</b>\n" .
                  "━━━━━━━━━━━━━━━━━━━━━\n" .
                  "👤 <b>Guest:</b> {$guestName}\n" .
                  "🏨 <b>Room:</b> {$roomName}\n" .
                  "📊 <b>Floor:</b> {$roomfloor}\n" .
                  "🏷️  <b>Type:</b> {$roomtype}\n" .
                  "🆔 <b>Reservation ID:</b> #{$reservationId}\n";

        if ($oldStatus) {
            $message .= "📈 <b>Status changed:</b> {$oldStatus} → {$newStatus}\n";
        } else {
            $message .= "📈 <b>New status:</b> {$newStatus}\n";
        }

        $message .= "⏰ <b>Updated at:</b> " . now()->toDateTimeString();

        return trim($message);
    }

    private function formatCancellationMessage(array $data): string
    {
        $guestName = $data['guest']['name'] ?? 'N/A';
        $roomName = $data['room']['number'] ?? 'N/A';
        $reservationId = $data['id'] ?? 'N/A';
        $total = $data['total'] ?? 0;
        $totalText = number_format($total, 2) . ' $';

        return trim(
            "❌ <b>Reservation Cancelled</b>\n" .
            "━━━━━━━━━━━━━━━━━━━━━\n" .
            "👤 <b>Guest:</b> {$guestName}\n" .
            "🏨 <b>Room:</b> {$roomName}\n" .
            "🆔 <b>Reservation ID:</b> #{$reservationId}\n" .
            "💵 <b>Total amount:</b> {$totalText}\n" .
            "⏰ <b>Cancelled at:</b> " . now()->toDateTimeString()
        );
    }
}