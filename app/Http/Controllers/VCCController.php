<?php

namespace App\Http\Controllers;

use App\Models\VCC;
use App\Models\Currency;
use App\Models\Balance;
use App\Models\BIN;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

set_time_limit(0);

class VCCController extends Controller
{
    
    private $years = 5;
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
        $data_validator = Validator::make($request->all(),
        [
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
        $limit = $acc->vccLimit;
        if(count(VCC::whereDate('created_at', Carbon::today())->where(["idUser"=>$acc->idUser])->get())>=$limit)
            return response()->json([
                'error' => "You have reached the maximum creation limit per day."
            ], 422);
        $bin = BIN::first()->value;
        $expiring = $this->getMonth()."/".$this->getYear();
        $cmp=0;
        while(true)
        {
            $append = $acc->vkey = $this->generateRandomInt(8);
            $number = $bin.$append;
            $cvv = $this->generateRandomInt(3);
            $currency = $request->currency;
            $array = array_merge($request->all(),["idUser"=>$acc->idUser,"number"=>$number,"expiringDate"=>$expiring,"cvv"=>$cvv,"idCurrency"=>$currency]);
            unset($array["currency"]);
            try
            {
                $res = VCC::create($array);
                return response()->json([
                        'done' => "Created with success"
                    ], 200);
            }
            catch(Exception $e)
            {
                $cmp++;
                if($cmp>1000000)
                    return response()->json([
                        'error' => "Something went wrong."
                    ], 422);
            }
        }
    }
    
    private function generateRandomInt($n)
    {
        $res = "";
        for($i=0;$i<$n;$i++)
        {
            $res.=random_int(1,9);
        }
        return $res;
    }
    
    private function getMonth()
    {
        $month = date('m', time());
        if($month<10)
            return "0".$month;
        return $month;
    }
    
    private function getYear()
    {
        return substr(date('Y', time())+$this->years,-2);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\VCC  $vCC
     * @return \Illuminate\Http\Response
     */
    public function show(VCC $vCC)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\VCC  $vCC
     * @return \Illuminate\Http\Response
     */
    public function edit(VCC $vCC)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\VCC  $vCC
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, VCC $vCC)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\VCC  $vCC
     * @return \Illuminate\Http\Response
     */
    public function destroy($number)
    {
        $vcc = VCC::where("number","=",$number)->first();
        if($vcc==null)
            return response()->json([
                'error' => "There is no card with number."
            ], 404);
        $vcc->active = false;
        if(!$vcc->save())
            return response()->json([
                'error' => "Something went wrong."
            ], 422);
        return response()->json([
            'done' => "Deleted with success."
        ], 200);
    }
    
    public function serve(Request $request)
    {
        $data_validator = Validator::make($request->all(),
        [
            'number' => 'required|numeric',
            'expiring' => 'required|string',
            'cvv' => 'required|numeric',
            'amount'=>'required|numeric'
        ]);
        if ($data_validator->fails()) {
            return response()->json([
                'error' => $data_validator->errors()->first()
            ], 422);
        }
        $vcc = VCC::where("number","=",$request->number)->first();
        if($vcc==null)
            return response()->json([
                'error' => "This card doesn't exist."
            ], 404);
        if($vcc->expiringDate!=$request->expiring)
            return response()->json([
                'error' => "Invalid expiring date."
            ], 422);
        if($vcc->cvv!=$request->cvv)
            return response()->json([
                'error' => "Invalid cvv."
            ], 422);
        $balance = Balance::where([["idUser","=",$vcc->idUser],["currency","=",$vcc->idCurrency]])->first();
        if($balance == null)
            return response()->json([
                'error' => "No balance with card currency."
            ], 404);
        if($balance->amount<$request->amount)
            return response()->json([
                'error' => "Insuffisant money."
            ], 422);
        $array = array();
        $user = User::find($vcc->idUser);
        if($user->deleted || !$user->enabled || !$user->verified || !$user->enabledSending)
            return response()->json([
                'error' => "User blocked in our system."
            ], 422);
        $array["firstname"]=$user->firstname;
        $array["lastname"]=$user->lastname;
        $balance->amount -= $request->amount;
        if(!$balance->save())
            return response()->json([
                'error' => "Something went wrong."
            ], 422);
        $vcc->save();
        return response()->json($array, 200);
    }
    
    public function findAll()
    {
        $acc = Auth::user();
        $vccs = VCC::where([["idUser","=",$acc->idUser],["active","=","1"]])->get();
        if(count($vccs)==0)
            return response()->json([
                'error' => "No vccs are created yet."
            ], 404);
        foreach($vccs as $val)
        {
            $val->name=strtoupper($acc->lastname)." ".strtoupper($acc->firstname);
        }
        return response()->json($vccs);
    }
}
