<?php

namespace App\Http\Controllers\Admin\Expenses;

use Inertia\Inertia;
use App\Models\Expense;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\ProductPurchaseCost;

class ExpensesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Check if this is for product purchase costs or regular expenses
        if ($request->route()->getName() === 'admin.product-purchase-costs.index') {
            $items = ProductPurchaseCost::orderBy('created_at', 'desc')->paginate(10);
            return Inertia::render('Admin/ProductPurchaseCost/Index', [
                'productPurchaseCosts' => $items,
            ]);
        }

        $expenses = Expense::orderBy('created_at', 'desc')->paginate(10);
        return Inertia::render('Admin/Expense/Index', [
            'expenses' => $expenses,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        if ($request->route()->getName() === 'admin.product-purchase-costs.create') {
            return Inertia::render('Admin/ProductPurchaseCost/Create');
        }

        return Inertia::render('Admin/Expense/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if ($request->route()->getName() === 'admin.product-purchase-costs.store') {
            $request->validate([
                'cost_name' => 'required|string|max:255',
                'quantity' => 'nullable|integer|min:1',
                'unit_price' => 'nullable|numeric|min:0',
                'total_price' => 'required|numeric|min:0',
                'purchase_date' => 'required|date',
                'supplier_name' => 'nullable|string|max:255',
                'month' => 'required|string|max:255',
                'note' => 'nullable|string',
            ]);

            ProductPurchaseCost::create($request->all());

            return redirect()->route('admin.product-purchase-costs.index')->with('success', 'Product purchase cost created successfully!');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'month' => 'required|string|max:255',
            'date' => 'required|date',
            'note' => 'nullable|string',
        ]);

        Expense::create($request->all());

        return redirect()->route('admin.expenses.index')->with('success', 'Expense created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show($id, Request $request)
    {
        if ($request->route()->getName() === 'admin.product-purchase-costs.show') {
            $item = ProductPurchaseCost::findOrFail($id);
            return Inertia::render('Admin/ProductPurchaseCost/Show', [
                'productPurchaseCost' => $item,
            ]);
        }

        $expense = Expense::findOrFail($id);
        return Inertia::render('Admin/Expense/Show', [
            'expense' => $expense,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id, Request $request)
    {
        if ($request->route()->getName() === 'admin.product-purchase-costs.edit') {
            $item = ProductPurchaseCost::findOrFail($id);
            return Inertia::render('Admin/ProductPurchaseCost/Edit', [
                'productPurchaseCost' => $item,
            ]);
        }

        $expense = Expense::findOrFail($id);
        return Inertia::render('Admin/Expense/Edit', [
            'expense' => $expense,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        if ($request->route()->getName() === 'admin.product-purchase-costs.update') {
            $item = ProductPurchaseCost::findOrFail($id);

            $request->validate([
                'cost_name' => 'required|string|max:255',
                'quantity' => 'nullable|integer|min:1',
                'unit_price' => 'nullable|numeric|min:0',
                'total_price' => 'required|numeric|min:0',
                'purchase_date' => 'required|date',
                'supplier_name' => 'nullable|string|max:255',
                'month' => 'required|string|max:255',
                'note' => 'nullable|string',
            ]);

            $item->update($request->all());

            return redirect()->route('admin.product-purchase-costs.index')->with('success', 'Product purchase cost updated successfully!');
        }

        $expense = Expense::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'month' => 'required|string|max:255',
            'date' => 'required|date',
            'note' => 'nullable|string',
        ]);

        $expense->update($request->all());

        return redirect()->route('admin.expenses.index')->with('success', 'Expense updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id, Request $request)
    {
        if ($request->route()->getName() === 'admin.product-purchase-costs.destroy') {
            $item = ProductPurchaseCost::findOrFail($id);
            $item->delete();

            return redirect()->route('admin.product-purchase-costs.index')->with('success', 'Product purchase cost deleted successfully!');
        }

        $expense = Expense::findOrFail($id);
        $expense->delete();

        return redirect()->route('admin.expenses.index')->with('success', 'Expense deleted successfully!');
    }
}
