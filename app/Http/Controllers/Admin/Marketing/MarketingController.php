<?php

namespace App\Http\Controllers\Admin\Marketing;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\MarketingTool;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;

class MarketingController extends Controller
{
    public function index()
    {
        $tools = MarketingTool::all();
        return Inertia::render('Admin/MarketingTools/Index', [
            'tools' => $tools
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'tool_name' => 'required|in:Facebook Pixel,Google Analytics,Google Tag Manager',
            'script_code' => 'required'
        ]);

        MarketingTool::updateOrCreate(
            ['tool_name' => $request->tool_name],
            ['script_code' => $request->script_code]
        );

        Cache::forget('site_info');

        return redirect()->route('admin.marketing-tools.index')->with('success', 'Script saved successfully.');
    }

    public function destroy(MarketingTool $marketingTool)
    {
        $marketingTool->delete();
        
        return redirect()->route('admin.marketing-tools.index')
            ->with('success', 'Marketing tool deleted successfully.');
    }
}