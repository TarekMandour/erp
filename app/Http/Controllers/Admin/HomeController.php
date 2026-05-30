<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class HomeController extends Controller
{
    public function index(Request $request) {

        return view('admin.dashboard');
    }

    public function changLang(Request $request) {
        // App::setLocale($request->lang);
        session()->put('locale', $request->lang);

        return redirect()->back();
    }
}
