<?php

namespace App\Http\Controllers\Admin\Attribute;

use Inertia\Inertia;
use App\Models\Attribute;
use Illuminate\Http\Request;
use App\Models\AttributeValue;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class AttributeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $attrList = Attribute::with(['values' => function ($query) {
    $query->orderBy('value', 'asc');
}])->get();


        return Inertia::render('Admin/Attribute/Index', [
            'attrList' => $attrList,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function Create(){

    }

    /**
     * Store a newly created resource in storage.
     */
    // public function store(Request $request)
    // {

    //     // Create the attribute
    //     $attribute = Attribute::create([
    //         'name' => $request->name,
    //     ]);


    //     foreach ($request->values as $value) {
    //         AttributeValue::create([
    //             'attribute_id' => $attribute->id,
    //             'value' => $value['value'],
    //             'color' => $value['color'] ?? null,
    //         ]);
    //     }

    //     return redirect()->route('admin.attributes.create')->with('success', 'Attribute and its values created successfully.');
    // }


public function store(Request $request)
{
    $request->validate([
        'name' => 'required|string|unique:attributes,name,NULL,id,deleted_at,NULL',
        'values' => 'required|array',
        'values.*.value' => 'required|string',
    ]);

    // Check for duplicate values in the request
    $values = collect($request->values)->pluck('value')->map(fn($v) => Str::lower(trim($v)));
    if ($values->count() !== $values->unique()->count()) {
        return redirect()
            ->back()
            ->withInput()
            ->with('error', 'Duplicate attribute values are not allowed.');
    }    DB::beginTransaction();

    try {

        // Create attribute with active status by default (Big Tech style)
        $attribute = Attribute::create([
            'name' => Str::upper(trim($request->name)),
            'status' => 'active',
        ]);

        // Create or restore attribute values
        foreach ($request->values as $value) {
            $valueText = Str::upper(trim($value['value']));

            // Check if soft-deleted value exists
            $existingValue = AttributeValue::withTrashed()
                ->where('attribute_id', $attribute->id)
                ->where('value', $valueText)
                ->first();

            if ($existingValue && $existingValue->trashed()) {
                // Restore soft-deleted value
                $existingValue->restore();
                $existingValue->update(['color' => $value['color'] ?? null]);
            } else {
                // Create new value with active status (Big Tech style)
                AttributeValue::create([
                    'attribute_id' => $attribute->id,
                    'value'        => $valueText,
                    'color'        => $value['color'] ?? null,
                    'status'       => 'active',
                ]);
            }
        }

        DB::commit();

        // Clear attributes cache so new attribute appears immediately
        Cache::forget('attributes.all');

        return redirect()
            ->route('admin.attributes.create')
            ->with('success', 'Attribute and its values created successfully.');

    } catch (\Exception $e) {

        DB::rollBack();

        return redirect()
            ->back()
            ->withInput()
            ->with('error', 'Something went wrong. Please try again.');
    }
}

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $attribute = Attribute::with(['values' => function ($query) {
    $query->orderBy('value', 'asc');
}])->findOrFail($id);

        return Inertia::render('Admin/Attribute/Index', [
            'attrList' => Attribute::with(['values' => function ($query) {
    $query->orderBy('value', 'asc');
}])->get(),
            'editingAttribute' => $attribute
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $attribute = Attribute::findOrFail($id);

        // Validate request
        $request->validate([
            'name' => 'required|string|unique:attributes,name,' . $attribute->id . ',id,deleted_at,NULL',
            'values' => 'required|array',
            'values.*.value' => 'required|string',
        ]);

        // Check for duplicate values in the request
        $values = collect($request->values)->pluck('value')->map(fn($v) => Str::lower(trim($v)));
        if ($values->count() !== $values->unique()->count()) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Duplicate attribute values are not allowed.');
        }

        // Update attribute name
        $attribute->update([
            'name' => $request->name,
        ]);

        // Process values
        $existingIds = [];
        foreach ($request->values as $value) {
            $valueText = Str::upper(trim($value['value']));

            if (isset($value['id'])) {
                // Update existing value
                AttributeValue::where('id', $value['id'])->update([
                    'value' => $valueText,
                    'color' => $value['color'] ?? null,
                ]);
                $existingIds[] = $value['id'];
            } else {
                // Check if value already exists (active or soft-deleted)
                $existingValue = AttributeValue::withTrashed()
                    ->where('attribute_id', $attribute->id)
                    ->where('value', $valueText)
                    ->first();

                if ($existingValue) {
                    // Value exists - restore if soft-deleted and update color
                    if ($existingValue->trashed()) {
                        $existingValue->restore();
                    }
                    $existingValue->update(['color' => $value['color'] ?? null]);
                    $existingIds[] = $existingValue->id;
                } else {
                    // Create new value with active status (Big Tech style)
                    $newValue = AttributeValue::create([
                        'attribute_id' => $attribute->id,
                        'value' => $valueText,
                        'color' => $value['color'] ?? null,
                        'status' => 'active',
                    ]);
                    $existingIds[] = $newValue->id;
                }
            }
        }

        // Delete removed values
        AttributeValue::where('attribute_id', $attribute->id)
            ->whereNotIn('id', $existingIds)
            ->delete();

        // Clear attributes cache
        Cache::forget('attributes.all');

        return redirect()->route('admin.attributes.index')->with('success', 'Attribute updated successfully.');
    }

    /**
     * Toggle attribute status (Big Tech Style - No Delete)
     * Active → Inactive → Active
     * Preserves all data, never truly deletes
     */
    public function toggleStatus($id)
    {
        $attribute = Attribute::findOrFail($id);

        // Toggle status
        $newStatus = $attribute->status === 'active' ? 'inactive' : 'active';
        $attribute->status = $newStatus;
        $attribute->save();

        // Also toggle all attribute values status
        AttributeValue::where('attribute_id', $id)->update([
            'status' => $newStatus
        ]);

        // Clear cache
        Cache::forget('active_attributes_with_values');
        Cache::forget('attributes.all');

        $message = $newStatus === 'active'
            ? 'Attribute activated successfully! It will now appear in product forms.'
            : 'Attribute deactivated successfully! It will be hidden from product forms but existing products retain their data.';

        return redirect()->route('admin.attributes.index')
            ->with('success', $message);
    }
}
