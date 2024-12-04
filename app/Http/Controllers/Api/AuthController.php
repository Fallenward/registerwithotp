<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\OtpMail;
use App\Models\User;
use Carbon\Carbon;
use Ghasedak\DataTransferObjects\Request\InputDTO;
use Ghasedak\DataTransferObjects\Request\ReceptorDTO;
use Ghasedaksms\GhasedaksmsLaravel\Message\GhasedaksmsVerifyLookUp;
use Ghasedaksms\GhasedaksmsLaravel\Notification\GhasedaksmsBaseNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth; // for JWT token

class AuthController extends Controller
{
    public function generate($length = 6) {
        $otp = '';
        for ($i = 0; $i < $length; $i++) {
            $otp .= rand(0, 9);
        }
        return $otp;
    }
    public function chekUser(Request $request){
        $user=User::where('phone', $request->phone)->first;

        if(!$user){
            $newuser = new User;
            $newuser->phone = $request->phone;
            $validateCode= $this->generate();
            $newuser->otp= $validateCode;
            $newuser->save();
            try {
                $response = Http::withHeaders([
                    'ApiKey' => env("GHASEDAKAPI_KEY"),
                ])->post('https://gateway.ghasedak.me/rest/api/v1/WebService/SendOtpSMS', [
                    'sendDate' => now()->toIso8601String(),  // Example send date in ISO 8601 format
                    'receptors' => [
                        [
                            'mobile' => $request->phone,           // Mobile number from the request
                            'clientReferenceId' => (string) rand(1, 4), // Random client reference ID as a string
                        ],
                    ],
                    'templateName' => 'chartix',                    // Template name
                    'inputs' => [
                        [
                            'param' => 'code',                 // Parameter name
                            'value' => $validateCode,                // Parameter value
                        ],
                    ],
                    'udh' => true,                               // UDH flag
                ]);
            } catch (\Throwable $th) {
            }
            return response()->json(["data" => ["success" => false]], 200);
        }else {
            if ($user->otp == "ok") {
                return response()->json(["data" => ["success" => true]], 200);
            } else {
                $validationCode = $this->generate();
                $user->otp = $validationCode;
                $user->save();
                try {
                    $response = Http::withHeaders([
                        'ApiKey' => env("GHASEDAKAPI_KEY"),
                    ])->post('https://gateway.ghasedak.me/rest/api/v1/WebService/SendOtpSMS', [
                        'sendDate' => now()->toIso8601String(),  // Example send date in ISO 8601 format
                        'receptors' => [
                            [
                                'mobile' => $request->phone,           // Mobile number from the request
                                'clientReferenceId' => (string) rand(1, 4), // Random client reference ID as a string
                            ],
                        ],
                        'templateName' => 'chartix',                    // Template name
                        'inputs' => [
                            [
                                'param' => 'code',                 // Parameter name
                                'value' => $validationCode,                // Parameter value
                            ],
                        ],
                        'udh' => true,                               // UDH flag
                    ]);
                    return response()->json(["data" => ["success" => true]], 200);
                } catch (\Throwable $th) {
                }
            }
        }


    }
    public function checkOpt(Request $request){
    $request->validate([
        'phone' => 'required|regex:/^[0-9]{10}$/',
        'otp' => 'required|numeric',
    ]);

        $user=User::where('phone', $request->phone)->first;

        if($user || $user->otp == $request->otp){
            $user->otp= null;
            $user->save();
            return response()->json([
                'status'=> true,
                'massage'=> 'user otp is correct'
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
        'name' => 'required|string:min:3'
    ]);


        $user=User::where('phone', $request->phone)->first();
        if(!Hash::check($request->password, $user->password)){
            return response()->json(['massage' => 'wrong password']);
        }else{
            return response()->json(['massage' => 'correct password']);
            }
    }
}
