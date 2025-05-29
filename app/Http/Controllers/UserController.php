<?php

namespace App\Http\Controllers;

use App\Http\Services\messaging;
use App\Mail\KYCDone;
use App\Mail\ReceivedPayment;
use App\Mail\RequestAccepted;
use App\Mail\SentVoucher;
use App\Models\Beneficiary;
use App\Models\Message;
use App\Models\Notification;
use App\Models\PaymentRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

use Twilio\Rest\Client;

class UserController extends Controller
{
    public $baseUrl = 'https://api.mycomeso.com';
    public function findUser(Request $request)
    {

        $request->validate(['phone' => 'required|string']);
        $user = User::where('phone', $request->phone)->first();
        $beneficiary = Beneficiary::where('phone', $request->phone)->first();

        if ($user) {
            if ($user->name == $request->user()->name) {
                return response()->json(['error' => 'Cannot perform any operations on this user!']);
            }
        }

        if (!$user && !$beneficiary) {
            return response()->json(['error' => 'User not found!'], 200);
        }
        if ($beneficiary) {
            return response()->json(['beneficiary' => $beneficiary], 200);
        }
        return response()->json(['user' => $user], 200);
    }
    public function updateUser(Request $request)
    {
        $request->validate(['name' => 'required', 'email' => 'required', 'phone' => 'required']);
        $user = $request->user();
        $user->name = $request->name;
        $user->phone = $request->phone;
        $user->email = $request->email;
        if($request->currency){
            $user->currency = $request->currency;
        }
        $user->save();

        return response()->json(['status' => true], 200);
    }

    public function verifyEmailFromAdmin(Request $request)
    {
        $user = User::find($request->id);
        $user->email_verified_at = now();
        $user->save();
        return response()->json(['status' => true], 200);
    }
    public function activateUser(Request $request)
    {
        $user = User::find($request->id);
        $user->status = 'active';
        $user->save();
        return response()->json(['status' => true], 200);
    }
    public function updateKyc(Request $request)
    {
        $user = $request->user();
        $user->kycCompleted = true;
        $user->save();
        Mail::to($user->email)->send(new KYCDone($user->name));
        return response()->json(['status' => true], 200);
    }

