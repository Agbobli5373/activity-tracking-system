<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle login request with employee_id or email.
     */
    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $loginField = $request->input('login');
        $password = $request->input('password');

        // Determine if login field is email or employee_id
        $fieldType = filter_var($loginField, FILTER_VALIDATE_EMAIL) ? 'email' : 'employee_id';

        // Find user by email or employee_id
        $user = User::where($fieldType, $loginField)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            // Log failed login attempt - don't pass user ID if user doesn't exist
            AuditService::logAuth('login_failed', null, $request);
            
            throw ValidationException::withMessages([
                'login' => ['The provided credentials are incorrect.'],
            ]);
        }

        Auth::login($user, $request->boolean('remember'));

        $request->session()->regenerate();

        // Log successful login
        AuditService::logAuth('login_success', $user->id, $request);

        return redirect()->intended('/dashboard');
    }

    /**
     * Handle logout request.
     */
    public function logout(Request $request)
    {
        $userId = Auth::id();
        
        // Log logout before actually logging out
        AuditService::logAuth('logout', $userId, $request);
        
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}