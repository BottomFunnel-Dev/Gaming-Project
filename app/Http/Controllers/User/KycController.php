<?php

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
use Auth;
use App\User;
use App\UserData;
use Session;
use Illuminate\Support\Facades\Http;

class KycController
{

    public function step1()
    {
        $user_id = Auth::user()->id;
        return view('user.kyc_new');
    }

    public function check_aadhar(Request $request)
    {
        $user_id = Auth::user()->id;
        if (isset($request->ref_id)) {
            // $response = Http::attach('otp',$request->otp)
            //     ->attach('ref_id',$request->ref_id)
            //     ->attach('auth',$request->auth)
            //     ->withHeaders([
            //         'Accept'=> '*/*',
            //     ])
            //     ->post('https://mothersolution.in/api/aadhar_otp_verify');
            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL => "https://mothersolution.in/api/aadhar_otp_verify",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => "-----011000010111000001101001\r\nContent-Disposition: form-data; name=\"otp\"\r\n\r\n$request->otp\r\n-----011000010111000001101001\r\nContent-Disposition: form-data; name=\"ref_id\"\r\n\r\n$request->ref_id\r\n-----011000010111000001101001\r\nContent-Disposition: form-data; name=\"auth\"\r\n\r\n$request->auth\r\n-----011000010111000001101001--\r\n",
                CURLOPT_HTTPHEADER => [
                    "Accept: */*",
                    "User-Agent: Thunder Client (https://www.thunderclient.com)",
                    "content-type: multipart/form-data; boundary=---011000010111000001101001"
                ],
            ]);

            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);
            $lastdata = json_decode($response);
            // return $lastdata;
            if ($lastdata->code == 200) {
                $user_data_details = UserData::where('user_id', $user_id)->first();
                $user_data_details->DOCUMENT_FIRST_NAME = $lastdata->data->name;
                $user_data_details->DOCUMENT_DOB = $lastdata->data->dob;
                $user_data_details->verify_status = 1;
                $user_data_details->DOCUMENT_STATE =
                    $lastdata->data->split_address->street
                    . ', ' . $lastdata->data->split_address->vtc
                    . ', ' . $lastdata->data->split_address->po
                    . ', ' . $lastdata->data->split_address->subdist
                    . ', ' . $lastdata->data->split_address->dist
                    . ', ' . $lastdata->data->split_address->state
                    . ', ' . $lastdata->data->split_address->country
                    . ', ' . $lastdata->data->split_address->pincode;
                $user_data_details->save();
                return redirect('/complete-kyc/approve')->with('success', "Aadhar card Registered");
            }
            return back()->with('error', "Something wents wrong!");
        } else {
            $document_name = "UID";
            $document_number = $request->aadhar;
            $exist = UserData::where('DOCUMENT_NUMBER', $document_number)->where('verify_status', 1)->count();
            if ($exist > 0) {
                return back()->with('error', "Aadhar card already exist");
            }

            $user_data_details = UserData::where('user_id', $user_id)->first();
            $user_data_details->DOCUMENT_NAME = $document_name;
            $user_data_details->DOCUMENT_NUMBER = $document_number;
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://mothersolution.in/api/aadhar_verify',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => '{
      "aadhaar_number":' . $document_number . ',
      "userid":"MP15751"
  }',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                ),
            )
            );

            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);
            $data = json_decode($response);
            if ($data->status && isset($data->data->code) && $data->data->code == 200) {
                if (isset($data->trn)) {
                    $ref_id = $data->trn;
                    $user_data_details->save();
                    $user_data_details->ref_id = $ref_id;
                    return back()->with('otp', [$ref_id, $data->auth]);
                } else {
                    return back()->with('error', $data->data->message);
                }
            }
            return back()->with('error', "Something wents wrong");
        }
    }
    public function saveStep1(Request $request)
    {
        $user_id = Auth::user()->id;
        $document_name = "UID";
        $document_number = $request->DOCUMENT_NUMBER;
        $exist = UserData::where('DOCUMENT_NUMBER', $document_number)->where('verify_status', 1)->count();
        if ($exist > 0) {
            return back()->with('error', "Aadhar card already exist");
        }
        if ($request->hasFile('frontPic')) {
            $frontPic = $request->file('frontPic');
            $frontPic_name = time() . 'frontpic.' . $frontPic->getClientOriginalExtension();
            $destinationPath = public_path('/images/kycdata/' . $user_id . '/');
            $frontPic->move($destinationPath, $frontPic_name);
        }
        if ($request->hasFile('backPic')) {
            $backPic = $request->file('backPic');
            $backPic_name = time() . 'backPic.' . $backPic->getClientOriginalExtension();
            $destinationPath = public_path('/images/kycdata/' . $user_id . '/');
            $backPic->move($destinationPath, $backPic_name);
        }
        $user_data_details = UserData::where('user_id', $user_id)->first();
        $user_data_details->DOCUMENT_FIRST_NAME = $request->fname;
        $user_data_details->DOCUMENT_LAST_NAME = $request->lname;
        $user_data_details->DOCUMENT_NAME = $document_name;
        $user_data_details->DOCUMENT_NUMBER = $document_number;
        $user_data_details->DOCUMENT_FRONT_IMAGE = $frontPic_name;
        $user_data_details->DOCUMENT_BACK_IMAGE = $backPic_name;
        $user_data_details->verify_status = 2;
        if ($user_data_details->save()) {
            return redirect('/complete-kyc/kyc-submit');
        }
        return back()->with('error', "Something wents wrong");
    }

    public function step2(Request $request)
    {

        $user_id = Auth::user()->id;
        $user_data_details = UserData::where('user_id', $user_id)->first();

        return view('user.kyc_step2', compact('user_data_details'));

    }

    public function saveStep2(Request $request)
    {

        $user_id = Auth::user()->id;

        $firstName = $request->firstName;
        $lastName = $request->lastName;
        $dob = $request->dob;
        $state = $request->state;

        $user_data_details = UserData::where('user_id', $user_id)->first();
        $user_data_details->DOCUMENT_FIRST_NAME = $firstName;
        $user_data_details->DOCUMENT_LAST_NAME = $lastName;
        $user_data_details->DOCUMENT_DOB = $dob;
        $user_data_details->DOCUMENT_STATE = $state;
        $user_data_details->save();
        //dd($user_data_details);
        return redirect('/complete-kyc/step3');
    }

    public function step3(Request $request)
    {
        $user_id = Auth::user()->id;
        $user_data_details = UserData::where('user_id', $user_id)->first();
        return view('user.kyc_step3', compact('user_data_details'));
    }

    public function saveStep3(Request $request)
    {
        $user_id = Auth::user()->id;
        if ($request->hasFile('frontPic')) {
            $frontPic = $request->file('frontPic');
            $frontPic_name = time() . 'frontpic.' . $frontPic->getClientOriginalExtension();
            $destinationPath = public_path('/images/kycdata/' . $user_id . '/');
            $frontPic->move($destinationPath, $frontPic_name);
        }
        if ($request->hasFile('backPic')) {
            $backPic = $request->file('backPic');
            $backPic_name = time() . 'backPic.' . $backPic->getClientOriginalExtension();
            $destinationPath = public_path('/images/kycdata/' . $user_id . '/');
            $backPic->move($destinationPath, $backPic_name);
        }

        $user_data_details = UserData::where('user_id', $user_id)->first();
        $user_data_details->DOCUMENT_FRONT_IMAGE = $frontPic_name;
        $user_data_details->DOCUMENT_BACK_IMAGE = $backPic_name;
        $user_data_details->verify_status = 0;
        $user_data_details->save();

        // dd($user_data_details);
        return redirect('/complete-kyc/kyc-submit');
    }

    public function kyc_submit(Request $request)
    {
        return view('user.kyc_submit');
    }
    public function kyc_approve(Request $request)
    {
        $user_id = Auth::user()->id;
        $data = UserData::where('user_id', $user_id)->first();
        return view('user.kyc_approve', compact('data'));
    }

}