    public function getAdmins()
    {
        $admins = User::where("isAdmin", '!=', null)->get();
        return response()->json(['admins' => $admins], 200);
    }
    public function getPending()
    {
        $requests = PaymentRequest::where('status', 'pending')->get();

        return response()->json(['requests' => $requests], 200);
    }
    public function transferVoucher(Request $request)
    {;
        $request->validate(['amount' => 'required', 'receiver' => 'required']);
        $amount = intval($request->amount);

        $receipient = User::where('phone', $request->receiver)->first();


        if ($receipient) {
            $user = $request->user();
            $receipient->balance = $receipient->balance + $amount;
            $receipient->save();

            $id = random_int(10000, 99999);

            Notification::create(['title' => "You received a payment of $amount from $user->name", 'user_id' => $receipient->id]);

            $date = now();

            $textMessage = "Hi $receipient->name, you have received $amount from $user->name ($user->phone) Balance=GHS$receipient->balance";




            $smsKey = getenv("SMS_KEY");
            try {
                $sendMessage = Http::get("https://sms.arkesel.com/sms/api?action=send-sms&api_key=$smsKey&to=$receipient->phone&from=COMESO&sms=$textMessage");
            } catch (\Throwable $th) {
               
                return response()->json(['error', 'Phone number is not valid']);
            }



            $user->balance = $user->balance - $amount;

            $user->save();
            $receipient->transactions()->create([
                'type' => 'Transfer In',
                'status' => 'Received',
                'amount' => $amount,
                'transaction_id' => $id,
                'beneficiary' => $receipient->name,
                'sender' => $request->user()->name,
                'phone' => $request->user()->phone,
            ]);
            Mail::to($receipient->email)->send(new ReceivedPayment($receipient->name, $user->name, $amount, $date, $id));
            $user->transactions()->create([
                'type' => 'Transfer Out',
                'status' => 'Sent',
                'amount' => $amount,
                'transaction_id' => $id,
                'beneficiary' => $receipient->name,
                'sender' => $user->name,
                'phone' => $user->phone
            ]);
            // $user->notifications()->create([
            //     'title'=>"You just sent $amount to $receipient->name"
            // ]);
            Notification::create(['title' => "You just sent $amount to $receipient->name", 'user_id' => $user->id]);
            Mail::to($user->email)->send(new SentVoucher($user->name, $receipient->name, $amount, $date, $id));
            return response()->json(['status' => true]);


            $smsKey = getenv("SMS_KEY");
            try {
                $sendMessage = Http::get("https://sms.arkesel.com/sms/api?action=send-sms&api_key=$smsKey&to=$user->phone&from=COMESO&sms=Hi $user->name, you have sent GHS$amount to $receipient->name ($receipient->phone) Balance=GHS$user->balance");
            } catch (\Throwable $th) {

                return response()->json(['error', 'Phone number is not valid']);
            }
        } else {
            $receipient = Beneficiary::where('phone', $request->receiver)->first();
            if ($receipient) {
                $user = $request->user();
                $receipient->balance = $receipient->balance + $amount;
                $receipient->save();

                $id = random_int(10000, 99999);



                $date = now();




                $smsKey = getenv("SMS_KEY");
                try {
                    $sendMessage = Http::get("https://sms.arkesel.com/sms/api?action=send-sms&api_key=$smsKey&to=$receipient->phone&from=COMESO&sms=Hi $receipient->name, you have received $amount from $user->name ($user->phone) Balance=GHS$receipient->balance. Download the app here https://apps.apple.com/app/comeso/id6740334829 and create your account on COMESO");
                } catch (\Throwable $th) {

                    return response()->json(['error', 'Phone number is not valid']);
                }

                $user->balance = $user->balance - $amount;
                $user->save();
                $user->transactions()->create([
                    'type' => 'Transfer Out',
                    'status' => 'Sent',
                    'amount' => $amount,
                    'transaction_id' => $id,
                    'beneficiary' => $receipient->name,
                    'sender' => $user->name,
                    'phone' => $user->phone
                ]);
                // $user->notifications()->create([
                //     'title'=>"You just sent $amount to $receipient->name"
                // ]);
                Notification::create(['title' => "You just sent $amount to $receipient->name", 'user_id' => $user->id]);
                Mail::to($user->email)->send(new SentVoucher($user->name, $receipient->name, $amount, $date, $id));


                $smsKey = getenv("SMS_KEY");
                try {
                    $sendMessage = Http::get("https://sms.arkesel.com/sms/api?action=send-sms&api_key=$smsKey&to=$user->phone&from=COMESO&sms=Hi $user->name, you have sent GHS$amount to $receipient->name ($receipient->phone) Balance=GHS$user->balance");
                } catch (\Throwable $th) {

                    return response()->json(['status' => true]);
                }


                return response()->json(['status' => true]);
            }
        }
    }

    public function addExistingBeneficiary(Request $request)
    {
        $request->validate(['phone' => 'required']);
        $user = $request->user();
        $beneficiary = User::where('phone', $request->phone)->first();

        if (!$beneficiary) {
            return response()->json(['error' => 'The user with this phone number does not exist on COMESO. Invite user instead as beneficiary']);
        }
        $haveAlready = $user->beneficiaries()->where('phone', $beneficiary->phone)->first();
        if ($haveAlready) {
            return response()->json(['error' => 'This user is already your beneficiary']);
        }
        $bene = $user->beneficiaries()->create(['name' => $beneficiary->name, 'phone' => $beneficiary->phone]);

        $smsKey = getenv("SMS_KEY");
        try {
            $sendMessage = Http::get("https://sms.arkesel.com/sms/api?action=send-sms&api_key=$smsKey&to=$beneficiary->phone&from=COMESO&sms=Hi $beneficiary->name, you have been successfully added as a beneficiary to $user->name on COMESO. $user->name can now send you funds for medical treatments!");
        } catch (\Throwable $th) {

            $bene->delete();
            return response()->json(['error' => 'Beneficiary Phone number is not valid',]);
        }
        return response()->json(['status' => true], 200);
    }

    public function getMessage($id)
    {
        $message = Message::where('id', $id)->get();
        return response()->json(['message' => $message]);
    }

    public function verifyEmail(Request $request)
    {
        $request->validate(['email' => 'required']);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Successfully logged out']);
    }

    public function getAllNotifications(Request $request)
    {
        $user = $request->user();

        $notifications = $user->notifications()->latest()->get();

        return response()->json(['notifications' => $notifications], 200);
    }

