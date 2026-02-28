<?php

namespace App\Http\Controllers;
use App\Models\Room;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index()
    {
        $rooms = Room::all();
        return response()->json($rooms);
    }

    public function store(Request $request)
    {
        $request->validate([
            'number' => 'required|string|unique:rooms,number',
            'type' => 'required|string',
            'floor' => 'required|string',
            'capacity' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'status' => 'required|in:available,occupied,cleaning,maintenance',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $imageUrl = null;
        if ($request->hasFile('image')) {
            $cloudinary = app(CloudinaryService::class);
            $upload = $cloudinary->uploadImage($request->file('image')->getRealPath(), 'rooms');
            $imageUrl = $upload['url'];
        }   

        $room = Room::create([
            'number' => $request->number,
            'type' => $request->type,
            'floor' => $request->floor,
            'capacity' => $request->capacity,
            'price' => $request->price,
            'status' => $request->status,
            'image' => $imageUrl
        ]);
        return response()->json($room, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $room = Room::find($id);
        if (!$room) {
            return response()->json(['error' => 'Room not found'], 404);
        }
        return response()->json($room);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $room = Room::find($id);
        if (!$room) {
            return response()->json(['error' => 'Room not found'], 404);
        }

        $request->validate([
            'number' => 'required|string|unique:rooms,number,' . $id,
            'type' => 'required|string',
            'floor' => 'required|string',
            'capacity' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'status' => 'in:available,occupied,cleaning,maintenance',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $cloudinary = app(CloudinaryService::class);
            $upload = $cloudinary->uploadImage($request->file('image')->getRealPath(), 'rooms');
            $room->image = $upload['url'];
        }

        $room->update([
            'number' => $request->number ?? $room->number,
            'type' => $request->type ?? $room->type,
            'floor' => $request->floor ?? $room->floor,
            'capacity' => $request->capacity ?? $room->capacity,
            'price' => $request->price ?? $room->price,
            'status' => $request->status ?? $room->status,
        ]);
        return response()->json($room);
    }

    /**
     * Remove the specified resource from storage.  
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $room = Room::find($id);
        if (!$room) { 
            return response()->json(['error' => 'Room not found'], 404);
        }

        $room->delete();
        return response()->json(['message' => 'Room deleted successfully']);
    }
}