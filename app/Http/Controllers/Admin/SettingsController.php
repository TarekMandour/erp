<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;
use Validator;

class SettingsController extends Controller
{

    public function edit($id)
    {
        $data = Setting::find($id);
        return view('admin.settings.edit', compact('data'));
    }

    public function update(Request $request)
    {

        $items = $request->except('_token');
        $data = Setting::find(1);
        $data->update($items);

        if($request->hasFile('logo') && $request->file('logo')->isValid()){
            $data->addMediaFromRequest('logo')->toMediaCollection('logo');
        }

        if($request->hasFile('logodark') && $request->file('logodark')->isValid()){
            $data->addMediaFromRequest('logodark')->toMediaCollection('logoDark');
        }

        if($request->hasFile('fav') && $request->file('fav')->isValid()){
            $data->addMediaFromRequest('fav')->toMediaCollection('fav'); 
        }

        if($request->hasFile('breadcrumb') && $request->file('breadcrumb')->isValid()){
            $data->addMediaFromRequest('breadcrumb')->toMediaCollection('breadcrumb');
        }

        return redirect('admin/settings/edit/1')->with('message', 'تم التعديل بنجاح')->with('status', 'success');
    }

}
