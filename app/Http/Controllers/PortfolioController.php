<?php

namespace App\Http\Controllers;

use App\Models\PortfolioItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PortfolioController extends Controller
{
    public function index()
    {
        $items = PortfolioItem::where('user_id', Auth::id())
            ->latest()
            ->get()
            ->map(function ($item) {
                return [
                    ...$item->toArray(),
                    // Ensure absolute URL so frontend can load images from any origin
                    'image_url' => url(Storage::url($item->image_path)),
                ];
            });

        return response()->json($items);
    }

    public function publicPortfolio(Request $request, $userId)
    {
        $perPage = min(max((int) $request->get('per_page', 5), 1), 20);

        $paginated = PortfolioItem::where('user_id', $userId)
            ->latest()
            ->paginate($perPage);

        $paginated->getCollection()->transform(function ($item) {
            return [
                ...$item->toArray(),
                'image_url' => url(Storage::url($item->image_path)),
            ];
        });

        return response()->json($paginated);
    }

    public function store(Request $request)
    {
        $request->validate([
            'image'       => 'required|image|max:6144',
            'title'       => 'nullable|string|max:120',
            'description' => 'nullable|string|max:2000',
        ]);

        $path = $request->file('image')->store('portfolio', 'public');

        $item = PortfolioItem::create([
            'user_id'     => Auth::id(),
            'image_path'  => $path,
            'title'       => $request->title,
            'description' => $request->description,
            // Uploads are visible immediately by default
            'status'      => 'approved',
        ]);

        return response()->json([
            ...$item->toArray(),
            'image_url' => url(Storage::url($item->image_path)),
        ], 201);
    }

    public function destroy($id)
    {
        $item = PortfolioItem::findOrFail($id);

        // A1: formally enforce ownership via policy
        $this->authorize('delete', $item);

        Storage::disk('public')->delete($item->image_path);
        $item->delete();

        return response()->json(['message' => 'deleted']);
    }
}
