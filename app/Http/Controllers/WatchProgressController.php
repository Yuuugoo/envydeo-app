<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WatchProgress;
use Illuminate\Support\Facades\Auth;

class WatchProgressController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $userId = Auth::id();
        
        $watchProgress = WatchProgress::where('user_id', $userId)
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $watchProgress,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'tmdb_id' => 'required|integer',
            'media_type' => 'required|string|in:movie,tv',
            'watched' => 'required|numeric|min:0',
            'duration' => 'required|numeric|min:0',
        ]);

        $userId = Auth::id();
        
        // Find existing progress or create new one
        $watchProgress = WatchProgress::updateOrCreate(
            [
                'user_id' => $userId,
                'tmdb_id' => $request->tmdb_id,
                'media_type' => $request->media_type,
            ],
            [
                'watched' => $request->watched,
                'duration' => $request->duration,
            ]
        );

        return response()->json([
            'success' => true,
            'data' => $watchProgress,
            'watched_percentage' => $watchProgress->watched_percentage,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($tmdbId)
    {
        $userId = Auth::id();
        
        $deleted = WatchProgress::where('user_id', $userId)
            ->where('tmdb_id', $tmdbId)
            ->delete();

        return response()->json([
            'success' => $deleted > 0,
            'message' => $deleted > 0 ? 'Watch progress deleted' : 'Watch progress not found',
        ]);
    }
}
