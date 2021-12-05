<?php

namespace App\Http\Controllers;

use App\Models\Crypto;
use App\Models\Balance;
use App\Models\Currency;
use Illuminate\Http\Request;
use Auth;
use Illuminate\Support\Facades\Validator;
use DB;
use App\Notifications\FirebaseNotification;

class CryptoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Crypto  $crypto
     * @return \Illuminate\Http\Response
     */
    public function show(Crypto $crypto)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Crypto  $crypto
     * @return \Illuminate\Http\Response
     */
    public function edit(Crypto $crypto)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Crypto  $crypto
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Crypto $crypto)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Crypto  $crypto
     * @return \Illuminate\Http\Response
     */
    public function destroy(Crypto $crypto)
    {
        //
    }
    
    public function deposit(Request $request)
    {
        $data_validator = Validator::make($request->all(),
        [
            'amount'=>'required|numeric'
        ]);
        if ($data_validator->fails()) {
            return response()->json([
                'error' => $data_validator->errors()->first()
            ], 422);
        }
        $amount = $request->amount;
        if($amount<1)
            return response()->json([
                'error' => "Minimum amount to deposit is 1 USD."
            ], 422);
        $acc = Auth::user();
        $balance = Balance::where([["idUser","=",$acc->idUser],["currency","=","USD"]])->first();
        if($balance == null)
            return response()->json([
                'error' => "Open USD balance first."
            ], 422);
        if($balance->amount<$amount)
            return response()->json([
                'error' => "Insufficient funds."
            ], 422);
        $crypt = Crypto::where("idUser","=",$acc->idUser)->first();
        $crypto = $crypt;
        if($crypt==null)
        {
            if(!($crypto=Crypto::create(array("wallet"=>md5($acc->idUser.time()),"amountHeld"=>0,"active"=>1,"idUser"=>$acc->idUser))))
                return response()->json([
                    'error' => "Something went wrong while we are creating your wallet."
                ], 422); 
        }
        if(!$crypto->active)
            return response()->json([
                'error' => "Your wallet is not active."
            ], 422);
        try
        {
            DB::beginTransaction();
            $balance->amount -= $amount;
            $balance->save();
            $rate = $this->getExchangeToBTCReturned();
            $crypto->amountHeld += (float)((float)(1.0/$rate))*$amount;
            $crypto->save();
            DB::commit();
            $acc->notify(new FirebaseNotification("Crypto","You deposit ".$request->amount." USD to BTC",$acc->fireBaseToken,$acc->enabledNotification));
            return response()->json([
                'done' => "Deposit with succès"
            ], 200);
        }
        catch(Exception $e)
        {
            DB::rollBack();
            return response()->json([
                'error' => "Something went wrong"
            ], 422);
        }
    }
    
    public function withdraw(Request $request)
    {
        $data_validator = Validator::make($request->all(),
        [
            'amount'=>'required|numeric'
        ]);
        if ($data_validator->fails()) {
            return response()->json([
                'error' => $data_validator->errors()->first()
            ], 422);
        }
        $acc = Auth::user();
        $amount = $request->amount;
        $crypto = Crypto::where("idUser","=",$acc->idUser)->first();
        if($crypto == null)
            return response()->json([
                'error' => "No wallet."
            ], 422);
        if($crypto->amountHeld<$amount)
            return response()->json([
                'error' => "Insufficient funds."
            ], 422);
        if(!$crypto->active)
            return response()->json([
                'error' => "Your wallet is not active."
            ], 422);
        $balance = Balance::where([["idUser","=",$acc->idUser],["currency","=","USD"]])->first();
        try
        {
            DB::beginTransaction();
            $rate = $this->getExchangeToBTCReturned();
            $balance->amount += $rate*$amount;
            $balance->save();
            $crypto->amountHeld -= $amount;
            $crypto->save();
            DB::commit();
            $acc->notify(new FirebaseNotification("Crypto","You have withdrawn ".$request->amount." BTC to USD",$acc->fireBaseToken,$acc->enabledNotification));
            return response()->json([
                'done' => "Withdraw with succès"
            ], 200);
        }
        catch(Exception $e)
        {
            DB::rollBack();
            return response()->json([
                'error' => "Something went wrong"
            ], 422);
        }
    }
    
    public function getExchangeToBTC()
    {
        return response()->json($this->getExchangeToBTCReturned());
    }
    
    public function getExchangeToBTCReturned()
    {
        $endpoint = 'live';
        $access_key = '3ceb0b8456f6b9eff5602be2121044b9';
        $ch = curl_init('http://api.coinlayer.com/api/'.$endpoint.'?access_key='.$access_key.'');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $json = curl_exec($ch);
        curl_close($ch);
        $exchangeRates = json_decode($json, true);
        return $exchangeRates['rates']['BTC'];
    }
    
    public function getWallet()
    {
        $acc = Auth::user();
        $crypto = Crypto::where("idUser","=",$acc->idUser)->first();
        if($crypto == null)
            return response()->json([
                'error' => "No wallet."
            ], 404);
        return response()->json($crypto);
    }
    
    public function close()
    {
        $acc = Auth::user();
        $crypto = Crypto::where("idUser","=",$acc->idUser)->first();
        if($crypto == null)
            return response()->json([
                'error' => "No wallet."
            ], 422);
        if(!$crypto->active)
            return response()->json([
                'error' => "Already Closed."
            ], 422);
        $crypto->active = false;
        if(!$crypto->save())
            return response()->json([
                'error' => "Something went wrong."
            ], 422);
        return response()->json([
            'done' => "closed with success"
        ], 200);
    }
    
    public function open()
    {
        $acc = Auth::user();
        $crypto = Crypto::where("idUser","=",$acc->idUser)->first();
        if($crypto == null)
            return response()->json([
                'error' => "No wallet."
            ], 422);
        if($crypto->active)
            return response()->json([
                'error' => "Already Opened."
            ], 422);
        $crypto->active = true;
        if(!$crypto->save())
            return response()->json([
                'error' => "Something went wrong."
            ], 422);
        return response()->json([
            'done' => "Reopened with success"
        ], 200);
    }
}
