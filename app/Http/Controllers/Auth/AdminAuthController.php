<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AdminLoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.admin.login');
    }

    public function login(Request $request)
    {

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // QA 1.6: الدخول للمدير المفعّل فقط (is_active = 1)
        if (Auth::guard('admin')->attempt($credentials + ['is_active' => '1'])) {
            $request->session()->regenerate();
            return redirect()->intended('/admin/dashboard');
        }

        $admin = \App\Models\Admin::where('email', $credentials['email'])->first();
        if ($admin && \Illuminate\Support\Facades\Hash::check($credentials['password'], $admin->password)) {
            return back()->onlyInput('email')->withErrors([
                'email' => (string) $admin->is_active === '2' ? 'هذا الحساب محظور، تواصل مع مدير النظام' : 'هذا الحساب غير مفعّل، تواصل مع مدير النظام',
            ]);
        }

        return back()->onlyInput('email')->withErrors([
            'email' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/admin/login');
    }
}
