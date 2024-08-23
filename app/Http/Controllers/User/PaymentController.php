<?php

namespace App\Http\Controllers\User;

use App\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Auth;

use LoveyCom\CashFree\PaymentGateway\Order;
use App\PaymentOrder;
// use App\Log;

use Illuminate\Support\Facades\Log;
use App\Transaction;
use GuzzleHttp\Client;
use Paykun\Checkout\Payment;
use Carbon\Carbon;
use App\Setting;

class PaymentController
{
    public function paymentGatewayRes(Request $request)
    {
        $pCheck = PaymentOrder::where('order_id', $request['orderId'])->first();
        $user_id = $pCheck->user_id;
        $amount = $request['orderAmount'];
        $user_data = User::where('id', $user_id)->first();
        $wallet = $user_data->wallet;

        if ($request['txStatus'] == 'SUCCESS' && $pCheck->status == 0 && $amount == $pCheck->amount) {
            $txn = Transaction::create([
                'user_id' => $user_id,
                'source_id' => $request['orderId'],
                'amount' => $amount,
                'a_amount' => 0,
                'status' => 'Wallet',
                'remark' => 'Cashfree wallet recharge',
                'ip' => $request->ip(),
                'closing_balance' => $wallet + $amount,

            ]);

            if ($txn) {
                $pCheck->status = 1;
                $pCheck->save();

                User::where('id', $user_id)->increment('wallet', $amount);

                return redirect('dashboard')->with('payment_status', $request['txMsg']);
            }
        }

        if ($request['txStatus'] == 'FAILED') {
            return redirect('dashboard')->with('payment_status', $request['txMsg']);
        }

        die("Invalid request. <a href=" . route('dashboard') . ">Click here to go home</a>");
    }

    public function paymentGatewayResWebhook()
    {
        $pChecks = PaymentOrder::where('status', 0)->latest()->get();
        foreach ($pChecks as $row) {
            $dateAndTime = $row->created_at;
            $carbonDate = Carbon::parse($dateAndTime);

            $date = $carbonDate->format('d-m-Y');
            $orderid = $row->order_id;
            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL => "https://api.ekqr.in/api/check_order_status",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => "{\r\n  \"key\": \"8e4fb829-a510-4353-92ef-4f671e02edad\",\r\n  \"client_txn_id\": \"$orderid\",\r\n  \"txn_date\": \"$date\"\r\n}",
                CURLOPT_HTTPHEADER => [
                    "Accept: */*",
                    "Content-Type: application/json"
                ],
            ]);

            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);

            if ($err) {
                echo "cURL Error #:" . $err;
            } else {

                $request = json_decode($response);
                // return $response;
                $pCheck = PaymentOrder::where('id', $row->id)->first();
                $user_id = $pCheck->user_id;
                $amount = $request->data->amount;
                $user_data = User::where('id', $user_id)->first();
                $wallet = $user_data->wallet;
                if ($request->data->status == 'success') {
                    $txn = Transaction::create([
                        'user_id' => $user_id,
                        'source_id' => $request->data->client_txn_id,
                        'amount' => $amount,
                        'a_amount' => 0,
                        'status' => 'Wallet',
                        'remark' => 'UpiGateway wallet recharge',
                        'ip' => "000000",
                        'closing_balance' => $wallet + $amount,
                    ]);

                    if ($txn) {
                        $pCheck->status = 1;
                        $pCheck->save();
                        User::where('id', $user_id)->increment('wallet', $amount);
                        // return redirect('dashboard')->with('payment_status', $request['txMsg']);
                    }
                }
            }
        }
    }

    private function checkDepositStatus() {
        $DepositStatusS = Setting::where('key', 'DepositStatusS')->value('value');
        if ($DepositStatusS == "no") {
            return redirect('/wallet')->with('error', 'Deposit currently paused due to some Bank issue, Please try after some time!');
        }
    }

    public function addMoney(Request $request)
    {
        return view('user.add-money');
    }

    public function addMoneyChk(Request $request)
    {
        $statusCheck = $this->checkDepositStatus();
        if ($statusCheck) return $statusCheck;
        return view('user.add-money-chk');
    }


    public function createOrderChk(Request $request)
    {
        $DepositStatusS = Setting::where('key', 'DepositStatus')->value('value');

        if ($DepositStatusS == "no") {
            return redirect('/wallet')->with('error', 'Deposits currently paused due to some Bank issue, Please try after some time!');
        }

        return redirect("https://google.com");
    }
    public function createOrder(Request $request)
    {
        // Retrieve the deposit status setting directly
        $DepositStatusS = Setting::where('key', 'DepositStatusS')->value('value');

        if ($DepositStatusS == "no") {
            return redirect('/wallet')->with('error', 'Deposit currently paused due to some Bank issue, Please try after some time!');
        }

        $request->validate([
            'orderAmount' => 'required|numeric|gt:0|between:1,20000',
        ]);

        $gateway = 'UPI-Gateway';
        $user_id = Auth::user()->id;
        $order_id = $gateway . '-' . time() . '-' . $user_id;
        //$key 		=	'4a439b05-1871-4070-b8cd-d3f7361603c0';
        //$key 		=	'd0300afb-792e-4548-925a-1dd884f93cf9';
        $key = '08b3ae69-431b-4da8-b05e-a5669083d839';

        $order = PaymentOrder::create([
            'user_id' => $user_id,
            'order_id' => $order_id,
            'amount' => $request->orderAmount,
            'gateway' => $gateway,
            'ip' => $request->ip()
        ]);

        // if($gateway	==	'Paykun' && $order){
        // 	$this->orderPaykun($request->orderAmount,$order_id);
        // }

        // if($gateway	==	'Cashfree' && $order){
        // 	$this->orderCashfree($request->orderAmount,$order_id);
        // }

        $content = json_encode(
            array(
                "key" => $key,
                "client_txn_id" => $order_id, // order id or your own transaction id
                "amount" => $request->orderAmount,
                "p_info" => "Product Name",
                "customer_name" => Auth::user()->username, // customer name
                "customer_email" => "khelmoj.in@gmail.com", // customer email
                "customer_mobile" => Auth::user()->mobile, // customer mobile number
                "redirect_url" => url('upi-gateway-res'), // redirect url after payment, with ?client_txn_id=&txn_id=
                "udf1" => "user defined field 1", // udf1, udf2 and udf3 are used to save other order related data, like customer id etc.
                "udf2" => "user defined field 2",
                "udf3" => "user defined field 3",
            )
        );
        $url = "https://merchant.upigateway.com/api/create_order";
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt(
            $curl,
            CURLOPT_HTTPHEADER,
            array("Content-type: application/json")
        );
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $content);
        $json_response = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($status != 200) {
            // You can handle Error yourself.
            die("Error: call to URL $url failed with status $status, response $json_response, curl_error " . curl_error($curl) . ", curl_errno " . curl_errno($curl));
        }
        curl_close($curl);
        $response = json_decode($json_response, true);
        if ($response["status"] == true) {
            // Method 1
            // redirect to payment page of UPI
            //header("Location: ".$response["data"]["payment_url"]);
            //die();
            // Method 2
            echo "<script>window.location.href='" . $response["data"]["payment_url"] . "'</script>";
            die();
        } else {
            echo $response['msg'];
            die('error');
        }

    }

    private $api_id = '1238133a21244c6d4c21dd5e99318321';
    private $secret = 'f11179732a9a548411ac81f76e81fc87199f9f87';

    // private $api_id = 'TEST4182141935d4690b1b7ad0642e412814';
    // private  $secret = 'TESTe0325c737ee07700f05ebd09c9cb990c25802279';

    // 	public function createOrder(Request $request)
