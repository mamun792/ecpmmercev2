<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'role',
        'monthly_salary',
        'status',
    ];

    public function salaries()
    {
        return $this->hasMany(EmployeeSalary::class);
    }
}
