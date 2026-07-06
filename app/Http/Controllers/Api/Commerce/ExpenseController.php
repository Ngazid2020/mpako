<?php

namespace App\Http\Controllers\Api\Commerce;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ExpenseResource;
use App\Models\Expense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $shop     = $request->attributes->get('shop');
        $expenses = $shop->expenses()->with('category')->latest()->get();

        return response()->json(ExpenseResource::collection($expenses));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'expense_category_id' => 'nullable|integer',
            'description'         => 'nullable|string|max:500',
            'amount'              => 'required|numeric|min:1',
            'spent_at'            => 'nullable|date',
            'note'                => 'nullable|string|max:500',
        ]);

        $shop = $request->attributes->get('shop');
        $user = $request->user();

        $expense = Expense::create([
            'shop_id'             => $shop->id,
            'user_id'             => $user->id,
            'expense_category_id' => $request->expense_category_id,
            'description'         => $request->description,
            'amount'              => $request->amount,
            'spent_at'            => $request->spent_at ?? now()->toDateString(),
        ]);

        return response()->json(new ExpenseResource($expense->load('category')), 201);
    }
}