//     {
// 		$request->validate([
//             'orderAmount' => 'required|numeric|gt:0|between:1,20000',
//         ]);

    // 		$gateway	=	'UPI-Gateway';
// 		$user_id	=	Auth::user()->id;
// 		$order_id	=	$gateway.'-'.time().'-'.$user_id;

    // 		$order		=	PaymentOrder::create([
// 			'user_id'	=>	$user_id,
// 			'order_id'	=>	$order_id,
// 			'amount'	=>	$request->orderAmount,
// 			'gateway'	=>	$gateway,
// 			'ip'		=>	$request->ip()
// 		]);

    // 		// if($gateway	==	'Paykun' && $order){
// 		// 	$this->orderPaykun($request->orderAmount,$order_id);
// 		// }

    // 		// if($gateway	==	'Cashfree' && $order){
// 		// 	$this->orderCashfree($request->orderAmount,$order_id);
// 		// }
// 		$url = "https://api.cashfree.com/pg/orders";
// // 		$url = "https://sandbox.cashfree.com/pg/orders";
// 		$headers = array(
//             "Content-Type: application/json",
//             "x-api-version: 2022-01-01",
//             "x-client-id: " . $this->api_id,
//             "x-client-secret: " . $this->secret
//         );
//         $data = json_encode([
//             'order_id' => $order_id,
//             'order_amount' => $request->orderAmount,
//             "order_currency" => "INR",
//             "customer_details" => [
//                 "customer_id" => "UHG".Auth::user()->id,
//                 "customer_name" => Auth::user()->username,
//                 "customer_email" => 'jhon@gmail.com',
//                 "customer_phone" => '+91' . Auth::user()->mobile,
//             ],
//             "order_meta" => [
//                 "return_url" => 'https://rajasthaniludo.com/api/recharge-payment-status?order_id={order_id}&order_token={order_token}'
//             ]
//         ]);

    //         $curl = curl_init($url);

    //         curl_setopt($curl, CURLOPT_URL, $url);
//         curl_setopt($curl, CURLOPT_POST, true);
//         curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
//         curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
//         curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

    //         $resp = curl_exec($curl);

    //         curl_close($curl);
//         // return $resp;
//         return redirect()->to(json_decode($resp)->payment_link);

    //         // $response = json_decode($resp);
// // 		$url = "https://merchant.upigateway.com/api/create_order";
// // 		$curl = curl_init($url);
// // 		curl_setopt($curl, CURLOPT_HEADER, false);
// // 		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
// // 		curl_setopt($curl, CURLOPT_HTTPHEADER,
// // 				array("Content-type: application/json"));
// // 		curl_setopt($curl, CURLOPT_POST, true);
// // 		curl_setopt($curl, CURLOPT_POSTFIELDS, $content);
// // 		$json_response = curl_exec($curl);
// // 		$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
// // 		if ( $status != 200 ) {
// // 			// You can handle Error yourself.
// // 			die("Error: call to URL $url failed with status $status, response $json_response, curl_error " . curl_error($curl) . ", curl_errno " . curl_errno($curl));
// // 		}
// // 		curl_close($curl);
// // 		$response = json_decode($json_response, true);
// 		return $response['msg']; die('errro');
//     }


    // public function createOrdernew(Request $request)
