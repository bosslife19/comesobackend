<?php

namespace App\Http\Controllers;

use App\Http\Services\messaging;
use App\Models\User;
use Illuminate\Http\Request;

use function PHPUnit\Framework\isNull;
use Twilio\Rest\Client;

class BeneficiaryController extends Controller
{
    public function deleteBeneficiary(Request $request)
    {
        $user = $request->user();
        $beneficiary = $user->beneficiaries()->where('id', $request->id)->first();
        $beneficiary->delete();

        return response()->json(['status' => true], 200);
    }
    public function createBeneficiary(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required',
            'phone' => 'required'

        ]);

        $user = $request->user();

        $exists = User::where('phone', $validated['phone'])->first();

        if ($exists) {
            return response()->json(['error' => 'This user already exists in COMESO. Add his number as a beneficiary instead']);
        }

        // $beneficiary = User::where('name', $request->name);

        // if(is_null($beneficiary)){
        //     return response()->json(['status'=>false], 200);
        // }
        $haveAlready = $user->beneficiaries()->where('phone', $validated['phone'])->first();
        if ($haveAlready) {
            return response()->json(['error' => 'This user is already your beneficiary']);
        }
        $bene =  $user->beneficiaries()->create($validated);
        // try {
        //     messaging::sendSms($validated['phone'], "Hi $request->name, you have been successfully added as a beneficiary to $user->name on COMESO. $user->name can now send you funds for medical treatments! Download the COMESO app today on appstore https://apps.apple.com/app/comeso/id6740334829");
        // } catch (\Exception $e) {
            
        //     $bene->delete();
        //     return response()->json(['error' => 'Phone number does not exist',]);
        // }

        return response()->json(['status' => true], 200);
    }

    public function getBeneficiaries(Request $request)
    {
        $user = $request->user();
        $beneficiaries = $user->beneficiaries;

        return response()->json(['beneficiaries' => $beneficiaries], 200);
    }
}
