<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeSalary extends Model
{
    //protected $table = 'employee_salaries';

    protected $fillable = [
        'employee_id',
        'month',
        'amount',
        'paid_date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