//     {
// 		$request->validate([
//             'orderAmount' => 'required|numeric|gt:0|between:1,20000',
//         ]);

    // 		$gateway	=	'PHONEPe';
// 		$user_id	=	Auth::user()->id;
// 		$order_id	=	"MT".date("ymdhisymddmm");
// 		$amount = $request->orderAmount;
// 		$order		=	PaymentOrder::create([
// 			'user_id'	=>	$user_id,
// 			'order_id'	=>	$order_id,
// 			'amount'	=>	$request->orderAmount,
// 			'gateway'	=>	$gateway,
// 			'ip'		=>	$request->ip()
// 		]);
// 		$data = '{
//         	"APIID": "API1004",
//         	"Token": "6091fba5-1c3e-47ff-b9dc-0b784818b08b",
//         	"MethodName": "pg",
//         	"OrderID":"'.$order_id.'",
//             "merchantUserId":"'.$user_id.'",
//             "mobileNumber":"",
//             "amount":"'.($amount*100).'",
//             "redirectUrl":"https://rajasthaniludo.com/api/recharge-payment-status",
//             "callbackUrl":"https://rajasthaniludo.com/api/recharge-payment-status"
//         }';
// 		$curl = curl_init();

    //         curl_setopt_array($curl, [
//           CURLOPT_URL => "https://ibrpay.com/api/PG.aspx",
//           CURLOPT_RETURNTRANSFER => true,
//           CURLOPT_ENCODING => "",
//           CURLOPT_MAXREDIRS => 10,
//           CURLOPT_TIMEOUT => 30,
//           CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
//           CURLOPT_CUSTOMREQUEST => "POST",
//           CURLOPT_POSTFIELDS => $data,
//           CURLOPT_HTTPHEADER => [
//             "Accept: */*",
//             "Content-Type: application/json",
//           ],
//         ]);

    //         $response = curl_exec($curl);
//         $err = curl_error($curl);

    //         curl_close($curl);

    //         if ($err) {
//           echo "cURL Error #:" . $err;
//         } else {
//             // return array("request"=>$data,"response"=>$response);
//             $data = json_decode($response);
//             if(isset($data->data->instrumentResponse->redirectInfo->url)){
// 		    return redirect($data->data->instrumentResponse->redirectInfo->url);
//             }else{
//                 return back()->with("error","Something wents wrong");
//             }
//         }
// // 		$url = "https://alshuindia.com/Payment-initiate.php?amount=$amount&userid=$user_id&orderid=$order_id";
//     }
// 	public function createOrdernew(Request $request)
//     {
// 		$request->validate([
//             'orderAmount' => 'required|numeric|gt:0|between:10,100000',
//         ]);

    // 		$gateway	=	'PAYg';
// 		$user_id	=	Auth::user()->id;
// 		$order_id	=	"MT".date("ymdhisymddmm");
// 		$amount = $request->orderAmount;

    // 		$data = '{
//         	"APIID": "API1004",
//         	"Token": "6091fba5-1c3e-47ff-b9dc-0b784818b08b",
//         	"MethodName": "collectionrequest",
//             "amount":"'.($amount).'",
//             "redirect_url":"https://rajasthaniludo.com",
//         }';
// 		$curl = curl_init();
//         curl_setopt_array($curl, [
//           CURLOPT_URL => "https://ibrpay.com/api/GetAmount.aspx",
//           CURLOPT_RETURNTRANSFER => true,
//           CURLOPT_ENCODING => "",
//           CURLOPT_MAXREDIRS => 10,
//           CURLOPT_TIMEOUT => 30,
//           CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
//           CURLOPT_CUSTOMREQUEST => "POST",
//           CURLOPT_POSTFIELDS => $data,
//           CURLOPT_HTTPHEADER => [
//             "Accept: */*",
//             "Content-Type: application/json",
//           ],
//         ]);

    //         $response = curl_exec($curl);
//         $err = curl_error($curl);

    //         curl_close($curl);

    //         if ($err) {
