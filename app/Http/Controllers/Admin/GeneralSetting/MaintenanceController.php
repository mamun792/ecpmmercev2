<?php

namespace App\Http\Controllers\Admin\GeneralSetting;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class MaintenanceController extends Controller
{
    /**
     * Show the maintenance dashboard
     */
    public function index()
    {
        $isMaintenanceMode = app()->isDownForMaintenance();
        
        return Inertia::render('Admin/GeneralSettings/Maintenance', [
            'isMaintenanceMode' => $isMaintenanceMode,
            'systemInfo' => [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                'database' => config('database.default'),
            ]
        ]);
    }

    /**
     * Toggle Maintenance Mode
     */
    public function toggleMaintenance(Request $request)
    {
        // 1. Validate Password (Big Tech Security)
        if ($request->password !== 'mamun') {
            Log::warning('Unauthorized Maintenance Attempt. IP: ' . $request->ip());
            return redirect()->back()->with('error', 'Unauthorized: Invalid Security Password.');
        }

        try {
            if (app()->isDownForMaintenance()) {
                Artisan::call('up');
                Log::info('System is now LIVE.');
                return redirect()->back()->with('success', 'System is now LIVE.');
            } else {
                $secret = $request->input('secret', 'admin-access');
                Artisan::call('down', [
                    '--secret' => $secret,
                    '--render' => 'errors::503'
                ]);
                Log::warning('System entered MAINTENANCE MODE.');
                return redirect()->back()->with('success', 'System entered MAINTENANCE MODE. Access via secret: ' . $secret);
            }
        } catch (\Exception $e) {
            Log::error('Maintenance Toggle Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to change maintenance state: ' . $e->getMessage());
        }
    }

    /**
     * Clear System Cache
     */
    public function clearCache()
    {
        try {
            // 1. Clear Laravel's internal optimization cache
            Artisan::call('optimize:clear');
            
            // 2. Clear the actual data cache through the facade
            Cache::flush();
            
            // 3. Force Truncate the database tables (Big Tech Style - No Mercy)
            // This ensures phpMyAdmin will show 0 rows.
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::table('cache')->truncate();
            
            // Also clear cache locks to prevent stuck processes
            if (Schema::hasTable('cache_locks')) {
                DB::table('cache_locks')->truncate();
            }
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            Log::info('System cache FORCE CLEARED by admin.');
            return redirect()->back()->with('success', 'System cache and database tables cleared completely.');
        } catch (\Exception $e) {
            Log::error('Cache Clear Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to clear cache: ' . $e->getMessage());
        }
    }

    /**
     * Database Backup (Professional PHP Export)
     */
    public function databaseBackup(\App\Services\System\DatabaseBackupService $backupService)
    {
        try {
            $filePath = $backupService->generateBackup();
            return response()->download($filePath)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('DB Backup Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to generate backup: ' . $e->getMessage());
        }
    }
}
