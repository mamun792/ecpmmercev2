<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create simplified permissions matching sidebar route names
        $permissions = [
            'admin.dashboard.index',
            'admin.orders.index',
            'admin.pos.index',
            'admin.products.index',
            'admin.products.create',
            'admin.attributes.index',
            'admin.categories.index',
            'admin.brands.index',
            'admin.inventory.getAllProductsStock',
            'admin.reports.generateReport',
            'admin.employee.index',
            'admin.employee.salary.index',
            'admin.team-member.index',
            'admin.business-dashboard.index',
            'admin.expenses.index',
            'admin.product-purchase-costs.index',
            'admin.transaction-history.index',
            'admin.users.index',
            'admin.roles.index',
            // legacy permission used by route middleware
            'manage_user_roles',
            'admin.coupons.index',
            'admin.campaigns.index',
            'admin.corporate-clients.index',
            'admin.landing-pages.index',
            'admin.site-pages.index',
            'admin.blogs.index',
            'admin.reviews.index',
            'admin.courier.settings.index',
            'admin.settings.basicInformation',
            'admin.media.index',
            'admin.sliders.index',
            'admin.promotional-sliders.index',
            'admin.settings.socialLinks',
            'admin.marketing-tools.index',
        ];

        // create permissions if not exists (idempotent)
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create Admin role and assign all permissions (idempotent)
    $adminRole = Role::firstOrCreate(['name' => 'admin']);
    $adminRole->givePermissionTo(Permission::all());
    // ensure legacy permission is assigned as well
    $adminRole->givePermissionTo('manage_user_roles');

        // Create other roles with specific permissions (idempotent)
        $managerRole = Role::firstOrCreate(['name' => 'manager']);
        $managerRole->givePermissionTo([
            'admin.dashboard.index',
            'admin.orders.index',
            'admin.products.index',
            'admin.inventory.getAllProductsStock',
            'admin.reports.generateReport',
            'admin.employee.index',
            'admin.business-dashboard.index',
            'admin.expenses.index',
        ]);

        $accountantRole = Role::firstOrCreate(['name' => 'accountant']);
        $accountantRole->givePermissionTo([
            'admin.dashboard.index',
            'admin.business-dashboard.index',
            'admin.reports.generateReport',
            'admin.expenses.index',
            'admin.product-purchase-costs.index',
            'admin.employee.salary.index',
        ]);

        $salesRole = Role::firstOrCreate(['name' => 'sales']);
        $salesRole->givePermissionTo([
            'admin.dashboard.index',
            'admin.pos.index',
            'admin.products.index',
            'admin.inventory.getAllProductsStock',
            'admin.coupons.index',
        ]);

        // Create customer role for regular users (no admin permissions)
        Role::firstOrCreate(['name' => 'customer']);

        // Admin user will be created in DatabaseSeeder
    }
}