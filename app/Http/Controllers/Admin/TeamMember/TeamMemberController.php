<?php

namespace App\Http\Controllers\Admin\TeamMember;

use App\Http\Controllers\Controller;
use App\Helpers\ImageHelper;
use App\Models\TeamMember;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class TeamMemberController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->get('search');

        $teamMembers = TeamMember::query()
            ->when($search, function ($query, $search) {
                return $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('designation', 'like', '%' . $search . '%');
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return Inertia::render('Admin/TeamMember/Index', [
            'teamMembers' => $teamMembers,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Admin/TeamMember/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'designation' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = ImageHelper::uploadImage($request->file('image'), 'uploads/team_members');
        }

        TeamMember::create($validated);

        return redirect()->route('admin.team-member.index')->with('message', 'Team member created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(TeamMember $teamMember)
    {
        // Not needed for admin panel
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TeamMember $teamMember)
    {
        return Inertia::render('Admin/TeamMember/Edit', [
            'teamMember' => $teamMember,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TeamMember $teamMember)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'designation' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = ImageHelper::uploadImage($request->file('image'), 'uploads/team_members');
        }

        $teamMember->update($validated);

        return redirect()->route('admin.team-member.index')->with('message', 'Team member updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TeamMember $teamMember)
    {
        if ($teamMember->image) {
            ImageHelper::deleteImage($teamMember->image);
        }

        $teamMember->delete();
        return redirect()->route('admin.team-member.index')->with('message', 'Team member deleted successfully');
    }
}