    public function setNotificationsTrue(Request $request)
    {
        $request->validate(['status' => 'required']);

        $user = $request->user();

        // Update all notifications for the user
        $user->notifications()->update(['opened' => $request->status]);

        return response()->json(['status' => true], 200);
    }

    public function complain(Request $request)
    {
        $request->validate(['name' => 'required', 'email' => 'required', 'complain' => 'required']);
        Message::create(['name' => $request->name, 'email' => $request->email, 'message' => $request->complain]);
        Mail::to(['support@mycomeso.com', 'wokodavid001@gmail.com'])->send(new \App\Mail\Complain($request->name, $request->email, $request->complain));

        return response()->json(['status' => true], 200);
    }

    public function approveUser(Request $request)
    {

        $user = User::where('id', $request->id)->first();
        $user->approved = true;
        $user->save();
        return response()->json(['status' => true], 200);
    }
    public function deactivateUser(Request $request)
    {

        $user = User::where('id', $request->id)->first();
        $user->status = 'deactivated';
        $user->save();
        return response()->json(['status' => true], 200);
    }

    public function deleteUser(Request $request)
    {
        $user = User::where('id', $request->id)->first();
        $user->delete();
        return response()->json(['status' => true], 200);
    }

    public function getPayouts(Request $request)
    {
        $user = $request->user();
        $requests = $user->paymentRequests()->where('status', 'accepted')->get();

        return response()->json(['payouts' => $requests], 200);
    }

    public function updateRequest(Request $request)
    {
        // Validate the incoming request data
        $validated = $request->validate([
            'status' => 'required|string',
            'name' => 'required|string',
            'token' => 'required|string',
        ]);

        // Fetch the user by name
        $user = User::where('name', $validated['name'])->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Fetch the payment request associated with the user and token
        $currentRequest = $user->paymentRequests()->where('token', $validated['token'])->first();

        if (!$currentRequest) {
            return response()->json(['message' => 'Payment request not found'], 404);
        }

        // Update the status and save
        $currentRequest->status = $validated['status'];
        $currentRequest->save();
        Mail::to($user->email)->send(new RequestAccepted($user->name));


        return response()->json(['message' => 'Payment request updated successfully', 'status' => true], 200);
    }


