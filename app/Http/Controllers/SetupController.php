<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class SetupController extends Controller
{
    /**
     * Show setup page
     */
    public function index()
    {
        // Check if already installed
        if ($this->isInstalled()) {
            return redirect('/admin')->with('info', 'System is already installed.');
        }
        
        return view('setup.index', [
            'requirements' => $this->checkRequirements(),
            'permissions' => $this->checkPermissions(),
        ]);
    }
    
    /**
     * Run installation
     */
    public function install(Request $request)
    {
        if ($this->isInstalled()) {
            return redirect('/admin');
        }
        
        $request->validate([
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|max:255',
            'admin_password' => 'required|min:8|confirmed',
            'clinic_name' => 'required|string|max:255',
            'clinic_phone' => 'required|string|max:20',
        ]);
        
        try {
            // Run migrations
            Artisan::call('migrate', ['--force' => true]);
            
            // Create admin user
            $admin = User::create([
                'name' => $request->admin_name,
                'email' => $request->admin_email,
                'password' => Hash::make($request->admin_password),
                'role' => 'admin',
                'is_active' => true,
            ]);
            
            // Update clinic settings
            DB::table('settings')->updateOrInsert(
                ['key' => 'clinic_name'],
                ['value' => $request->clinic_name, 'group' => 'clinic']
            );
            
            DB::table('settings')->updateOrInsert(
                ['key' => 'clinic_phone'],
                ['value' => $request->clinic_phone, 'group' => 'clinic']
            );
            
            // Run seeders
            Artisan::call('db:seed', ['--force' => true]);
            
            // Create installed marker
            File::put(storage_path('installed'), date('Y-m-d H:i:s'));
            
            // Clear caches
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('view:clear');
            
            // Log the user in
            auth()->login($admin);
            
            return redirect('/admin')->with('success', 'Installation completed successfully!');
            
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Installation failed: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Check if system is installed
     */
    private function isInstalled(): bool
    {
        return File::exists(storage_path('installed')) || 
               DB::table('users')->where('role', 'admin')->exists();
    }
    
    /**
     * Check system requirements
     */
    private function checkRequirements(): array
    {
        return [
            'PHP Version >= 8.1' => version_compare(PHP_VERSION, '8.1.0', '>='),
            'BCMath Extension' => extension_loaded('bcmath'),
            'Ctype Extension' => extension_loaded('ctype'),
            'JSON Extension' => extension_loaded('json'),
            'Mbstring Extension' => extension_loaded('mbstring'),
            'OpenSSL Extension' => extension_loaded('openssl'),
            'PDO Extension' => extension_loaded('pdo'),
            'PDO MySQL Extension' => extension_loaded('pdo_mysql'),
            'Tokenizer Extension' => extension_loaded('tokenizer'),
            'XML Extension' => extension_loaded('xml'),
            'GD Extension' => extension_loaded('gd'),
            'Fileinfo Extension' => extension_loaded('fileinfo'),
        ];
    }
    
    /**
     * Check directory permissions
     */
    private function checkPermissions(): array
    {
        return [
            'storage' => is_writable(storage_path()),
            'storage/app' => is_writable(storage_path('app')),
            'storage/framework' => is_writable(storage_path('framework')),
            'storage/logs' => is_writable(storage_path('logs')),
            'bootstrap/cache' => is_writable(base_path('bootstrap/cache')),
        ];
    }
}