<?php

namespace App\Http\Controllers;

use App\Models\Historys;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\Rule;

class HistorysController extends Controller
{
    public function index()
    {
        return response()->json(
            Historys::with(['guest', 'room', 'reservation'])
                ->latest()
                ->get()
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'guest_id' => ['required', 'integer', 'exists:guests,id'],
            'room_id' => ['required', 'integer', 'exists:rooms,id'],
            'reservation_id' => ['required', 'integer', 'exists:reservations,id'],
            'total_stays' => ['sometimes', 'integer', 'min:0'],
            'status' => ['required', Rule::in(['current', 'upcoming', 'past'])],
        ]);

        $history = Historys::create($validated);

        return response()->json(
            $history->load(['guest', 'room', 'reservation']),
            201
        );
    }

    public function show($id)
    {
        try {
            return response()->json(
                Historys::with(['guest', 'room', 'reservation'])->findOrFail($id)
            );
        } catch (ModelNotFoundException) {
            return response()->json(['message' => 'History not found'], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $history = Historys::findOrFail($id);

            $validated = $request->validate([
                'guest_id' => ['sometimes', 'integer', 'exists:guests,id'],
                'room_id' => ['sometimes', 'integer', 'exists:rooms,id'],
                'reservation_id' => ['sometimes', 'integer', 'exists:reservations,id'],
                'total_stays' => ['sometimes', 'integer', 'min:0'],
                'status' => ['sometimes', Rule::in(['current', 'upcoming', 'past'])],
            ]);

            $history->update($validated);

            return response()->json(
                $history->load(['guest', 'room', 'reservation'])
            );
        } catch (ModelNotFoundException) {
            return response()->json(['message' => 'History not found'], 404);
        }
    }
    
    public function destroy($id)
    {
        try {
            $history = Historys::findOrFail($id);
            $history->delete();

            return response()->json(['message' => 'History deleted successfully']);
        } catch (ModelNotFoundException) {
            return response()->json(['message' => 'History not found'], 404);
        }
    }
}
