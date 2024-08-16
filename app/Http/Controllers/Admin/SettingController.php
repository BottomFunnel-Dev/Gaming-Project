<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;

use App\Setting;
use Auth;
use Illuminate\Support\Facades\Validator;

class SettingController extends Controller
{
    protected $paging   =   100;

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(Request $request)
    {        
        $settings = Setting::pluck('field_value');
        //echo "<pre>";print_r($settings[1]);die;
        return view('admin/setting/index',compact('settings'));
    }

    public function update(Request $request)
    {         
        //echo "<pre>";print_r($request->all());die;
        //echo strtotime('+5 minutes');die;
        try
        {
            $rcode           =  0;
            $room_code       =   Setting::find(1);
            $room_code_time  =   Setting::find(2);
            $maintainance  =   Setting::find(3);
            $auto_withdraw  =   Setting::find(4);
            $auto_withdraw_setting  =   Setting::find(6);
            $GatewayChoice_setting  =   Setting::find(7);
            $WithdrawalStatus  =   Setting::find(8);

            if(isset($request->auto_room_code)){
                $rcode       =  1;
            }

            $room_code->field_value =  $rcode; 
            $room_code->save(); 

            $room_code_time->field_value    =   $request->room_code_expire_in;
            $room_code_time->save();
            
            $maintainance->field_value    =   $request->maintainance_mode;
            $maintainance->save();
            
            $auto_withdraw->field_value    =   $request->auto_withdraw;
            $auto_withdraw->save();
            
            $auto_withdraw_setting->field_value    =   $request->notice;
            $auto_withdraw_setting->save();
            
            $GatewayChoice_setting->field_value    =   $request->GatewayChoice;
            $GatewayChoice_setting->save();
            
            $WithdrawalStatus->field_value    =   $request->WithdrawalStatus;
            $WithdrawalStatus->save();

            return redirect()->back()->with('success', 'Room codes added successfully!');            
        }catch (\Exception $e) {
            $bug = $e->getMessage();
            return redirect()->back()->with('error', $bug);
        }
    }

}