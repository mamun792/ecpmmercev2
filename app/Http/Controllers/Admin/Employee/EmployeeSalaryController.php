<?php

namespace App\Http\Controllers\Admin\Employee;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeSalary;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmployeeSalaryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->get('search');

        $salaries = EmployeeSalary::with('employee')
            ->when($search, function ($query, $search) {
                return $query->whereHas('employee', function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%');
                })->orWhere('month', 'like', '%' . $search . '%');
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $employees = Employee::select('id', 'name', 'monthly_salary')->get();

        return Inertia::render('Admin/Employee/Salary/Index', [
            'salaries' => $salaries,
            'employees' => $employees,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'month' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'paid_date' => 'nullable|date',
        ]);

        EmployeeSalary::create($validated);

        return redirect()->route('admin.employee.salary.index')->with('message', 'Employee salary added successfully');
    }
}