<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;

use App\Models\User;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;


class AuthController extends Controller
{
    public function generate($length = 6) {
        $otp = '';
        for ($i = 0; $i < $length; $i++) {
            $otp .= rand(0, 9);
        }
        return $otp;
    }

    public function checkUser(Request $request)
    {

        $validated = $request->validate([
            'phone' => 'required|regex:/^(\+98|0)?9\d{9}$/',
        ]);


        $user = User::where('phone', $request->phone)->first();


        if (!$user) {
            $user = new User;
            $user->phone = $request->phone;
            $otpCode = $this->generate();
            $user->otp = $otpCode;
            $user->save();
            error_log("New user created with phone: " . $request->phone);
        } elseif ($user->otp = 'ok') {

            error_log("User OTP updated for phone: " );
        }

        try {
            $response = Http::withHeaders([
                'ApiKey' => env('GHASEDAKAPI_KEY'),
            ])->post('https://gateway.ghasedak.me/rest/api/v1/WebService/SendOtpSMS', [
                'sendDate' => now()->toIso8601String(),
                'receptors' => [
                    [
                        'mobile' => $request->phone,
                        'clientReferenceId' => (string) rand(1, 10000), // Random reference ID
                    ],
                ],
                'templateName' => 'chartix',
                'inputs' => [
                    [
                        'param' => 'code',
                        'value' => $otpCode,
                    ],
                ],
                'udh' => true,
            ]);


            if ($response->failed()) {
                error_log("Failed to send OTP SMS: " . $response->body());
            }

        } catch (\Throwable $th) {
            error_log("Error sending OTP SMS: " . $th->getMessage());
        }

        return response()->json(['data' => ['success' => true]], 200);
    }

    public function checkopt(Request $request){
    $request->validate([
        'phone' => 'required|regex:/^[0-9]{10}$/',
        'otp' => 'required|numeric',
    ]);

        $user=User::where('phone', $request->phone)->first();

        if($user || $user->otp == $request->otp){
            $user->password= Hash::make($request->password);
            $user->name=$request->name;
            $user->otp= "ok";
            $user->save();
            return response()->json([
                'status'=> true,
                'massage'=> 'user otp is correct and info updated'
            ],200);

        }elseif($user || $user->otp !== $request->otp){
            return response()->json([
                'status'=> false,
                'massage'=> 'wrong otp,commit again'
            ],200);

        }else{

            return response()->json([
                'status'=> false,
                'massage'=> 'wrong phone number'
            ],200);

        }

    }
     public function setinfo(Request $request){
        $request->validate([
                'phone' =>'required|regex:/^[0-9]{10}$/',
            ]);

            $user=User::where('phone', $request->phone)->first();
                if (!$user){
                    return response()->json([
                        'massage' => 'wrong number or not registered'
                    ]);
                }else {
                    $user->password= Hash::make($request->password);
                    $user->name=$request->name;
                    $user->save();

                    return response()->json([
                        'massage' => $user->name,'info set succesfully'
                    ]);

                }


     }

    public function checkauth(Request $request){
        $request->validate([
        'phone' => 'required|regex:/^[0-9]{10}$/',
        'password'=> 'required|string:min:6',
    ]);


        $user=User::where('phone', $request->phone)->first();

        if(!$user){
            return response()->json(['massage' => 'correct password']);
        }

        if(!Hash::check($request->password, $user->password)){
            return response()->json(['massage' => 'wrong password']);
        }else{
            return response()->json(['massage' => 'correct password']);
            }
    }
}

