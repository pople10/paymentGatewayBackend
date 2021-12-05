<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Balance;
use App\Models\AccountType;
use Illuminate\Http\Request;
use Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Notifications\FirebaseNotification;
use App\Notifications\SMSNotification;
use Storage;

class UserController extends Controller
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
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request)
    {
        $data_validator = Validator::make($request->all(),
        [
            'old_password'=>'required|string',
            'password' =>'required|string|min:8|confirmed'
        ]);
        if ($data_validator->fails()) {
            return response()->json([
                'error' => $data_validator->errors()->first()
            ], 422);
        }
        if($request->old_password==$request->password)
        return response()->json([
            'error' => "You should enter a new password different than the old one."
        ], 422);
        $acc = Auth::user();
        if(!Hash::check($request->old_password, $acc->password))
        return response()->json([
            'error' => "Old password is incorrect"
        ], 422);
        $acc->password = Hash::make($request->password);
        if(!$acc->save())
            return response()->json([
                'error' => "Something went wrong"
            ], 422);
        return response()->json([
                'done' => "Updated with success"
            ], 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        //
    }
    
    public function signUp(Request $request)
    {
        $data_validator = Validator::make($request->all(),
        [
            'firstname'=>'required|regex:/^[a-z A-Z]+$/u|min:2',
            'lastname'=>'required|regex:/^[a-z A-Z]+$/u|min:2',
            'username' => 'required|string',
            'title' => 'required|string',
            'phone' => 'required|numeric',
            'cin' => 'required|string',
            'birth' => 'required|date',
            'email' => 'required|email',
            'company' => 'string',
            'password' =>'required|string|min:8|confirmed',
            'accType'=>'required|numeric',
            'mac'=>'required|string',
            'enabledNotification'=>'required|boolean'
        ]);
        if ($data_validator->fails()) {
            return response()->json([
                'error' => $data_validator->errors()->first()
            ], 422);
        }
        if(User::where("username",$request->username)->get()->count()!=0)
            return response()->json([
                'error' => "Username already exists"
            ], 422);
        if(User::where("cin",$request->cin)->get()->count()!=0)
            return response()->json([
                'error' => "You have already an account"
            ], 422);
        if(User::where("phone",$request->phone)->get()->count()!=0)
            return response()->json([
                'error' => "Phone number used before"
            ], 422);
        $request->merge([
            'password' => Hash::make($request->password)
        ]);
        $photo = 'photos/no-photo-women.png';
        if($request->title=='Mr.')
            $photo = 'photos/no-photo-men.png';
        $array = array_merge($request->all(), ['vkey' => random_int(100000, 999999),"api_token"=>Str::random(60),'photo'=>$photo]);
        unset($array["password_confirmation"]);
        $acc;
        if(!$acc = User::create($array))
            return response()->json([
                'error' => "Something went wrong"
            ], 422);
        try
        {
            Balance::create(array("idUser"=>$acc->idUser,"currency"=>"USD","amount"=>0,"label"=>"Default"));
        }
        catch(Excpetion $e)
        {
            
        }
        return response()->json([
                'done' => "Created with success"
            ], 200);
    }
    
    public function login(Request $request)
    {
        $data_validator = Validator::make($request->all(),
        [
            'username' => 'required|string',
            'password' =>'required|string',
            'code'=>'required|numeric'
        ]);
        if ($data_validator->fails()) {
            return response()->json([
                'error' => $data_validator->errors()->first()
            ], 422);
        }
        $acc = User::where([["username","=",$request->username]])->orWhere([["email","=",$request->username]])->get();
        if($acc->count()==0 || !Hash::check($request->password, $acc[0]->password) || $acc[0]->vkey!=$request->code)
        {
            if(count($acc)>0)
            {
                $acc[0]->loginAttemps = $acc[0]->loginAttemps+1;
                $acc[0]->save();
                if($acc[0]->vkey!=$request->code)
                {
                    return response()->json([
                        'error' => "2 step authentification code is incorrect"
                    ], 422);
                }
            }
            return response()->json([
                'error' => "Invalid data"
            ], 422);
        }
        $acc = $acc[0];
        if((time() - strtotime($acc->updated_at))>3600 and $acc->loginAttemps!=0)
        {
            $acc->loginAttemps = 0;
            $acc->save();
        }
        if($acc->loginAttemps>=3)
            return response()->json([
                'error' => "You have reached the maximum login attempts.\nTry again after one hour"
            ], 422);
        if($acc->deleted)
            return response()->json([
                'error' => "Account deleted"
            ], 422);
        if(!$acc->enabled)
            return response()->json([
                'error' => "Account disabled"
            ], 422);
        $acc->vkey = random_int(100000, 999999);
        if($acc->loginAttemps!=0)
        {
            $acc->loginAttemps = 0;
        }
        $acc->save();
        $token = $acc->createToken($acc->idUser." ".$acc->username);
        return response()->json(["token"=>$token->plainTextToken]);
        
    }
    
    public function delete(Request $request)
    {
        Auth::user()->tokens()->delete();
        $acc = Auth::user();
        $acc->deleted = true;
        if(!$acc->save())
            return response()->json([
                'error' => "Something went wrong"
            ], 422);
        return response()->json([
                'done' => "Deleted with success"
            ], 200);
    }
    
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
                'done' => "Logout successfully"
            ], 200);
    }
    
    public function editInfo(Request $request)
    {
        
        $data_validator = Validator::make($request->all(),
        [
            'lastname' => 'required|string',
            'firstname' =>'required|string',
            'email'=>'required|email',
            'phone'=>'required|string',
            'photoAttachement' => 'file|mimes:jpeg,jpg,png'
        ]);
        if ($data_validator->fails()) {
            return response()->json([
                'error' => $data_validator->errors()->first()
            ], 422);
        }
        $user = Auth::user();
        $user->lastname = $request->lastname;
        $user->firstname = $request->firstname;
        if(User::where("email",$request->email)->get()->count()!=0 and $user->email!=$request->email)
            return response()->json([
                'error' => "Email already exists"
            ], 422);
        if(User::where("phone",$request->phone)->get()->count()!=0 and $user->phone!=$request->phone)
            return response()->json([
                'error' => "Phone already exists"
            ], 422);
        $user->email = $request->email;
        $user->phone = $request->phone;
        if($request->photoAttachement)
        {
            if(strpos($user->photo, "no-photo") === false)
                Storage::delete($user->photo);
            $filename=pathinfo($request->photoAttachement->getClientOriginalName(),PATHINFO_FILENAME);
            $extention=pathinfo($request->photoAttachement->getClientOriginalName(),PATHINFO_EXTENSION);
            $path = md5(time(). $filename).".".$extention;
            $path = request()->photoAttachement->storeAs('photos', $path);
            $user->photo = $path;
        }
        if(!$user->save())
            return response()->json([
                'error' => "Something went wrong"
            ], 422);
        return response()->json([
                'done' => "Updated with success"
            ], 200);
    
    }
    
    public function updatePhoto(Request $request)
    {
        $data_validator = Validator::make($request->all(),
        [
            'photo' => 'required|file|mimes:jpeg,jpg,png'
        ]);
        if ($data_validator->fails()) {
            return response()->json([
                'error' => $data_validator->errors()->first()
            ], 422);
        }
        $user = Auth::user();
        if($request->photo)
        {
            if(strpos($user->photo, "no-photo") === false)
                Storage::delete($user->photo);
            $filename=pathinfo($request->photo->getClientOriginalName(),PATHINFO_FILENAME);
            $extention=pathinfo($request->photo->getClientOriginalName(),PATHINFO_EXTENSION);
            $path = md5(time(). $filename).".".$extention;
            $path = request()->photo->storeAs('photos', $path);
            $user->photo = $path;
        }
        if(!$user->save())
            return response()->json([
                'error' => "Something went wrong"
            ], 422);
        return response()->json([
                'done' => "Updated with success"
            ], 200);
    
    }
    
    public function sendResetRequest(Request $request)
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
        $acc = User::where([["username","=",$request->username]])->orWhere("email",$request->username)->get();
        if(count($acc)==0)
            return response()->json([
                'error' => 'The username does not exist'
            ], 422);
        $emailController = new EmailController();
        $acc=$acc[0];
        if(!$emailController->sendMessage("Reset code : ".$acc->vkey."<br><p style='color:#f15036'>Do not communicate this code with nobody!</p>","[FastPayment] ".$acc->vkey." Reset Code",$acc->email))
            return response()->json([
                'error' => "error se produit"
            ], 422);
        try
        {
            $acc->notify(new SMSNotification($acc->phone,"Reset code : ".$acc->vkey));
        }
        catch(Exception $e)
        {
            
        }
        return response()->json([
                'done' => "We sent you a code to your email and phone.\nKindly retrieve it."
            ], 200);
    }
    
    public function resetPassword(Request $request)
    {
        $data_validator = Validator::make($request->all(),
        [
            'username' => 'required|string',
            'code' => 'required|numeric',
            'password' => 'required|min:8|string|confirmed'
        ]);
        if ($data_validator->fails()) {
            return response()->json([
                'error' => $data_validator->errors()->first()
            ], 422);
        }
        $acc = User::where([["username","=",$request->username]])->orWhere("email",$request->username)->get();
        if(count($acc)==0)
            return response()->json([
                'error' => 'There is no username as the given.'
            ], 422);
        $acc=$acc[0];
        if(!$acc->enabled)
            return response()->json([
                'error' => 'Your account already disabled'
            ], 422);
        if($acc->deleted)
            return response()->json([
                'error' => 'Your account already deleted'
            ], 422);
        if($acc->vkey != $request->code)
            return response()->json([
                'error' => 'Code incorrect'
            ], 422);
        $acc->password = Hash::make($request->password);
        $acc->vkey = random_int(100000, 999999);
        if(!$acc->save())
        {
            return response()->json([
                'error' => "Something went wrong"
            ], 422);
        }
        
        $emailController = new EmailController();
        $emailController->sendMessage("Your account password has been changed, if this action doesn't belong to your activities.\nContact us.","Password changed ⚠️",$acc->email);
            
        return response()->json([
            'done' => "Changed with success"
        ], 200);
    }
    
    public function fireBaseTokenPush(Request $request)
    {
        $data_validator = Validator::make($request->all(),
        [
            'token' => 'required|string'
        ]);
        if ($data_validator->fails()) {
            return response()->json([
                'error' => $data_validator->errors()->first()
            ], 422);
        }
        $acc = Auth::user();
        $acc->fireBaseToken = $request->token;
        if(!$acc->save())
            return response()->json([
                'error' => "Something went wrong"
            ], 422);
        return response()->json([
            'done' => "Changed with success"
        ], 200);
    }
    
    public function fireBaseTokenPop()
    {
        $acc = Auth::user();
        $acc->fireBaseToken = "";
        if(!$acc->save())
            return response()->json([
                'error' => "Something went wrong"
            ], 422);
        return response()->json([
            'done' => "Unsubscribed with success"
        ], 200);
    }
    
    public function getProfile()
    {
        $acc = Auth::user();
        $acc->photoUrl = url('storage/app/'.$acc->photo);
        $tmp = AccountType::find($acc->accType);
        $acc->typeAccount="";
        if($tmp!=null)
            $acc->typeAccount = $tmp->label;
        return response()->json($acc);
    }
    
    public function OTP(Request $request)
    {
        $data_validator = Validator::make($request->all(),
        [
            'username' => 'required|string',
            'password' =>'required|string'
        ]);
        if ($data_validator->fails()) {
            return response()->json([
                'error' => $data_validator->errors()->first()
            ], 422);
        }
        $acc = User::where([["username","=",$request->username]])->orWhere([["email","=",$request->username]])->get();
        if($acc->count()==0 || !Hash::check($request->password, $acc[0]->password))
        {
            if(count($acc)>0)
            {
                $acc[0]->loginAttemps = $acc[0]->loginAttemps+1;
                $acc[0]->save();
            }
            return response()->json([
                'error' => "Invalid data"
            ], 422);
        }
        $acc = $acc[0];
        if((time() - strtotime($acc->updated_at))>3600 and $acc->loginAttemps!=0)
        {
            $acc->loginAttemps = 0;
            $acc->save();
        }
        if($acc->loginAttemps>=3)
            return response()->json([
                'error' => "You have reached the maximum login attempts.\nTry again after one hour"
            ], 422);
        if($acc->deleted)
            return response()->json([
                'error' => "Account deleted"
            ], 422);
        if(!$acc->enabled)
            return response()->json([
                'error' => "Account disabled"
            ], 422);
        if($acc->loginAttemps!=0)
        {
            $acc->loginAttemps = 0;
            $acc->save();
        }
        try
        {
            $acc->notify(new SMSNotification($acc->phone,"Authentification code is : ".$acc->vkey));
            return response()->json([
                'done' => "Code sent to your phone number"
            ], 200);
        }
        catch(Exception $e)
        {
            return response()->json([
                'error' => "Something went wrong"
            ], 422);
        }
    }
    
    public function verify(Request $request)
    {
        $data_validator = Validator::make($request->all(),
        [
            'id' => 'required|file|mimes:jpeg,jpg,png'
        ]);
        if ($data_validator->fails()) {
            return response()->json([
                'error' => $data_validator->errors()->first()
            ], 422);
        }
        $acc = Auth::user();
        if($acc->verified)
            return response()->json([
                    'error' => "You are already verified"
                ], 422);
        $filename=pathinfo($request->id->getClientOriginalName(),PATHINFO_FILENAME);
        $extention=pathinfo($request->id->getClientOriginalName(),PATHINFO_EXTENSION);
        $path = md5(time(). $filename).".".$extention;
        $path = request()->id->storeAs('ids', $path);
        $url = url('storage/app/'.$path);
        $data = $this->verifyID($url);
        if($data==null)
            return response()->json([
                'error' => "Something went wrong"
            ], 422);
        $data = json_decode($data);
        /* Privacy purposes */
        Storage::delete($path);
        if($data->response->IDAuthentication=="Passed")
        {
            $firstname = trim(strtolower(preg_replace('/[0-9\@\.\;]+/', '', $data->response->GivenName)));
            $lastname = trim(strtolower(preg_replace('/[0-9\@\.\;]+/', '', $data->response->Surname)));
            $birth = strtotime($data->response->BirthDate);
            $expiring = strtotime($data->response->ExpirationDate);
            if((time()-$expiring)>0)
                return response()->json([
                    'error' => "ID expired"
                ], 433);
            $cin = trim(strtolower($data->response->DocumentNumber));
            if(strpos($firstname, strtolower($acc->firstname)) === false)
                return response()->json([
                    'error' => "First name doesn't match our records"
                ], 422);
            if(strpos($lastname, strtolower($acc->lastname)) === false)
                return response()->json([
                    'error' => "Last name doesn't match our records"
                ], 422);
            if(strtotime($acc->birth)!=$birth)
                return response()->json([
                    'error' => "Birthday doesn't match our records"
                ], 422);
            if(strpos($cin, strtolower($acc->cin)) === false)
                return response()->json([
                    'error' => "Document number doesn't match our records"
                ], 422);
            $acc->verified = true;
            if(!$acc->save())
                return response()->json([
                    'error' => "Something went wrong"
                ], 422);
            $emailController = new EmailController();
            $emailController->sendMessage("Your account has been verified, you can use our sevices freely.","[FastPayment] Verification with success ✔️✔️",$acc->email);
            $acc->notify(new FirebaseNotification("Verification","Your account is verified now",$acc->fireBaseToken,$acc->enabledNotification));
            return response()->json([
                'done' => "Verified with success"
            ], 200);
        }
        else if($data->response->IDAuthentication=="Failed")
        {
            return response()->json([
                'error' => "Non-valid document"
            ], 422);
        }
        else
        {
            return response()->json([
                'error' => "We don't support this document"
            ], 422);
        }
        return $data;
    }
    
    public function verifyID($url)
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
        	CURLOPT_URL => "https://id-verification1.p.rapidapi.com/verifyID/verify?side=front&front_imgurl=".$url."",
        	CURLOPT_RETURNTRANSFER => true,
        	CURLOPT_FOLLOWLOCATION => true,
        	CURLOPT_ENCODING => "",
        	CURLOPT_MAXREDIRS => 10,
        	CURLOPT_TIMEOUT => 30,
        	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        	CURLOPT_CUSTOMREQUEST => "GET",
        	CURLOPT_HTTPHEADER => [
        		"x-rapidapi-host: id-verification1.p.rapidapi.com",
        		"x-rapidapi-key: 0e1ed1f144mshe5ad421b2180d43p109108jsnbecb2b1ed202"
        	],
        ]);
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        
        curl_close($curl);
        
        if ($err) {
        	return null;
        } else {
        	return $response;
        }
    }
}