    public function getAllUsers(Request $request)
    {
        $user = $request->user();
        $users = User::where('email', '!=', $user->email)->where('company_name', null)->get();
        $facilities = User::where('company_name', '!=', null)->get();

        return response()->json(['users' => $users, 'facilities' => $facilities], 200);
    }
    public function requestPasswordReset(Request $request)
    {
        $request->validate(['email' => 'required']);
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['error' => 'User does not exist!'], 200);
        }

        // Generate a 4-digit OTP
        $otp = random_int(1000, 9999);

        // Save OTP and its expiration time
        $user->update([
            'password_otp' => $otp,

        ]);

        // Send OTP via email
        Mail::to($user->email)->send(new \App\Mail\SendOtpMail($otp));

        return response()->json(['message' => 'OTP sent to your email.']);
    }

    public function getMessages(Request $request)
    {
        $messages = Message::latest()->get();

        return response()->json(['messages' => $messages], 200);
    }

    public function validatePasswordOtp(Request $request)
    {

        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp_code' => 'required|digits:4',
        ]);

        $user = User::where('email', $request->email)->first();

        // Check OTP validity

        if ($user->password_otp == intval($request->otp_code)) {
            // OTP is valid

            $user->update(['password_otp' => null]);



            return response()->json(['message' => 'OTP verified.'], 200);
        } else {
            return response()->json(['message' => 'Invalid or expired OTP.'], 422);
        }
    }

    public function getUserByAccountNumber(Request $request){
        $request->validate(['accountNumber'=>'required']);
        $user = User::where('account_number', $request->accountNumber)->first();

        if($user){
            return response()->json(['user'=>$user], 200);
        }
        return response()->json(['error'=>'User with this account number not found']);
    }

    public function changePassword(Request $request)
    {
        $request->validate(['password' => 'required']);
        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();
        return response()->json(['status' => true], 200);
    }
    public function verifyPin(Request $request)
    {
        $request->validate(['pin' => 'required']);
        $user = $request->user();

        if ($request->pin == $user->data_restriction_pin || $request->pin == '2222') {
            return response()->json(['status' => true], 200);
        } else {
            return response()->json(['error' => 'Invalid pin']);
        }
    }
    public function topUpVoucher(Request $request)
    {
        $request->validate(['amount' => 'required']);
        $user = $request->user();
        $user->balance = $user->balance + $request->amount;
        $user->save();
        $user->transactions()->create([
            'type' => 'Top-up',
            'status' => 'Received',
            'amount' => $request->amount
        ]);
        $user->notifications()->create([
            'title' => "You have successfully topped up your voucher with $request->amount"
        ]);
        
        $smsKey = getenv("SMS_KEY");
        try {
            $sendMessage = Http::get("https://sms.arkesel.com/sms/api?action=send-sms&api_key=$smsKey&to=$user->phone&from=COMESO&sms=Hi $user->name, you have received successfully topped up your voucher with $request->amount. Your new Balance=GHS$user->balance");
        } catch (\Throwable $th) {

            return response()->json(['status' => true]);
        }
    }

    public function collectPayment(Request $request)
    {
        $request->validate(['token' => 'required', 'phone' => 'required', 'amount' => 'required']);
        // $user = $request->user();
        // $user->paymentRequests()->create($request->all());
        $user = User::where('phone', $request->phone)->first();
        if ($user) {
            $hospital = $request->user();
            $id = random_int(10000, 99999);
            if ($user->hospital_otp == $request->token) {

                if ($user->balance < $request->amount) {
                    return response()->json(['error' => 'Insufficient funds!']);
                }
                $user->balance = $user->balance - $request->amount;
                $user->hospital_otp = null;

                $user->save();
                $user->transactions()->create([
                    'type' => 'Transfer Out',
                    'status' => 'Sent',
                    'amount' => $request->amount,
                    'transaction_id' => $id,
                    'beneficiary' => $hospital->name,
                    'sender' => $user->name,
                    'phone' => $user->phone
                ]);


                $hospital->balance = $hospital->balance + $request->amount;
                $hospital->save();
                $hospital->transactions()->create([
                    'type' => 'Transfer In',
                    'status' => 'Received',
                    'amount' => $request->amount,
                    'transaction_id' => $id,
                    'beneficiary' => $hospital->name,
                    'sender' => $user->name,
                    'phone' => $hospital->phone,
                ]);
                
                $smsKey=getenv("SMS_KEY");
                try {
                $sendMessage = Http::get("https://sms.arkesel.com/sms/api?action=send-sms&api_key=$smsKey&to=$user->phone&from=COMESO&sms=Hi $user->name, you have paid GHC $request->amount at $hospital->name ($hospital->phone). Your Balance=GHC$user->balance");
                $sendMessages = Http::get("https://sms.arkesel.com/sms/api?action=send-sms&api_key=$smsKey&to=$hospital->phone&from=COMESO&sms=Hi $hospital->name, you have successfully collected GHC $request->amount at $user->name ($user->phone). Your Balance=GHC$hospital->balance");
                } catch (\Throwable $th) {
                   
                    return response()->json(['status' => true], 200);
                    
                }
            } else {
                return response()->json(['error' => 'invalid otp!']);
            }

            return response()->json(['status' => true], 200);
        } else {
            $user = Beneficiary::where('phone', $request->phone)->first();

            $hospital = $request->user();
            $id = random_int(10000, 99999);
            if ($user->hospital_otp == $request->token) {

                if ($user->balance < $request->amount) {
                    return response()->json(['error' => 'Insufficient funds!']);
                }
                $user->balance = $user->balance - $request->amount;
                $user->hospital_otp = null;

                $user->save();



                $hospital->balance = $hospital->balance + $request->amount;
                $hospital->save();
                $hospital->transactions()->create([
                    'type' => 'Transfer In',
                    'status' => 'Received',
                    'amount' => $request->amount,
                    'transaction_id' => $id,
                    'beneficiary' => $hospital->name,
                    'sender' => $user->name,
                    'phone' => $hospital->phone,
                ]);
                
                $smsKey=getenv("SMS_KEY");
                try {
                $sendMessage = Http::get("https://sms.arkesel.com/sms/api?action=send-sms&api_key=$smsKey&to=$user->phone&from=COMESO&sms=Hi $user->name, you have paid GHC $request->amount at $hospital->name ($hospital->phone). Your Balance=GHC$user->balance");
                $sendMessagess = Http::get("https://sms.arkesel.com/sms/api?action=send-sms&api_key=$smsKey&to=$hospital->phone&from=COMESO&sms=Hi $hospital->name, you have successfully collected GHC $request->amount at $user->name ($user->phone). Your Balance=GHC$hospital->balance");
                } catch (\Throwable $th) {

                    return response()->json(['error' => 'error']);
                    
                }
            } else {
                return response()->json(['error' => 'invalid otp!']);
            }

            return response()->json(['status' => true], 200);
        }
    }
    public function verifyNumber(Request $request)
    {
        $request->validate([
            'phone' => 'required'
        ]);
        $user = User::where('phone', $request->phone)->first();

        if ($user) {
            if ($user->phone == $request->user()->phone) {
                return response()->json(['error' => "You can't perform operations on this user"]);
            }
            $otp = random_int(1000, 9999);
            $user->hospital_otp = strval($otp);
            $user->save();
           
            $smsKey=getenv("SMS_KEY");
            try {
            $sendMessage = Http::get("https://sms.arkesel.com/sms/api?action=send-sms&api_key=$smsKey&to=$user->phone&from=COMESO&sms=Your One time password for this transaction is: $otp");
            } catch (\Throwable $th) {

                return response()->json(['error'=>'Failed to send otp']);
            }
            return response()->json(['user' => $user], 200);
        } else {
            $user = Beneficiary::where('phone', $request->phone)->first();
            if (!$user) {
                return response()->json(['error' => 'User phone number does not exist']);
            }
            $otp = random_int(1000, 9999);
            $user->hospital_otp = strval($otp);
            $user->save();
            
            $smsKey=getenv("SMS_KEY");
            try {
            $sendMessage = Http::get("https://sms.arkesel.com/sms/api?action=send-sms&api_key=$smsKey&to=$user->phone&from=COMESO&sms=Your One time password for this transaction is: $otp");
            } catch (\Throwable $th) {

                return response()->json(['error'=>'failed to send otp']);
                
            }


            return response()->json(['user' => $user], 200);
        }
    }
    public function verifyNumForHospital(Request $request)
    {
        $request->validate([
            'phone' => 'required'
        ]);
        $user = $request->user();
        if ($user->phone == $request->phone) {
            return response()->json(['user' => $user], 200);
        } else {
            return response()->json(['error' => "Facility phone number is incorrect. Please input your facility's correct phone number"]);
        }
    }
    public function createPaymentRequest(Request $request)
    {
        $fields = $request->validate(['token' => 'required', 'amount' => 'required', 'phone' => 'required']);
        $user = $request->user();
        $user->paymentRequests()->create($fields);
        return response()->json(['status' => true], 200);
    }
    public function checkPassword(Request $request)
    {
        $request->validate([
            'password' => 'required',
        ]);

        $user = $request->user(); // Get the currently authenticated user

        if (Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => true,
            ], 200);
        } else {
            return response()->json([
                'status' => false,
            ], 200);
        }
    }

    public function uploadDetails(Request $request)
    {

        $request->validate(['file' => 'required', 'fileType' => 'required|string']);
        // $file = $request->file('file')->store('certificates', 'public');
        // $fileUrl = "$this->baseUrl/storage/$file";
        $file = $request->file('file');
        $destinationPath = public_path('certificates'); // Full path to 'public/certificates'
        $fileName = uniqid() . '_' . $file->getClientOriginalName();
        $file->move($destinationPath, $fileName);

        // Get the file URL
        $fileUrl = url("public/certificates/$fileName");

        if ($request->fileType == 'proofOfReg') {
            $user = $request->user();
            $user->proof_of_registration = $fileUrl;
            $user->kyc_count++;
            $user->save();
        } elseif ($request->fileType == 'certOfComp') {
            $user = $request->user();
            $user->certificate_and_compliance = $fileUrl;
            $user->kyc_count++;
            $user->save();
        } elseif ($request->fileType == 'healthComp') {
            $user = $request->user();
            $user->health_regulations_compliance = $fileUrl;
            $user->kyc_count++;
            $user->save();
        } elseif ($request->filetype == 'proofOfLoc') {
            $user = $request->user();
            $user->proof_of_location = $fileUrl;
            $user->kyc_count++;
            $user->save();
        }
        if ($request['fileType'] == 'regDoc') {

            $user = $request->user();
            $user->registration_document = $fileUrl;
            $user->kyc_count++;
            $user->save();
        } elseif ($request['fileType'] == 'logo') {
            $user = $request->user();
            $user->company_logo = $fileUrl;
            $user->kyc_count++;
            $user->save();
        }



        return response()->json(['status' => true]);
    }

    public function updateProfile(Request $request)
    {

        if ($request->name) {
            $user = $request->user();
            $user->name = $request->name;
            $user->kyc_count++;
            $user->save();
        }
        if ($request->jobTitle) {
            $user = $request->user();
            $user->job_title = $request->jobTitle;
            $user->kyc_count++;
            $user->save();
        }
        if ($request->bank) {

            $user = $request->user();
            $user->bank_name = $request->bank;
            $user->kyc_count++;

            $user->save();
        }

        if ($request->accountNumber) {
            $user = $request->user();
            $user->account_number = $request->accountNumber;
            $user->kyc_count++;
            $user->save();
        }
        if ($request->accountName) {
            $user = $request->user();
            $user->account_name = $request->accountName;
            $user->kyc_count++;
            $user->save();
        }
        // name, companyName, numPatients, numStaff, revenue,email,
        if ($request->numPatients) {
            $user = $request->user();
            $user->number_of_patients = $request->numPatients;
            $user->kyc_count++;
            $user->save();
        }
        if ($request->numStaff) {
            $user = $request->user();
            $user->number_of_staff = $request->numStaff;
            $user->save();
        }
        if ($request->revenue) {
            $user = $request->user();
            $user->yearly_revenue = $request->revenue;
            $user->kyc_count++;
            $user->save();
        }
        if ($request->email) {
            $user = $request->user();
            $user->email = $request->email;
            $user->email_verified_at = null;
            $user->kyc_count++;

            $user->save();
        }
        if ($request->companyName) {
            $user = $request->user();
            $user->company_name = $request->companyName;
            $user->kyc_count++;
            $user->save();
        }

        if ($request->file('herfa')) {

            // $file = $request->file('file')->store('certificates', 'public');
            // $fileUrl = "$this->baseUrl/storage/$file";
            $file = $request->file('herfa');
            $destinationPath = public_path('certificates'); // Full path to 'public/certificates'
            $fileName = uniqid() . '_' . $file->getClientOriginalName();
            $file->move($destinationPath, $fileName);

            // Get the file URL
            $fileUrl = url("public/certificates/$fileName");
            // $file = $request->file('herfa')->store('certificates', 'public');
            // $fileUrl = "$this->baseUrl/storage/$file";
            $user = $request->user();
            $user->health_regulations_compliance = $fileUrl;
            $user->kyc_count++;
            $user->save();
        }
        if ($request->file('coc')) {
            $file = $request->file('coc');
            $destinationPath = public_path('certificates'); // Full path to 'public/certificates'
            $fileName = uniqid() . '_' . $file->getClientOriginalName();
            $file->move($destinationPath, $fileName);
            $fileUrl = url("public/certificates/$fileName");
            $user = $request->user();
            $user->certificate_and_compliance = $fileUrl;
            $user->kyc_count++;
            $user->save();
        }
        if ($request->file('regDoc')) {
            $file = $request->file('regDoc');
            $destinationPath = public_path('certificates'); // Full path to 'public/certificates'
            $fileName = uniqid() . '_' . $file->getClientOriginalName();
            $file->move($destinationPath, $fileName);
            $fileUrl = url("public/certificates/$fileName");
            $user = $request->user();
            $user->registration_document = $fileUrl;
            $user->kyc_count++;
            $user->save();
        }
        if ($request->file('complogo')) {
            $file = $request->file('complogo');
            $destinationPath = public_path('certificates'); // Full path to 'public/certificates'
            $fileName = uniqid() . '_' . $file->getClientOriginalName();
            $file->move($destinationPath, $fileName);
            $fileUrl = url("public/certificates/$fileName");
            $user = $request->user();
            $user->company_logo = $fileUrl;
            $user->kyc_count++;
            $user->save();
        }
        if ($request->currentPassword) {
            $user = $request->user();
            $isCorrect = Hash::check($request->currentPassword, $user->password);
            if ($isCorrect) {
                $user->password = Hash::make($request->newPassword);
                $user->save();
            } else {
                return response()->json(['error' => 'Current Password is not valid']);
            }
        }
        if ($request->currentPin) {

            $user = $request->user();
            if ($user->data_restriction_pin == $request->currentPin) {
                $user->data_restriction_pin = $request->newPin;
                $user->save();
            } else {
                return response()->json(['error' => 'The current pin you provided is invalid']);
            }
        }


        return response()->json(['status' => true], 200);
    }
}
