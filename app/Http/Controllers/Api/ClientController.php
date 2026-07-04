<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $user  = $request->user();
        $query = Customer::where('user_id', $user->id)->orderBy('name');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        return response()->json(['data' => $query->paginate(20)]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
        ]);

        $client = Customer::create([
            'user_id' => $request->user()->id,
            'name'    => $request->name,
            'email'   => $request->email,
            'phone'   => $request->phone,
            'address' => $request->address,
        ]);

        return response()->json(['data' => $client], 201);
    }

    public function show(Request $request, int $id)
    {
        $client = $this->resolveClient($request, $id);
        if (!$client) return response()->json(['message' => 'Not found'], 404);
        return response()->json(['data' => $client]);
    }

    public function update(Request $request, int $id)
    {
        $client = $this->resolveClient($request, $id);
        if (!$client) return response()->json(['message' => 'Not found'], 404);

        $client->update($request->only([
            'name', 'email', 'phone', 'address',
        ]));

        return response()->json(['data' => $client]);
    }

    public function destroy(Request $request, int $id)
    {
        $client = $this->resolveClient($request, $id);
        if (!$client) return response()->json(['message' => 'Not found'], 404);

        $client->delete();
        return response()->json(['success' => true]);
    }

    private function resolveClient(Request $request, int $id): ?Customer
    {
        $client = Customer::find($id);
        if (!$client) return null;
        if ($client->user_id !== $request->user()->id) return null;
        return $client;
    }
}