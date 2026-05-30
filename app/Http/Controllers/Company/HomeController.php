<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use App\Models\Conversation;
use App\Models\Message;

class HomeController extends Controller
{
    public function index(Request $request) {
        return view('company.dashboard');
    }

    public function changLang(Request $request) {
        // App::setLocale($request->lang);
        session()->put('locale', $request->lang);

        return redirect()->back();
    }

    public function webhookHndle (Request $request) {
        
        $conversation = Conversation::create([
            'company_id' => 1,
            'company_user_id' => NULL,
            'name' => NULL,
            'phone' => $request->phone,
            'type' => 'customer',
            'is_read' => 'unread',
            'status' => 'new'
        ]);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'company_user_id' => NULL,
            'type' => 'msg',
            'status' => 'unread',
            'sender_type' => 'customer',
            'sender_phone' => $request->phone,
            'receiver_type' => 'emp',
            'receiver_phone' => '201006287379'
        ]);

        return true;

    }
}
