<?php

namespace App\Http\Controllers;

use App\Http\Services\messaging;
use App\Models\Beneficiary;
use App\Models\User;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Twilio\Rest\Client;

class AuthController extends Controller
{
    public function signUp(Request $request)
    {
        try {
            //code...


            $data = $request->validate(['email' => 'required', 'name' => 'required', 'password' => 'required', 'phone' => 'required|string']);
            if ($request['companyName']) {
                $user = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'password' => bcrypt($data['password']),
                    'balance' => 0,
                    'company_name' => $request['companyName'],
                    'company_location' => $request['companyLocation'],
                    'data_restriction_pin' => $request['data_restriction_pin']
                ]);
            } else {
                $user = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'password' => bcrypt($data['password']),
                    'balance' => 0
                ]);
                $bene = Beneficiary::where('phone', $user->phone)->first();
                if ($bene) {
                    $user->balance = $bene->balance;

                    $user->save();
                   
                }
            }
            // try {
                
            //     $textMessage = "Hi $user->name, you have successfuly registered with COMESO. You are Welcome.";
            //     messaging::sendSms($user->phone, $textMessage);
            // } catch (\Throwable $th) {
            //     $user->delete();

            //     return response()->json(['error' => 'Phone number is not valid. Register with a valid phone number']);
            // }

            $token =  $user->createToken('main')->plainTextToken;

            return response()->json(['token' => $token, 'user' => $user], 200);
        } catch (\Exception $e) {
            \Log::info($e);
            return response()->json(['error'=>$e->getMessage()]);
            // if ($e->getCode() === '23000') {
            //     return response()->json(['message' => 'Email already exists, please login instead.'], 422);
            // }
        }
    }


    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required',
            'password' => 'required'
        ]);

        if (!Auth::attempt($credentials)) {
            return response([
                'message' => 'Provided email address or password is incorrect'
            ], 422);
        }

        $user = Auth::user();
        $user->last_visited = now();
        $user->save();

        $token = $user->createToken('main')->plainTextToken;


        return response(
            [
                'status' => true,
                'token' => $token,
                'user' => $user,
                'isAdmin' => $user->isAdmin,

            ]
        );
    }

    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)->first();

        // Generate a 4-digit OTP
        $otp = random_int(1000, 9999);

        // Save OTP and its expiration time
        $user->update([
            'otp_code' => $otp,
            'otp_expires_at' => Carbon::now()->addMinutes(10),
        ]);

        // Send OTP via email
        Mail::to($user->email)->send(new \App\Mail\SendOtpMail($otp));
       
        $smsKey=getenv("SMS_KEY");
        try {
        $sendMessage = Http::get("https://sms.arkesel.com/sms/api?action=send-sms&api_key=$smsKey&to=$user->phone&from=COMESO&sms=Your One Time Password code is: $otp");
        } catch (\Throwable $th) {
            \Log::info($th);
            return response()->json(['message' => 'OTP sent to your email.']);
            
        }

        return response()->json(['message' => 'OTP sent to your email.']);
    }

    public function resendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)->first();

        // Generate a 4-digit OTP
        $otp = random_int(1000, 9999);

        // Save OTP and its expiration time
        $user->update([
            'otp_code' => $otp,
            'otp_expires_at' => Carbon::now()->addMinutes(10),
        ]);

        // Send OTP via email
        Mail::to($user->email)->send(new \App\Mail\SendOtpMail($otp));

        return response()->json(['message' => 'OTP sent to your email.']);
    }
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp_code' => 'required|digits:4',
        ]);

        $user = User::where('email', $request->email)->first();

        // Check OTP validity

        if ($user->otp_code == intval($request->otp_code)) {
            // OTP is valid

            $user->update(['otp_code' => null, 'otp_expires_at' => null, 'email_verified_at' => now()]);

            // Generate token for authentication
            $token = $user->createToken('authToken')->plainTextToken;

            return response()->json(['message' => 'OTP verified.', 'token' => $token]);
        } else {
            return response()->json(['error' => 'Invalid or expired OTP.'], 422);
        }
    }
}
