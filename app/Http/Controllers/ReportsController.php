<?php

namespace App\Http\Controllers;

use App\Models\Reservations;
use App\Models\Room;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $period = $request->query('period', 'month');

        $today = Carbon::today();
        $start = null;
        $end = null;

        if ($period === 'custom') {
            $start = $request->query('start') ? Carbon::parse($request->query('start'))->startOfDay() : null;
            $end = $request->query('end') ? Carbon::parse($request->query('end'))->endOfDay() : null;
        }

        if (!$start || !$end) {
            switch ($period) {
                case 'day':
                    $start = $today->copy()->startOfDay();
                    $end = $today->copy()->endOfDay();
                    break;
                case 'week':
                    $start = $today->copy()->subDays(6)->startOfDay();
                    $end = $today->copy()->endOfDay();
                    break;
                case 'quarter':
                    $start = $today->copy()->subMonths(3)->addDay()->startOfDay();
                    $end = $today->copy()->endOfDay();
                    break;
                case 'year':
                    $start = $today->copy()->subYear()->addDay()->startOfDay();
                    $end = $today->copy()->endOfDay();
                    break;
                case 'month':
                default:
                    $start = $today->copy()->subMonth()->addDay()->startOfDay();
                    $end = $today->copy()->endOfDay();
                    break;
            }
        }

        $startDate = $start->toDateString();
        $endExclusiveDate = $end->copy()->addDay()->toDateString();

        $roomsCount = (int) Room::count();
        $days = max(1, $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()) + 1);

        $reservationsInRangeQuery = Reservations::query()
            ->where('status', '!=', 'Cancelled')
            ->where('check_in', '<', $endExclusiveDate)
            ->where('check_out', '>', $startDate);

        $bookings = (int) (clone $reservationsInRangeQuery)->count();
        $revenue = (float) (clone $reservationsInRangeQuery)->sum('total');

        $roomNights = (float) (clone $reservationsInRangeQuery)
            ->selectRaw(
                'COALESCE(SUM(GREATEST(0, DATEDIFF(LEAST(check_out, ?), GREATEST(check_in, ?)))), 0) as room_nights',
                [$endExclusiveDate, $startDate]
            )
            ->value('room_nights');

        $availableRoomNights = $roomsCount > 0 ? ($roomsCount * $days) : 0;

        $occupancyRate = $availableRoomNights > 0 ? round(($roomNights / $availableRoomNights) * 100, 1) : 0.0;
        $adr = $roomNights > 0 ? round($revenue / $roomNights, 2) : 0.0;
        $revpar = $availableRoomNights > 0 ? round($revenue / $availableRoomNights, 2) : 0.0;

        $monthly = Reservations::query()
            ->where('status', '!=', 'Cancelled')
            ->whereBetween('check_in', [$startDate, $end->toDateString()])
            ->selectRaw("DATE_FORMAT(check_in, '%Y-%m') as ym")
            ->selectRaw('COUNT(*) as bookings')
            ->selectRaw('COALESCE(SUM(total), 0) as revenue')
            ->selectRaw('COALESCE(SUM(DATEDIFF(check_out, check_in)), 0) as room_nights')
            ->groupBy('ym')
            ->orderBy('ym', 'desc')
            ->get()
            ->map(function ($row) use ($roomsCount) {
                $ym = (string) $row->ym;
                $monthStart = Carbon::createFromFormat('Y-m', $ym)->startOfMonth();
                $daysInMonth = $monthStart->daysInMonth;

                $availableRoomNights = $roomsCount > 0 ? ($roomsCount * $daysInMonth) : 0;
                $roomNights = (float) $row->room_nights;
                $revenue = (float) $row->revenue;

                return [
                    'month' => $monthStart->format('F Y'),
                    'occupancy' => $availableRoomNights > 0 ? round(($roomNights / $availableRoomNights) * 100, 1) : 0.0,
                    'adr' => $roomNights > 0 ? round($revenue / $roomNights, 2) : 0.0,
                    'revpar' => $availableRoomNights > 0 ? round($revenue / $availableRoomNights, 2) : 0.0,
                    'revenue' => round($revenue, 2),
                    'bookings' => (int) $row->bookings,
                ];
            });

        $dailyAgg = Reservations::query()
            ->where('status', '!=', 'Cancelled')
            ->whereBetween('check_in', [$startDate, $end->toDateString()])
            ->selectRaw('DATE(check_in) as d')
            ->selectRaw('COUNT(*) as bookings')
            ->selectRaw('COALESCE(SUM(total), 0) as revenue')
            ->groupBy('d')
            ->orderBy('d', 'asc')
            ->get();

        $dailyMap = $dailyAgg->keyBy(function ($row) {
            return (string) $row->d;
        });

        $series = [];
        foreach (CarbonPeriod::create($start->copy()->startOfDay(), $end->copy()->startOfDay()) as $date) {
            $d = $date->toDateString();
            $row = $dailyMap->get($d);
            $series[] = [
                'date' => $d,
                'label' => $date->format('M d'),
                'bookings' => (int) ($row->bookings ?? 0),
                'revenue' => round((float) ($row->revenue ?? 0), 2),
            ];
        }

        $revenueByTypeRows = DB::table('reservations as res')
            ->join('rooms as rm', 'rm.id', '=', 'res.room_id')
            ->where('res.status', '!=', 'Cancelled')
            ->whereBetween('res.check_in', [$startDate, $end->toDateString()])
            ->groupBy('rm.type')
            ->selectRaw('rm.type as room_type')
            ->selectRaw('COALESCE(SUM(res.total), 0) as revenue')
            ->orderByDesc('revenue')
            ->get();

        $topTypes = $revenueByTypeRows->take(2)->values();
        $otherRevenue = (float) $revenueByTypeRows->slice(2)->sum('revenue');

        $revenueBySource = $topTypes->map(function ($row) {
            return [
                'key' => (string) $row->room_type,
                'label' => (string) $row->room_type,
                'revenue' => round((float) $row->revenue, 2),
            ];
        })->values()->all();

        if ($otherRevenue > 0) {
            $revenueBySource[] = [
                'key' => 'Other',
                'label' => 'Other',
                'revenue' => round($otherRevenue, 2),
            ];
        }

        return response()->json([
            'period' => $period,
            'range' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
                'days' => $days,
            ],
            'kpis' => [
                'occupancy_rate' => $occupancyRate,
                'adr' => $adr,
                'revpar' => $revpar,
                'total_revenue' => round($revenue, 2),
                'bookings' => $bookings,
                'room_nights' => $roomNights,
                'rooms' => $roomsCount,
            ],
            'analytics' => [
                'reservations_daily' => $series,
                'revenue_by_source' => $revenueBySource,
            ],
            'monthly_summary' => $monthly,
        ]);
    }
}
