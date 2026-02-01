<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\PreOrderLeadInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PreOrderLeadController extends Controller
{
    protected PreOrderLeadInterface $preOrderLeadService;

    public function __construct(PreOrderLeadInterface $preOrderLeadService)
    {
        $this->preOrderLeadService = $preOrderLeadService;
    }

    public function index()
    {
        $leads = $this->preOrderLeadService->getAllLeads();

        return Inertia::render('Admin/Leads/Index', [
            'leads' => $leads,
        ]);
    }

    public function show($id)
    {
        $lead = $this->preOrderLeadService->getLeadById($id);

        return Inertia::render('Admin/Leads/Show', [
            'lead' => $lead,
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,contacted,converted,rejected',
        ]);

        try {
            $this->preOrderLeadService->updateLeadStatus($id, $request->status);

            return response()->json([
                'success' => true,
                'message' => 'Lead status updated successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update lead status.'
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $this->preOrderLeadService->deleteLead($id);

            return response()->json([
                'success' => true,
                'message' => 'Lead deleted successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete lead.'
            ], 500);
        }
    }
}