//           echo "cURL Error #:" . $err;
//         } else {
//             $data = json_decode($response);
//             // return $data->data;
//             // return array("request"=>$data,"response"=>$response);
//             $order		=	PaymentOrder::create([
// 			'user_id'	=>	$user_id,
// 			'order_id'	=>	$data->data->OrderKeyId,
// 			'amount'	=>	$request->orderAmount,
// 			'gateway'	=>	$gateway,
// 			'ip'		=>	$request->ip()
// 		    ]);
//             if(isset($data->data->PaymentProcessUrl)){
// 		    return redirect($data->data->PaymentProcessUrl);
//             }else{
//                 return back()->with("error","Something wents wrong");
//             }
//         }
// // 		$url = "https://alshuindia.com/Payment-initiate.php?amount=$amount&userid=$user_id&orderid=$order_id";
//     }
//     public function createOrdernew(Request $request)
//     {
// 		$request->validate([
//             'orderAmount' => 'required|numeric|gt:0|between:10,20000',
//         ]);
// 		$amount = $request->orderAmount;
//         // if($amount >= 500000000000000){
// 		  //$gateway	=	'RazorPay';
// 		  //$user_id	=	Auth::user()->id;
// 		  //$mobile	=	Auth::user()->mobile;
// 		  //$order_id	=	"RZRPay".date("ymdhisymddmm");
// 		  //$order		=	PaymentOrder::create([
// 		  //	'user_id'	=>	$user_id,
// 		  //	'order_id'	=>	$order_id,
// 		  //	'amount'	=>	$request->orderAmount,
// 		  //	'gateway'	=>	$gateway,
// 		  //	'ip'		=>	$request->ip()
// 		  //]);
// 		  //$url = "https://alshuindia.com/payment_integrate.php?am=$amount&userid=$user_id&trn=$order_id&mob=$mobile";
// 		  //return redirect($url);
//     //     }
//     //     elseif($amount >= 200){
//     //         $gateway	=	'Cashfree';
// 		  //$user_id	=	Auth::user()->id;
// 		  //$mobile	=	Auth::user()->mobile;
// 		  //$order_id	=	"CHFRE".date("ymdhisymddmm");
// 		  //$order		=	PaymentOrder::create([
// 		  //	'user_id'	=>	$user_id,
// 		  //	'order_id'	=>	$order_id,
// 		  //	'amount'	=>	$request->orderAmount,
// 		  //	'gateway'	=>	$gateway,
// 		  //	'ip'		=>	$request->ip()
// 		  //]);
// 		  //$url = "https://aktraders.org/cashfree/?am=$amount&userid=$user_id&trn=$order_id&mob=$mobile";
// 		  ////return $url;
// 		  //return redirect($url);
//     //     }
//     //     else{
//     // UPIGATEWAY
//           $gateway	=	'UPI-Gateway';
// 		  $user_id	=	Auth::user()->id;
// 		  $order_id	=	$gateway.'-'.time().'-'.$user_id;
// 		  //$key 		=	'08b3ae69-431b-4da8-b05e-a5669083d839';

    // 		  $order		=	PaymentOrder::create([
// 		  	'user_id'	=>	$user_id,
// 		  	'order_id'	=>	$order_id,
// 		  	'amount'	=>	$request->orderAmount,
// 		  	'gateway'	=>	$gateway,
// 		  	'ip'		=>	$request->ip()
// 		  ]);

    // 		$key 		=	'08b3ae69-431b-4da8-b05e-a5669083d839';
// 		  $content = json_encode(array(
// 		  	"key"=> $key,
// 		  	"client_txn_id"=> $order_id, // order id or your own transaction id
// 		  	"amount"=> $request->orderAmount,
// 		  	"p_info"=> "Product Name",
// 		  	"customer_name"=> Auth::user()->username, // customer name
// 		  	"customer_email"=> "alshuindia@gmail.com", // customer email
// 		  	"customer_mobile"=> Auth::user()->mobile, // customer mobile number
// 		  	"redirect_url"=> url('/'), // redirect url after payment, with ?client_txn_id=&txn_id=
// 		  	"udf1"=> "user defined field 1", // udf1, udf2 and udf3 are used to save other order related data, like customer id etc.
// 		  	"udf2"=> "user defined field 2",
// 		  	"udf3"=> "user defined field 3",
// 		  ));
// 		  $url = "https://merchant.upigateway.com/api/create_order";
// 		  //$curl = curl_init($url);
// 		  //curl_setopt($curl, CURLOPT_HEADER, false);
// 		  //curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
// 		  //curl_setopt($curl, CURLOPT_HTTPHEADER,
// 		  //		array("Content-type: application/json"));
// 		  //curl_setopt($curl, CURLOPT_POST, true);
// 		  //curl_setopt($curl, CURLOPT_POSTFIELDS, $content);
// 		  //$json_response = curl_exec($curl);
// 		  //$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
// 		  //if ( $status != 200 ) {
// 		  //	die("Error: call to URL $url failed with status $status, response $json_response, curl_error " . curl_error($curl) . ", curl_errno " . curl_errno($curl));
// 		  //}
// 		  //curl_close($curl);
// 		  //$response = json_decode($json_response, true);
// 		  //if($response["status"] == true){
// 		  //	echo "<script>window.location.href='".$response["data"]["payment_url"]."'</script>";
// 		  //	 die();
// 		  //}else{
// 		  //	echo $response['msg']; die('errro');
// 		  //}

    // 		  //UITELGATEWAY

    // 		  $curl = curl_init();

    // curl_setopt_array($curl, [
//   CURLOPT_URL => "https://upipg.gtelararia.com/order/create",
//   CURLOPT_RETURNTRANSFER => true,
//   CURLOPT_ENCODING => "",
//   CURLOPT_MAXREDIRS => 10,
//   CURLOPT_TIMEOUT => 30,
//   CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
//   CURLOPT_CUSTOMREQUEST => "POST",
//   CURLOPT_POSTFIELDS => "{\n  \"loginid\": \"8957287400\",\n  \"apikey\": \"4qt8cakywh\",\n  \"orderid\": \"".$order_id."\",\n  \"amt\": \"".$request->orderAmount."\",\n  \"trxnote\": \"".Auth::user()->username."\",\n  \"custmobile\": \"8524785698\",\n  \"redirecturl\": \"https://akadda.com\",\n  \"option1\": \"your option 1\",\n  \"option2\": \"your option 2\"\n}",
//   CURLOPT_HTTPHEADER => [
//     "Accept: */*",
//     "Content-Type: application/json"
//   ],
// ]);

    // $response = curl_exec($curl);
// $err = curl_error($curl);

    // curl_close($curl);

    // if ($err) {
