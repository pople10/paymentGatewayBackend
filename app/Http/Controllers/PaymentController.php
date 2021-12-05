<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Currency;
use App\Models\Balance;
use App\Models\Tax;
use App\Models\User;
use App\Models\Status;
use Auth;
use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Notifications\FirebaseNotification;

class PaymentController extends Controller
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
        
    }
    
    public function checkReceiver(Request $request)
    {
        $data_validator = Validator::make($request->all(),
        [
            'username' => 'required|string'
        ]);
        if ($data_validator->fails()) {
            return response()->json([
                'error' => $data_validator->errors()->first()
            ], 422);
        }
        $rec = User::where("email",$request->username)->orWhere("username",$request->username)->orWhere("idUser",$request->username)->first();
        $acc = Auth::user();
        if($rec == null)
            return response()->json([
                'error' => "This receipient does not exist"
            ], 404);
        if($rec->idUser==$acc->idUser)
            return response()->json([
                'error' => "You cannot send to yourself."
            ], 422);
        return response()->json([
            'done' => $rec->firstname." ".$rec->lastname
        ], 200);
    }
    
    public function sendPayment(Request $request)
    {
        $data_validator = Validator::make($request->all(),
        [
            'email' => 'required|string',
            'currency' => 'required|string',
            'amount'=> 'required|numeric',
            'type' => 'required|numeric',
            'status' => 'required|numeric'
        ]);
        if ($data_validator->fails()) {
            return response()->json([
                'error' => $data_validator->errors()->first()
            ], 422);
        }
        $curr = Currency::find($request->currency);
        if($curr==null)
            return response()->json([
                'error' => "We don't have this currency yet."
            ], 404);
        $acc = Auth::user();
        if(!$acc->enabledSending)
            return response()->json([
                'error' => "Your account cannot send any payments, contact us for more help."
            ], 422);
        $balance = Balance::where([["idUser","=",$acc->idUser],["currency","=",$request->currency]])->first();
        if($balance==null)
            return response()->json([
                'error' => "This balance doesn't exist"
            ], 404);
        if($balance->amount<$request->amount)
            return response()->json([
                'error' => "You don't have enough money in ".$request->currency." balance"
            ], 422);
        $rec = User::where("email",$request->email)->orWhere("username",$request->email)->orWhere("idUser",$request->email)->first();
        if($rec == null)
            return response()->json([
                'error' => "This receipient does not exist"
            ], 404);
        if($rec->idUser==$acc->idUser)
            return response()->json([
                'error' => "You can't send to yourself."
            ], 422);
        $balanceTo = Balance::where([["idUser","=",$rec->idUser],["currency","=",$request->currency]])->first();
        if($balanceTo==null)
            return response()->json([
                'error' => "Sender doesn't have any balance with the currency of ".$request->currency
            ], 422);
        if(!$acc->enabled || $acc->deleted)
            return response()->json([
                'error' => "You are blocked or deleted."
            ], 422);
        if(!$rec->enabled || $rec->deleted || !$rec->enabledReceiving)
            return response()->json([
                'error' => "You can't send to this user"
            ], 422);
        $status = Status::find($request->status);
        if($status==null)
            return response()->json([
                'error' => "This status does not exist"
            ], 404);
        $tax = Tax::first();
        $percent = $tax->taxPercent;
        $amountShouldBeAdded = $request->amount*(1-(float)($percent/100));
        $payment;
        try
        {
            DB::beginTransaction();
            $array = array();
            $array = array_merge($request->all(),["from"=>$acc->idUser,"to"=>$rec->idUser,"status"=>1,"idPayment"=>md5($rec->idUser.time().$acc->id),"title"=>"Payment sent to ".$rec->firstname." ".$rec->lastname]);
            unset($array["email"]);
            $payment = Payment::create($array);
            if($status->eligible)
            {
                $balance->amount -= $amountShouldBeAdded;
                $balance->save();
                $balanceTo->amount += $amountShouldBeAdded;
                $balanceTo->save();
                $tax->balance += $request->amount*((float)($percent/100));             
                $tax->transactionNumber++;             
                $tax->save();
            }
            DB::commit();
            $acc->notify(new FirebaseNotification("Payment","You have sent a payment of ".$request->amount." ".$curr->label,$acc->fireBaseToken,$acc->enabledNotification));
            $rec->notify(new FirebaseNotification("Payment","You have received a payment of ".$request->amount." ".$curr->label,$rec->fireBaseToken,$rec->enabledNotification));
            $emailController = new EmailController();
            $emailController->sendMessage("<p> "."You have received a payment of ".$request->amount." ".$curr->label."</p>","Payment received",$rec->email);
            return response()->json([
                'sender' => $acc->lastname." ".$acc->firstname,
                'receiver' => $rec->lastname." ".$rec->firstname,
                'dateTransfer' => $payment->created_at,
                'idTransfer' => substr($payment->idPayment,0,12),
                'amount' => $request->amount,
                'currency' => $request->currency
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
    

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Payment  $payment
     * @return \Illuminate\Http\Response
     */
    public function show(Payment $payment)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Payment  $payment
     * @return \Illuminate\Http\Response
     */
    public function edit(Payment $payment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Payment  $payment
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Payment $payment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Payment  $payment
     * @return \Illuminate\Http\Response
     */
    public function destroy(Payment $payment)
    {
        //
    }
    
    public function findAll()
    {
        $acc = Auth::user();
        return response()->json(Payment::where(["from"=>$acc->idUser,"to"=>$acc->idUser])->orWhere("to","=",$acc->idUser)->orderBy("created_at")->get());
    }
    
    public function findById($id)
    {
        $payment = Payment::find($id);
        $acc = Auth::user();
        if($payment==null)
            return response()->json([
                'error' => "There is no payment with that id"
            ], 404);
        if($payment->from!=$acc->idUser && $payment->to!=$acc->idUser)
            return response()->json([
                'error' => "Forbidden access"
            ], 404);
        return response()->json($payment);
    }
    
    public function fullRefund(Request $request)
    {
        $data_validator = Validator::make($request->all(),
        [
            'id' => 'required|string'
        ]);
        if ($data_validator->fails()) {
            return response()->json([
                'error' => $data_validator->errors()->first()
            ], 422);
        }
        $payment = Payment::find($request->id);
        $acc = Auth::user();
        if($payment==null)
            return response()->json([
                'error' => "There is no payment with that id"
            ], 404);
        if($payment->to!=$acc->idUser)
            return response()->json([
                'error' => "Forbidden access"
            ], 422);
        if($payment->type==2)
            return response()->json([
                'error' => "Not Refundable"
            ], 422);
        $flag = Payment::find(md5($payment->idPayment));
        if($flag==null)
            return response()->json([
                'error' => "Already Refunded"
            ], 422);
        $amount = $payment->amount;
        $rec = User::find($payment->to);
        $currency = $payment->currency;
        $balance = Balance::where([["idUser","=",$acc->idUser],["currency","=",$currency]])->first();
        if($balance==null)
            return response()->json([
                'error' => "This balance doesn't exist"
            ], 422);
        $balanceTo = Balance::where([["idUser","=",$rec->idUser],["currency","=",$currency]])->first();
        if($balanceTo==null)
            return response()->json([
                'error' => "Sender doesn't have any balance with the currency of ".$currency
            ], 422);
        $tax = Tax::first();
        $percent = $tax->taxPercent;
        $amountShouldBeAdded = $request->amount*(1-(float)($percent/100));
        try
        {
            DB::beginTransaction();
            $array = array("idPayment"=>md5($payment->idPayment),"amount"=>$amount,"type"=>$payment->type,"currency"=>$currency,"from"=>$rec->idUser,"to"=>$acc->idUser,"status"=>1,"idPayment"=>md5($rec->idUser.time().$acc->id),"title"=>"Refund from ".$acc->firstname." ".$acc->lastname);
            unset($array["id"]);
            Payment::create($array);
            $balance->amount += $amount;
            $balance->save();
            $balanceTo->amount -= $amountShouldBeAdded;
            $balanceTo->save();
            $payment->status = 3;
            $payment->save();
            $tax->balance -= $amount*((float)($percent/100));             
            $tax->transactionNumber++;             
            $tax->save();
            DB::commit();
            return response()->json([
                'done' => "Refund sent"
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
}
