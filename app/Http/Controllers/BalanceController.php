<?php

namespace App\Http\Controllers;

use App\Models\Balance;
use App\Models\Currency;
use Illuminate\Http\Request;
use App\Models\Tax;
use App\Models\Payment;
use Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use DB;
use App\Notifications\FirebaseNotification;
use Illuminate\Pagination\Paginator;
class BalanceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        
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
        $data_validator = Validator::make($request->all(),
        [
            'label' => 'required|string',
            'currency' => 'required|string'
        ]);
        if ($data_validator->fails()) {
            return response()->json([
                'error' => $data_validator->errors()->first()
            ], 422);
        }
        $acc = Auth::user();
        if(Currency::find($request->currency)==null)
            return response()->json([
                'error' => "We don't have this currency yet."
            ], 422);
        if(count(Balance::where(["idUser"=>$acc->idUser,"currency"=>$request->currency])->get())>0)
            return response()->json([
                'error' => "You have already an open balance with ".$request->currency."."
            ], 422);
        $array = array_merge($request->all(),["idUser"=>$acc->idUser,"amount"=>0]);
        if(!Balance::create($array))
            return response()->json([
                'error' => "Something went wrong"
            ], 422);
        return response()->json([
                'done' => "Created successfully"
            ], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Balance  $balance
     * @return \Illuminate\Http\Response
     */
    public function show(Balance $balance)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Balance  $balance
     * @return \Illuminate\Http\Response
     */
    public function edit(Balance $balance)
    {
        
    }
    
    public function modify(Request $request)
    {
        
        $data_validator = Validator::make($request->all(),
        [
            'label' => 'required|string',
            'id' => 'required|string'
        ]);
        if ($data_validator->fails()) {
            return response()->json([
                'error' => $data_validator->errors()->first()
            ], 422);
        }
        $acc = Auth::user();
        $balance = Balance::where([["idUser","=",$acc->idUser],["id","=",$request->id]])->first();
        if($balance==null)
            return response()->json([
                'error' => "This balance doesn't exist"
            ], 404);
        $balance->label = $request->label;
        if(!$balance->save())
            return response()->json([
                'error' => "Something went wrong"
            ], 422);
        return response()->json([
                'done' => "Updated successfully"
            ], 200);
    
    }
    
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Balance  $balance
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Balance $balance)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Balance  $balance
     * @return \Illuminate\Http\Response
     */
    public function destroy(Balance $balance)
    {
        
    }
    
    
    public function delete($id)
    {
        $array = array();
        $array["id"] = $id;
        $data_validator = Validator::make($array,
        [
            'id' => 'required|string'
        ]);
        if ($data_validator->fails()) {
            return response()->json([
                'error' => $data_validator->errors()->first()
            ], 422);
        }
        $acc = Auth::user();
        $balance = Balance::where([["idUser","=",$acc->idUser],["id","=",$array["id"]]])->first();
        if($balance==null)
            return response()->json([
                'error' => "This balance doesn't exist"
            ], 404);
        if($balance->amount!=0)
            return response()->json([
                'error' => "You cannot close a balance with money there or with due"
            ], 422);
        if(count(Balance::where(["idUser"=>$acc->idUser])->get())<=1)
            return response()->json([
                'error' => "You need to have at least one"
            ], 422);
        $q = 'DELETE FROM balances where idUser = ? and id = ?';
        if(!DB::delete($q, [$acc->idUser,$array["id"]]))
            return response()->json([
                'error' => "Something went wrong"
            ], 422);
        return response()->json([
                'done' => "closed successfully"
            ], 200);
    
    }
    
    public function getCurrencyAvailable()
    {
        $acc = Auth::user();
        $balances = Balance::where([["idUser","=",$acc->idUser]])->get();
        $used = array();
        foreach($balances as $val)
        {
            $used[]=$val->currency;
        }
        return response()->json(Currency::whereNotIn("idCurrency",$used)->get());
    }
    
    public function getCurrencyUsed()
    {
        $acc = Auth::user();
        $balances = Balance::where([["idUser","=",$acc->idUser]])->get();
        $used = array();
        foreach($balances as $val)
        {
            $used[]=$val->currency;
        }
        return response()->json(Currency::whereIn("idCurrency",$used)->get());
    }
    
    public function getCurrencyUsedExcept($curr)
    {
        $acc = Auth::user();
        $balances = Balance::where([["idUser","=",$acc->idUser],["currency","<>",$curr]])->get();
        $used = array();
        foreach($balances as $val)
        {
            $used[]=$val->currency;
        }
        return response()->json(Currency::whereIn("idCurrency",$used)->get());
    }

    
    public function findAll()
    {
        $acc = Auth::user();
        $data = Balance::where([["idUser","=",$acc->idUser]])->get();
        if(count($data)==0)
            return response()->json([
                'error' => "There are no balances"
            ], 422);
        return response()->json($data);
    }
    
    public function findById($id)
    {
        $acc = Auth::user();
        $data = Balance::where([["idUser","=",$acc->idUser],["id","=",$id]])->first();
        if($data==null)
            return response()->json([
                'error' => "There is no balance"
            ], 404);
        return response()->json($data);
    }
    
    public function sendRequestRate($base)
    {
        $url = "https://freecurrencyapi.net/api/v2/latest?apikey=c3248f10-4784-11ec-ab84-b3f9794912ff&base_currency=".$base;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }
    
    public function getRate($base,$to)
    {
        $res = $this->getRateV2($base,$to);
        if($res==null)
            return response()->json([
                'error' => "Currency doesn't exist in our system"
            ], 404);
        return response()->json($res);
    }
    
    public function getRateV2($base,$to)
    {
        $response = $this->sendRequestRate($base);
        $res = json_decode($response)->data;
        if(!property_exists($res,$to))
            return null;
        return $res->{$to};
    }
    
    public function convert(Request $request)
    {
        $data_validator = Validator::make($request->all(),
        [
            'base' => 'required|string',
            'to' => 'required|string',
            'amount' => 'required|numeric'
        ]);
        if ($data_validator->fails()) {
            return response()->json([
                'error' => $data_validator->errors()->first()
            ], 422);
        }
        $base = $request->base;
        $to = $request->to;
        if($base==$to)
            return response()->json([
                'error' => "Currencies are different"
            ], 422);
        $amount = $request->amount;
        $acc = Auth::user();
        $balanceBase = Balance::where([["idUser","=",$acc->idUser],["currency","=",$base]])->first();
        if($balanceBase==null)
            return response()->json([
                'error' => "Balance source does not exist"
            ], 422);
        if($balanceBase->amount<$amount)
            return response()->json([
                'error' => "Insufficient funds"
            ], 422);
        $balanceTo = Balance::where([["idUser","=",$acc->idUser],["currency","=",$to]])->first();
        if($balanceTo==null)
            return response()->json([
                'error' => "Balance target does not exist"
            ], 422);
        $tax = Tax::first();
        $percent = $tax->taxPercent;
        $amountShouldBeAdded = $request->amount*(1-(float)($percent/100));
        try
        {
            DB::beginTransaction();
            $balanceBase->amount -= $amount;
            $balanceBase->save();
            $rate = $this->getRateV2($base,$to);
            $balanceTo->amount += $rate*$amountShouldBeAdded;
            $balanceTo->save();
            $tax->balance += $request->amount*((float)($percent/100));
            $tax->transactionNumber++;
            $tax->save();
            Payment::create(array("idPayment"=>md5($acc->idUser.time().$acc->idUser),"from"=>$acc->idUser,"to"=>$acc->idUser,"title"=>"Conversion from ".$base." to ".$to,
            "amount"=>$amount,"status"=>1,"type"=>2,"currency"=>$base));
            DB::commit();
            Auth::user()->notify(new FirebaseNotification("Conversion","You have converted ".$amount." ".$base." to ".$to,$acc->fireBaseToken,$acc->enabledNotification));
            return response()->json([
                'done' => "Conversion with succès"
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
    
      public function getStatisticInfo(){
        
        $id = Auth::user()->idUser;
        $balances = Balance::where("idUser","=",$id)->get();
        $data = [];
        foreach($balances as $b){
            $s = [];
            $s["balance"]=$b;
            $s["income"]= number_format((float)Payment::where("to","=",$id)->where("currency","=",$b->currency)->get()->sum("amount"), 2, '.', '');
            $s["expense"]=number_format((float)Payment::where("from","=",$id)->where("currency","=",$b->currency)->get()->sum("amount"), 2, '.', '');
            $s["expenseData"]=Payment::where("from","=",$id)->where("currency","=",$b->currency)->paginate(5)->pluck('amount')->toArray();
            $s["incomeData"]=Payment::where("to","=",$id)->where("currency","=",$b->currency)->paginate(5)->pluck('amount')->toArray();
            array_push($data,$s);
        }
        return response()->json($data);
    }
    public function getPayments(Request $r){
        $acc = Auth::user();
        $id = $acc->idUser;
        $payments = Payment::where("to","=",$id)->orWhere("from","=",$id)
        ->paginate(10);
        $data = [];
        foreach($payments as $p){
            $s = [];
            $s["idPayment"] = $p->idPayment;
            $s["from"] = $p->from;
            $s["to"] = $p->to;
            $s["currency"] = $p->currency;
            $s["amount"] = $p->amount;
            $s["status"] = $p->status;
            $s["typeT"] = $p->type;
            $s["date"] = $p->created_at;
            $s["balance"] = Balance::where("idUser","=",$id)->where("currency","=",$p->currency)->first();
            if($s["balance"]==null)
                $s["balance"]="";
            else
                $s["balance"]=$s["balance"]->label;

            if($p->from == $p->to){
                $s["typeT"] = 0;
                $s["name"] =  $acc->username;
            }
            else if($p->to == $id){
                 $s["typeT"] = 1;
                 $a = DB::table('users')->where("idUser","=",$p->from)->first();
                 $s["name"] = $a->username;
            }
            else{
                 $s["typeT"] = -1;
                 $a = DB::table('users')->where("idUser","=",$p->to)->first();
                 $s["name"] = $a->username;
            }
                
            array_push($data,$s);
        }
        $pym = [];
        $pym["more"] = $payments->hasMorePages();
        $pym["data"] = $data;
        return response()->json($pym);
    }
     public function getTransaction(Request $r){
        $acc = Auth::user();
        $id = $acc->idUser;
        
        $p = Payment::where("idPayment","=",$r->idPayment)->first();
        if(is_null($p))
            return response()->json([
                    'error' => "There is no Transaction with these id"
                ], 422);
            
        $s = [];
        
        $s["from"] = $p->from;
        $s["to"] = $p->to;
        $s["currency"] = $p->currency;
        $s["amount"] = $p->amount;
        $s["status"] = $p->status;
        $s["date"] = $p->created_at;            
        $s["balance"] = Balance::where("idUser","=",$id)->where("currency","=",$p->currency)->first()->label;
        if($p->from == $p->to){
                $s["typeT"] = 0;
                $s["fullname"] = $acc->firstname." ".$acc->lastname;
                $s["name"] =  $acc->username;
                $s["title"] = $p->title;   
        }
        else if($p->to == $id){
                 $s["typeT"] = 1;
                 $a = DB::table('users')->where("idUser","=",$p->from)->first();
                 $s["fullname"] = $a->firstname." ".$a->lastname;
                 $s["name"] = $a->username;
                 $s["title"] = "Payment sent from ".$s["fullname"];   
        }
            else{
                 $s["typeT"] = -1;
                 $a = DB::table('users')->where("idUser","=",$p->to)->first();
                 $s["fullname"] = $a->firstname." ".$a->lastname;
                 $s["name"] = $a->username;
                $s["title"] = "Payment sent to ".$s["fullname"];   
        }
        $s["type"]=DB::table("statuses")->where('idStatus',"=",$p->status)->first()->label;
        
        return response()->json($s);
    }
}