//   echo "cURL Error #:" . $err;
// } else {
//   $data = json_decode($response);
//   if($data->status == "success"){
//     return redirect($data->gotourl);
//   }else{
//     return $data;
//   }
// }
//         // }
//     }
    public function createOrdernew(Request $request)
    {
        // Retrieve the deposit status setting directly
        $DepositStatusS = Setting::where('key', 'DepositStatusS')->value('value');

        if ($DepositStatusS == "no") {
            return redirect('/wallet')->with('error', 'Deposit currently paused due to some Bank issue, Please try after some time!');
        }

        $request->validate([
            'orderAmount' => 'required|numeric|gt:0|between:1,20000',
        ]);
        $amount = $request->orderAmount;
        $GatewayChoice_setting = Setting::find(7);
        $ChoicedGateway = $GatewayChoice_setting->field_value;
        if ($ChoicedGateway == "mpay") {
            // UPIGATEWAY
            $gateway = 'AKDA_PhonePe';
            $user_id = Auth::user()->id;
            $order_id = $gateway . '-' . time() . '-' . $user_id;
            //$key 		=	'08b3ae69-431b-4da8-b05e-a5669083d839';
            $order = PaymentOrder::create([
                'user_id' => $user_id,
                'order_id' => $order_id,
                'amount' => $request->orderAmount,
                'gateway' => $gateway,
                'ip' => $request->ip()
            ]);
            //Mpay_recharge_callback
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://mothersolution.in/api/pg/phonepe/initiate',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => '{
                "token": "$2y$10$5IaoAASmcK0JRWmp4obfSOvaHrFSFmvKU7WzMbqeTPFCVJN7TT7Ty",
                "userid": "MP15751",
                "amount": "' . $request->orderAmount . '",
                "mobile": "' . Auth::user()->mobile . '",
                "orderid": "' . $order_id . '",
                "callback_url": "https://akadda.com"
            }',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    'X-API-Key: $2y$10$5IaoAASmcK0JRWmp4obfSOvaHrFSFmvKU7WzMbqeTPFCVJN7TT7Ty'
                ),
            )
            );

            $respons = curl_exec($curl);

            curl_close($curl);
            $response = json_decode($respons);
            if ($response->status == true) {
                echo "<script>window.location.href='" . $response->url . "'</script>";
                die();
            } else {
                echo $response->message;
                die('errro');
            }
        } elseif ($ChoicedGateway == "upi") {
            // UPIGATEWAY
            $gateway = 'UPI-Gateway';
            $user_id = Auth::user()->id;
            $order_id = $gateway . '-' . time() . '-' . $user_id;
            //$key 		=	'08b3ae69-431b-4da8-b05e-a5669083d839';
            $order = PaymentOrder::create([
                'user_id' => $user_id,
                'order_id' => $order_id,
                'amount' => $request->orderAmount,
                'gateway' => $gateway,
                'ip' => $request->ip()
            ]);

            $postdata = [
                "loginid" => "6375030393",
                "apikey" => "hdcupxeg2m",
                "orderid" => $order_id,
                "amt" => $request->orderAmount,
                "trxnote" => Auth::user()->username,
                "custmobile" => "8524785698",
                "redirecturl" => "https://game.bottomfunnel.net/",
                "mcallback_url" => "https://game.bottomfunnel.net/new-upi-gateway-response"
                // "redirecturl" => "http://192.168.29.197:8080/",
                // "mcallback_url" => "http://192.168.29.197:8080/new-upi-gateway-response"
                // "redirecturl" => "http://127.0.0.1:8000",
                // "mcallback_url" => "http://127.0.0.1:8000/new-upi-gateway-response"
            ];

            //UITELGATEWAY
            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL => "https://upipg.gtelararia.com/order/create",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => json_encode($postdata),
                CURLOPT_HTTPHEADER => [
                    "Accept: */*",
                    "Content-Type: application/json"
                ],
            ]);

            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);

            \Log::info("API Response: " . $response);

            if ($err) {
                echo "cURL Error #:" . $err;
            } else {
                $data = json_decode($response);
                if ($data->status == "success") {
                    return redirect($data->gotourl);
                } else {
                    return $data;
                }
            }
        } elseif ($ChoicedGateway == "phonepeupi") {
            // UPIGATEWAY
            $gateway = 'UPI-Gateway';
            $user_id = Auth::user()->id;
            $order_id = $gateway . '-' . time() . '-' . $user_id;
            $key = '8e4fb829-a510-4353-92ef-4f671e02edad';
            $order = PaymentOrder::create([
                'user_id' => $user_id,
                'order_id' => $order_id,
                'amount' => $request->orderAmount,
                'gateway' => $gateway,
                'ip' => $request->ip()
            ]);
            $content = json_encode(
                array(
                    "key" => $key,
                    "client_txn_id" => $order_id, // order id or your own transaction id
                    "amount" => $request->orderAmount,
                    "p_info" => "Product Name",
                    "customer_name" => Auth::user()->username, // customer name
                    "customer_email" => "alshuindia@gmail.com", // customer email
                    "customer_mobile" => Auth::user()->mobile, // customer mobile number
                    "redirect_url" => url('/'), // redirect url after payment, with ?client_txn_id=&txn_id=
                    "udf1" => "user defined field 1", // udf1, udf2 and udf3 are used to save other order related data, like customer id etc.
                    "udf2" => "user defined field 2",
                    "udf3" => "user defined field 3",
                )
            );
            $url = "https://merchant.upigateway.com/api/create_order";
            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt(
                $curl,
                CURLOPT_HTTPHEADER,
                array("Content-type: application/json")
            );
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $content);
            $json_response = curl_exec($curl);
            $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if ($status != 200) {
                die("Error: call to URL $url failed with status $status, response $json_response, curl_error " . curl_error($curl) . ", curl_errno " . curl_errno($curl));
            }
            curl_close($curl);
            $response = json_decode($json_response, true);
            if ($response["status"] == true) {
                echo "<script>window.location.href='" . $response["data"]["payment_url"] . "'</script>";
                die();
            } else {
                echo $response['msg'];
                die('errro');
            }
        } else {
            return "Something wents wrong!!";
        }
    }

    // using this for the upipayment status check callback
    public function upiStatusCheck() {
        $orders = PaymentOrder::where('status', 0)->get();
        foreach ($orders as $key => $value) {
            $payOrder = PaymentOrder::find($value->id);
            if (!empty($payOrder)) {
                \Log::info("Processing order ID: " . $payOrder->id);

                $client = new Client();
                $res = $client->request('GET', 'https://upipg.gtelararia.com/order/statuscheck.php?loginid=6375030393&apikey=hdcupxeg2m&request_id=' . $payOrder->order_id);

                if ($res->getStatusCode() == 200) {
                    $response_data = $res->getBody()->getContents();
                    $response = json_decode($response_data, true);
                    \Log::info("API Response: " . $response_data);

                    // Set the $user_id variable early
                    $user_id = $payOrder->user_id;
                    \Log::info("User ID: " . $user_id);

                    if ($response['status'] == 'success') {
                        $user_data = User::where('id', $user_id)->first();
                        \Log::info("User Wallet Before: " . $user_data->wallet);

                        $wallet = $user_data->wallet;
                        $txn = Transaction::create([
                            'user_id' => $user_id,
                            'source_id' => $payOrder->order_id,
                            'amount' => $payOrder->amount,
                            'a_amount' => 0,
                            'status' => 'Wallet',
                            'remark' => 'Upigateway wallet recharge',
                            'ip' => "127.0.0.1",
                            'closing_balance' => $wallet + $payOrder->amount,
                        ]);

                        $payOrder->status = 1;
                        \Log::info("Transaction Created: " . $txn->id);
                    } elseif ($response['status'] == 'fail') {
                        $payOrder->status = 2;
                        \Log::info("Payment failed for order ID: " . $payOrder->id);
                    }

                    $payOrder->save();
                    \Log::info("Order status updated: " . $payOrder->status);

                    // Only update wallet balance if the payment was successful
                    if ($payOrder->status == 1) {
                        User::where('id', $user_id)->increment('wallet', $payOrder->amount);
                        \Log::info("User Wallet After: " . ($wallet + $payOrder->amount));
                    }
                }
            }
        }
    }

    public function upitel_recharge_status(Request $request)
    {
        Log::info(json_encode($request->all()));
        //  $initialdaya = json_encode($request->all());
        $json_string = file_get_contents('php://input');
        $initialdaya = json_decode($json_string, true);
        Log::info(json_encode($initialdaya));
        // return $initialdaya;
        // PaymentOrder::where('id',1)->update(['amount'=>$json_string]);
        if (!isset($initialdaya['status']) || $initialdaya['status'] != "success") {
            Log::info("PaymentController:721 => Status is Not Success");
            return "Status is Not Success";
        }
        $dec = openssl_decrypt($initialdaya['data'], 'AES-128-ECB', "aGRjdXB4ZWcybQ==");
        $resp = json_decode($dec, true);
        // return $dec;
        if (!$resp) {
            Log::info("PaymentController:728 => No data in Encode data");
            return "No data in Encode data";
        }
        // return $resp;
        $cust_mobile = $resp['cust_mobile'];
        $amt = $resp['amt'];
        $utr = $resp['utr'];
        $trxnote = $resp['trxnote'];
        $order_date = $resp['order_date'];
        $orderid = $resp['orderid'];

        $pCheck = PaymentOrder::where('order_id', $orderid)->first();
        // 		return $pCheck;
// 		$orderData	=	$this->checkOrderStatus($orderid,$pCheck->created_at);
        //echo "<pre>";print_r($pCheck);die;
        $user_id = $pCheck->user_id;
        //$amount		=	$request['orderAmount'];
        //if($pCheck->status == 0 && $amount == $pCheck->amount){ //die('kk');
        $user_data = User::where('id', $user_id)->first();
        $wallet = $user_data->wallet;

        // 		if($pCheck->status == 0 && $pCheck->amount == $orderData['amount'] && $orderData['status'] == 'success'){ //die('kk');
        $txn = Transaction::create([
            'user_id' => $user_id,
            'source_id' => $orderid,
            'amount' => $pCheck->amount,
            'a_amount' => 0,
            'status' => 'Wallet',
            'remark' => 'Upigateway wallet recharge',
            'ip' => $request->ip(),
            'closing_balance' => $wallet + $pCheck->amount,

        ]);

        if ($txn) {
            $pCheck->status = 1;
            $pCheck->save();

            User::where('id', $user_id)->increment('wallet', $pCheck->amount);
            return "Successfull";
            // return redirect('challenges')->with('payment_status', "Wallet rechaged successfully!");
        }
        // 		}

        // if($request['txStatus'] ==	'FAILED'){
        // 	return redirect('dashboard')->with('payment_status', $request['txMsg']);
        // }

        die("Invalid request. <a href=" . route('challenges') . ">Click here to go home</a>");
    }
    public function upigateway_recharge_callback(Request $request)
    {
        // $l = new Log;
        // $l->value = json_encode($request->all());
        // $l->save();
        // return $request->all();
        $trnstatus = $request->status;
        $trn = $request->client_txn_id;
        if ($trn && $trnstatus == "success") {
            $pCheck = PaymentOrder::where('order_id', $trn)->where('status', '!=', 1)->first();
            $exist = Transaction::where('source_id', $trn)->count();
            if ($exist > 0) {
                return response()->json(array("status" => 0, "message" => "Dublicate sessions!"));
            }
            if ($pCheck) {
                $user_id = $pCheck->user_id;
                $user_data = User::where('id', $user_id)->first();
                $wallet = $user_data->wallet;
                $txn = Transaction::create([
                    'user_id' => $user_id,
                    'source_id' => $trn,
                    'amount' => $pCheck->amount,
                    'a_amount' => 0,
                    'status' => 'Wallet',
                    'remark' => 'Phonepe wallet recharge',
                    'ip' => $request->ip(),
                    'closing_balance' => $wallet + $pCheck->amount,
                ]);
                if ($txn) {
                    $pCheck->status = 1;
                    $pCheck->save();

                    User::where('id', $user_id)->increment('wallet', $pCheck->amount);
                    return response()->json(array("status" => 1));
                    // return redirect('challenges')->with('payment_status', "Wallet rechaged successfully!");
                }
            }
        } else {
            return response()->json(array("status" => 0, "message" => $request->code));
        }
    }
    public function cashfree_recharge_callback(Request $request)
    {
        $event = $request->type;
        // return $request->all();
        $trnstatus = $request->data['payment']['payment_status'];
        $trn = $request->data['order']['order_id'];
        $trn_razorpay = $request->data['payment']['bank_reference'];
        $amount_razorpay = $request->data['payment']['payment_amount'];
        // $l = new Log;
        // $l->value = json_encode($request->all());
        // $l->save();

        if ($event == "PAYMENT_SUCCESS_WEBHOOK" && $trn && $trnstatus == "SUCCESS") {
            $pCheck = PaymentOrder::where('order_id', $trn)->where('status', '!=', 1)->first();
            $exist = Transaction::where('source_id', $trn)->count();
            if ($exist > 0) {
                return response()->json(array("status" => 0, "message" => "Dublicate sessions!"));
            }
            if ($pCheck) {
                $user_id = $pCheck->user_id;
                $user_data = User::where('id', $user_id)->first();
                $wallet = $user_data->wallet;
                $txn = Transaction::create([
                    'user_id' => $user_id,
                    'source_id' => $trn_razorpay,
                    'amount' => $amount_razorpay,
                    'a_amount' => 0,
                    'status' => 'Wallet',
                    'remark' => 'Cashfree wallet recharge',
                    'ip' => $request->ip(),
                    'closing_balance' => $wallet + $amount_razorpay,
                ]);
                if ($txn) {
                    $pCheck->amount = $amount_razorpay;
                    $pCheck->order_id = $trn_razorpay;
                    $pCheck->status = 1;
                    $pCheck->save();

                    User::where('id', $user_id)->increment('wallet', $amount_razorpay);
                    return response()->json(array("status" => 1));
                }
            }
        } else {
            return response()->json(array("status" => 0, "message" => $request->code));
        }
    }
    public function Mpay_recharge_callback(Request $request)
    {
        // $l = new Log;
        // $l->value = json_encode($request->all());
        // $l->save();
        // return $request->all();
        $trnstatus = $request->status;
        $trn = $request->client_txn_id;
        if ($trn && $trnstatus == "success") {
            $pCheck = PaymentOrder::where('order_id', $trn)->where('status', '!=', 1)->first();
            $exist = Transaction::where('source_id', $trn)->count();
            if ($exist > 0) {
                return response()->json(array("status" => 0, "message" => "Dublicate sessions!"));
            }
            if ($pCheck) {
                $user_id = $pCheck->user_id;
                $user_data = User::where('id', $user_id)->first();
                $wallet = $user_data->wallet;
                $txn = Transaction::create([
                    'user_id' => $user_id,
                    'source_id' => $trn,
                    'amount' => $pCheck->amount,
                    'a_amount' => 0,
                    'status' => 'Wallet',
                    'remark' => 'Phonepe wallet recharge',
                    'ip' => $request->ip(),
                    'closing_balance' => $wallet + $pCheck->amount,
                ]);
                if ($txn) {
                    $pCheck->status = 1;
                    $pCheck->save();

                    User::where('id', $user_id)->increment('wallet', $pCheck->amount);
                    return response()->json(array("status" => 1));
                    // return redirect('challenges')->with('payment_status', "Wallet rechaged successfully!");
                }
            }
        } else {
            return response()->json(array("status" => 0, "message" => $request->status));
        }
    }


    public function recharge_status(Request $request)
    {
        $l = new Log;
        $l->value = json_encode($request->all());
        $l->save();
        $trn = $request->data['OrderKeyId'];
        $trnstatus = $request->data['OrderPaymentStatusText'];
        if ($trn && $trnstatus == "Paid") {
            $pCheck = PaymentOrder::where('order_id', $trn)->where('status', '!=', 1)->first();
            $exist = Transaction::where('source_id', $trn)->count();
            if ($exist > 0) {
                return redirect('challenges')->with('payment_status', "Dublicate sessions!");
            }
            if ($pCheck) {
                $user_id = $pCheck->user_id;
                $user_data = User::where('id', $user_id)->first();
                $wallet = $user_data->wallet;
                $txn = Transaction::create([
                    'user_id' => $user_id,
                    'source_id' => $trn,
                    'amount' => $pCheck->amount,
                    'a_amount' => 0,
                    'status' => 'Wallet',
                    'remark' => 'Phonepe wallet recharge',
                    'ip' => $request->ip(),
                    'closing_balance' => $wallet + $pCheck->amount,
                ]);
                if ($txn) {
                    $pCheck->status = 1;
                    $pCheck->save();

                    User::where('id', $user_id)->increment('wallet', $pCheck->amount);

                    return redirect('challenges')->with('payment_status', "Wallet rechaged successfully!");
                }
            }
        } else {
            return redirect('challenges')->with('payment_status', $request->code);
        }
    }
    public function upiGatewayRes(Request $request)
    {
        $pCheck = PaymentOrder::where('order_id', $request->client_txn_id)->first();
        $orderData = $this->checkOrderStatus($request->client_txn_id, $pCheck->created_at);
        //echo "<pre>";print_r($pCheck);die;
        $user_id = $pCheck->user_id;
        //$amount		=	$request['orderAmount'];
        //if($pCheck->status == 0 && $amount == $pCheck->amount){ //die('kk');
        $user_data = User::where('id', $user_id)->first();
        $wallet = $user_data->wallet;

        if ($pCheck->status == 0 && $pCheck->amount == $orderData['amount'] && $orderData['status'] == 'success') { //die('kk');
            $txn = Transaction::create([
                'user_id' => $user_id,
                'source_id' => $request->client_txn_id,
                'amount' => $pCheck->amount,
                'a_amount' => 0,
                'status' => 'Wallet',
                'remark' => 'Upigateway wallet recharge',
                'ip' => $request->ip(),
                'closing_balance' => $wallet + $pCheck->amount,

            ]);

            if ($txn) {
                $pCheck->status = 1;
                $pCheck->save();

                User::where('id', $user_id)->increment('wallet', $pCheck->amount);

                return redirect('challenges')->with('payment_status', "Wallet rechaged successfully!");
            }
        }

        // if($request['txStatus'] ==	'FAILED'){
        // 	return redirect('dashboard')->with('payment_status', $request['txMsg']);
        // }

        die("Invalid request. <a href=" . route('challenges') . ">Click here to go home</a>");
    }

    public function upiGatewayResPost(Request $request)
    {
        $pCheck = PaymentOrder::where('order_id', $request->client_txn_id)->first();
        $orderData = $this->checkOrderStatus($request->client_txn_id, $pCheck->created_at);
        //echo "<pre>";print_r($pCheck);die;
        $user_id = $pCheck->user_id;
        //$amount		=	$request['orderAmount'];
        //if($pCheck->status == 0 && $amount == $pCheck->amount){ //die('kk');
        $user_data = User::where('id', $user_id)->first();
        $wallet = $user_data->wallet;

        if ($pCheck->status == 0 && $pCheck->amount == $orderData['amount'] && $orderData['status'] == 'success') { //die('kk');
            $txn = Transaction::create([
                'user_id' => $user_id,
                'source_id' => $request->client_txn_id,
                'amount' => $pCheck->amount,
                'a_amount' => 0,
                'status' => 'Wallet',
                'remark' => 'Cashfree wallet recharge',
                'ip' => $request->ip(),
                'closing_balance' => $wallet + $pCheck->amount,

            ]);

            if ($txn) {
                $pCheck->status = 1;
                $pCheck->save();

                User::where('id', $user_id)->increment('wallet', $pCheck->amount);

                return redirect('challenges')->with('payment_status', "Wallet rechaged successfully!");
            }
        }

        // if($request['txStatus'] ==	'FAILED'){
        // 	return redirect('dashboard')->with('payment_status', $request['txMsg']);
        // }

        die("Invalid request. <a href=" . route('challenges') . ">Click here to go home</a>");
    }

    private function checkOrderStatus($order_id, $date)
    {
        $client = new Client();
        $key = '08b3ae69-431b-4da8-b05e-a5669083d839';
        $res = $client->request('POST', 'https://merchant.upigateway.com/api/check_order_status', [
            'form_params' => [
                'key' => $key,
                'client_txn_id' => $order_id,
                'txn_date' => date('d-m-Y', strtotime($date)),
            ]
        ]);

        if ($res->getStatusCode() == 200) { // 200 OK
            $response_data = $res->getBody()->getContents();
        }
        $response = json_decode($response_data, true);

        return $response['data'];
        //echo "<pre>";print_r($response['data']);die;
    }

    protected function orderCashfree($orderAmount, $order_id)
    {
        $order = new Order();
        $od["orderId"] = $order_id;
        $od["orderAmount"] = $orderAmount;
        $od["orderNote"] = "Recharge";
        $od["customerPhone"] = Auth::user()->mobile;
        $od["customerName"] = Auth::user()->name;
        $od["customerEmail"] = 'bygame47@gmail.com';
        $od["returnUrl"] = route('payment-gateway-cashfree-res');
        $od["notifyUrl"] = route('payment-gateway-cashfree-res');
        //$od["notifyUrl"]      = "http://127.0.0.1:8000/order/success";

        $order->create($od);
        $link = $order->getLink($od['orderId']);//echo $link->paymentLink;die;
        return redirect()->to($link->paymentLink)->send();
    }

}
