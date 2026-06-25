<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DiningTable;
use Illuminate\Http\Request;

class DiningTableController extends Controller
{
    public function index()
    {
        return response()->json(DiningTable::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'table_number' => 'required|string|max:50'
        ]);

        $table = DiningTable::create($validated);

        return response()->json($table->fresh(), 201);
    }

    public function updateStatus(Request $request, DiningTable $diningTable)
    {
        $validated = $request->validate([
            'status' => 'required|in:available,occupied,reserved'
        ]);

        $diningTable->update($validated);

        return response()->json($diningTable);
    }
}
