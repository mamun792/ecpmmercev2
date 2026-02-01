<?php

namespace App\Http\Controllers\Admin\CorporateClient;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\CorporateClient\CorporateClientService;
use App\Http\Requests\CorporateClient\StoreCorporateClientRequest;
use App\Models\CorporateClient;

class CorporateClientController extends Controller
{
    protected $corporateClientService;

    public function __construct(CorporateClientService $corporateClientService)
    {
        $this->corporateClientService = $corporateClientService;
    }

    public function index(){
        $corporateClients = $this->corporateClientService->getAllCorporateClients();
        return Inertia::render("Admin/CorporateClient/Index", compact("corporateClients"));
    }

    public function create(){
        return Inertia::render("Admin/CorporateClient/Create");
    }

    public function store(StoreCorporateClientRequest $request)
    {
        try {
            $this->corporateClientService->createCorporateClient($request->validated());

            return redirect()
                ->route('admin.corporate-clients.index')
                ->with('success', 'Corporate client created successfully.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to create corporate client: ' . $e->getMessage());
        }
    }


        /**
      * Show the form for editing the specified corporate client.
      */
    public function edit(CorporateClient $corporateClient)
    {
        return Inertia::render('Admin/CorporateClient/Edit', compact('corporateClient'));
    }

    /**
      * Update the specified corporate client in storage.
      */
    public function update(StoreCorporateClientRequest $request, CorporateClient $corporateClient)
    {
        try {
            $this->corporateClientService->updateCorporateClient($corporateClient, $request->validated());

            return redirect()
                ->route('admin.corporate-clients.index')
                ->with('success', 'Corporate client updated successfully.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to update corporate client: ' . $e->getMessage());
        }
    }

    /**
      * Remove the specified corporate client from storage.
      */
    public function destroy(CorporateClient $corporateClient)
    {
        try {
            $this->corporateClientService->deleteCorporateClient($corporateClient);

            return redirect()
                ->route('admin.corporate-clients.index')
                ->with('success', 'Corporate client deleted successfully.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Failed to delete corporate client: ' . $e->getMessage());
        }
    }




}