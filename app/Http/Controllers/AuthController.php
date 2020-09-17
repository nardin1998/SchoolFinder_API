<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller; 
use App\Http\Controllers\Auth\VerificationController;
use Illuminate\Http\Request;
use App\Models\User;
use Validator;
use Illuminate\Support\Facades\Auth; 
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use App\Notifications\RegisterMailActivate;

class AuthController extends Controller
{
    private function createToken($user){
        //creating access tokens..
        $tokenResult = $user->createToken('school finder app');
        $token = $tokenResult->token;
        $token->expires_at = Carbon::now()->addDays(365);
        $token->save();
        $user->access_token = $tokenResult->accessToken;
        $user->save();
        return $tokenResult->accessToken;
    }
    //login
    public function login(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'password' => 'required|string',
        ]);

        $name = $request->name;

        //if user sent their email 
        if(filter_var($name, FILTER_VALIDATE_EMAIL)) 
            $user=Auth::attempt(['email' => $name, 'password' => $request->password]);
        //else if they sent their name instead 
        else $user=Auth::attempt(['name' => $name, 'password' => $request->password]);
        
        if(!$user) return response()->json(['error' => 'Unauthorized'], 401);
        
        $user = $request->user();
        if($user->email_verified_at == null) return response()->json(['error' => 'Unverified'], 401);
        $accessToken=$this->createToken($user);
        
        return response()->json([
            'access_token' => $accessToken,
            'token_type' => 'Bearer',
        ]);
    }
 
    //register
    public function register(Request $request) 
    { 
        //for validation 
        $rules = [
            'email' => 'required|string|email|unique:users', 
            'password' => 'required|string|min:8|confirmed', 
            'name' =>'required|string|unique:users',
            'role' =>'string',
        ];
        $validator = Validator::make($request->all(),$rules);
        if ($validator->fails()) 
            return response()->json(['error'=>$validator->errors()], 400); //bad request    
        
        $input = $request->all(); 
        $input['password'] = Hash::make($input['password']); 

        //create the user in the database and send email verification message
        $user = User::create($input);
        //if the user is app admin so no need for verification
        if($user->role == 'app_admin' || $user->role == 'admin'){
            $user->email_verified_at = Carbon::now();
            $user->save();
            $accessToken=$this->createToken($user);
            return response()->json([
                'message' => 'Successfully created user!',
                'access_token' => $accessToken,
                'token_type' => 'Bearer',
            ]);
        }
        else{
            $user->notify(new RegisterMailActivate($user));
            return response()->json(['message' => 'Successfully created user, just verify it!'], 201);
        } 
    }

    //verify the registeration
    public function registerActivate($token)
    {
        $user = User::where('verify_token', $token)->first();
        if (!$user) {
            return response()->json([
                'message' => 'This verification token is invalid..'
            ], 404);
        }
        $user->email_verified_at = Carbon::now();
        $user->save();
        return view('thanks');
    }

    //logout
    public function logout(Request $request)
    {
        $request->user()->token()->revoke();
        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }

    //getId from the access token
    /*public function getId(Request $request)
    {
        $user = $request->user();
        if(!$user) return response()->json([
            'message' => 'User not found',
        ]);
        return $user->id;
    }*/
}
