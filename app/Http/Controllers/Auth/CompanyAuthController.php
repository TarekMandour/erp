<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CompanyLoginRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Company;
use App\Models\CompanyUser;

class CompanyAuthController extends Controller
{
    public function showLoginForm()
    {
        // Check if already logged in as company
        if (Auth::guard('company')->check()) {
            return redirect()->route('company.dashboard');
        }
        
        return view('auth.company.login');
    }

    public function login(Request  $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = CompanyUser::where('email', $request->email)->first();

        if ($user) {
            $company = Company::find($user->company_id);

            if ($company->is_active == 1) {

                if ($user->is_active == 1) {
                    if (Auth::guard('company')->attempt($credentials)) {
                        $request->session()->regenerate();
                        if ($user->type == 'super') {
                            return redirect()->intended('/company/dashboard');
                        } else {
                            return redirect()->intended('/company/chat');
                        }
                        
                    } else {
                        return back()->withErrors([
                            'email' => 'عفوا ، بيانات الدخول غير متطابقه',
                        ]);
                    }
                } elseif ($user->is_active == 0) {
                    return back()->withErrors([
                        'email' => 'عفوا ، هذا الحساب غير مفعل',
                    ]);
                } elseif ($user->is_active == 2) {
                    return back()->withErrors([
                        'email' => 'عفوا ، هذا الحساب تم حظره',
                    ]);
                }

            } elseif ($company->is_active == 0) {
                return back()->withErrors([
                    'email' => 'عفوا ، هذا الحساب غير مفعل',
                ]);
            } elseif ($company->is_active == 2) {
                return back()->withErrors([
                    'email' => 'عفوا ، هذا الحساب تم حظره',
                ]);
            }
        } else {
            return back()->withErrors([
                'email' => 'عفوا ، هذا الحساب غير موجود',
            ]); 
        }
        

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/company/login');
    }
}
