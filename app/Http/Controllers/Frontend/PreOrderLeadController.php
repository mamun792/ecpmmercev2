<?php

namespace App\Http\Controllers\Frontend;

use App\Contracts\PreOrderLeadInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePreOrderLeadRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PreOrderLeadController extends Controller
{
    protected PreOrderLeadInterface $preOrderLeadService;

    public function __construct(PreOrderLeadInterface $preOrderLeadService)
    {
        $this->preOrderLeadService = $preOrderLeadService;
    }

    public function showForm()
    {
        return Inertia::render('PreOrderLead/Form');
    }

    public function store(StorePreOrderLeadRequest $request)
    {
        try {
            $this->preOrderLeadService->createLead($request->validated());

            return redirect()->back()->with('success', 'Your pre-order lead has been submitted successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to submit lead. Please try again.');
        }
    }
}