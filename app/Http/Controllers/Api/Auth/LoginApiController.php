<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\User;
use App\Profile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use App\Mail\SendRegMail;
use App\Mail\ForgotPassword;
use App\Mail\ResetForgotPassword;
use App\Mail\ActiveRegisterAccount;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
// https://blog.pusher.com/laravel-jwt/ --tutorial

class LoginApiController extends Controller
{


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
    public function register_submit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|max:255',
            'email' => 'required|email|unique:users|max:255',
            'password' => 'required|confirmed|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->getMessageBag(),
                'alert' => 'Form submission failed.',
                'status' => 0
            ],201);
        }

            $remember_token = Str::random(60);

            $user_reg = new User;
            $user_reg->name = $request->username;
            $user_reg->email = $request->email;
            $user_reg->type = "user";
            $user_reg->password = Hash::make($request->password);
            $user_reg->remember_token = Hash::make($remember_token);
            $user_reg->save();    
    
            $token = JWTAuth::fromUser($user_reg);
            
    
            if(@$user_reg){
                
                $data = [
                    'email' => $request->email,
                    'url' => 'http://qnaonline.tech/QnaAng/#/forgot/password/reset/'.$request->email.'/'.$remember_token,
                    // 'url' => 'http://localhost:4200/#/active/register/account/'.$request->email.'/'.$remember_token,                    
                    'username' => $request->username,
                    'password' => $request->password
                ];

                Mail::to($request->email)->send(new SendRegMail($data));
                if (Mail::failures()) {
                    $mail_status = 0;
                    $mail_msg = 'Mail submission failed.';
                }else{
    
                    $mail_status = 1;
                    $mail_msg = 'Mail sucessfully sent.';
                }
    
                return response()->json([
                    'token' => $token,
                    'user' => $user_reg,
                    'success' => 'Form successfully submitted',
                    'mail_status' => $mail_status,
                    'mail_msg' => $mail_msg,
                    'status' => 1
                ],201);
            }else{
                return response()->json([
                    'alert' => 'Form submission failed',
                    'status' => 0
                ],400);
            }
        
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function register_social_submit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255',
            'email' => 'required|email|unique:users|max:255',
            'provider' => 'required',
            'id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->getMessageBag(),
                'alert' => 'Form submission failed.',
                'status' => 0
            ],201);
        }      

        $user_reg = new User;
        $user_reg->name = $request->name;
        $user_reg->email = $request->email;
        $user_reg->type = "user";
        $user_reg->password = Hash::make($request->id);
        $user_reg->social_provider = $request->provider;
        $user_reg->social_id = $request->id;
        $user_reg->status = 1;
        $user_reg->save(); 
         
        if($request->photoUrl){
            $profile = $user_reg->profile ?: new Profile;
            $profile->avatar = $request->photoUrl;
            $user_reg->profile()->save($profile);
        }

        $token = JWTAuth::fromUser($user_reg);
        

        if(@$user_reg){
            return response()->json([
                'token' => $token,
                'user' => $user_reg,
                'success' => 'Form successfully submitted',
                'status' => 1
            ],201);
        }else{
            return response()->json([
                'alert' => 'Form submission failed',
                'status' => 0
            ],400);
        }
    }

        /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function authenticate(Request $request)
    {
        
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|min:8',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->getMessageBag(),
                'alert' => 'Authentication failed.',
                'status' => 0
            ],201);
        }

        $credentials = $request->only('email', 'password');

        try {
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'error' => 'invalid_credentials',
                    'status' => 0
                ], 200);
            }
        } catch (JWTException $e) {
            return response()->json([
                'error' => 'could_not_create_token',
                'status' => 0
            ], 500);
        }

        $user = User::where('email',$request->email)->first();
        if(!$user->status){
            return response()->json([
                    'error' => 'Please active your account through mail.',
                    'status' => 0
                ], 200);
        }else{

            return response()->json([
                    'token' => $token,
                    'success' => 'Login Successfull',
                    'status' => 1
                ], 200);
     
        }
    }



        /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function authenticate_social(Request $request)
    {
        
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'name' => 'required',
            'provider' => 'required',
            'id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->getMessageBag(),
                'alert' => 'Authentication failed.',
                'status' => 0,
                'regSt' => 0
            ],201);
        }

        $user_data = User::where('email',$request->email)->where('social_id',$request->id)->first();
        if($user_data){
            if(!$user_data->status){

                return response()->json([
                    'error' => 'Please active your account through mail.',
                    'status' => 0,
                    'regSt' => 0
                ],200); 

            }else{
                $credentials = [
                    'email' => $request->email,
                    'password' => $user_data->social_id
                ];


                try {
                    if (! $token = JWTAuth::attempt($credentials)) {
                        return response()->json([
                            'error' => 'invalid_credentials',
                            'status' => 0,
                            'regSt' => 0
                        ], 200);
                    }
                } catch (JWTException $e) {
                    return response()->json([
                        'error' => 'could_not_create_token',
                        'status' => 0,
                        'regSt' => 0
                    ], 500);
                }

                return response()->json([
                        'token' => $token,
                        'success' => 'Login Successfull',
                        'status' => 1,
                        'regSt' => 0
                    ], 200);
            }

        }else{
            return response()->json([
                'status' => 0,
                'regSt' => 1
            ],200);
        }
    }


    public function getAuthenticatedUser()
    {
        try {

                if (! $user = JWTAuth::parseToken()->authenticate()) {
                        return response()->json([
                            'error'=>'user_not_found',
                            'status'=> 0
                        ], 404);
                }

        } catch (Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {

                return response()->json([
                    'error'=>'token_expired',
                    'status'=> 0
                ], $e->getStatusCode());

        } catch (Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {

                return response()->json([
                    'error'=>'token_invalid',
                    'status' =>0
                ], $e->getStatusCode());

        } catch (Tymon\JWTAuth\Exceptions\JWTException $e) {

                return response()->json([
                    'error'=>'token_absent',
                    'status'=>0
                ], $e->getStatusCode());

        }

        $profile = Profile::where('user_id',$user->id)->first();
         return response()->json([
                            'user' => $user,
                            'profile' => $profile,
                            'success'=>'Welcome '.$user->name,
                            'status'=> 1
                        ], 200);
        
    }



    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function profile_update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|max:255',
            'id' => 'required'
        ]);

        if($request->password){
	        $validator = Validator::make($request->all(), [
            	'password' => 'required|confirmed|min:8'
	        ]);
        }

        if($request->avatar){
	        $validator = Validator::make($request->all(), [
            	'avatar' => 'mimes:jpeg,png,jpg|max:20000'
	        ]);
        }        

        if($validator->fails()) {
            return response()->json([
                'errors' => $validator->getMessageBag(),
                'alert' => 'Form submission failed.',
                'status' => 0
            ],201);
        }      

        $user_up = User::find($request->id);
        $user_up->name = $request->username;
        if($request->password){
        	$user_up->password = Hash::make($request->password);
    	}
        $user_up->save(); 
         


        $profile = $user_up->profile ?: new Profile;
        if($request->hasFile('avatar'))
        {
            if(str_contains($profile->avatar, 'images')){
        	$old_avatar_array = explode('images/',$profile->avatar);
            	$old_avatar = $old_avatar_array[1];

    	        if(file_exists(public_path('/upload/images/'.$old_avatar))){
    	            unlink(public_path('/upload/images/'.$old_avatar));
    	        }; 
            }
            $img_file = $request->avatar;
            $img_new_name = time().$img_file->getClientOriginalName();
            $img_file->move(public_path().'/upload/images/',$img_new_name);
            $profile->avatar = $img_new_name;
        }

        $profile->mobile = $request->mobile;
        $profile->city = $request->city;
        $profile->country = $request->country;
        $profile->pin = $request->pin;
        $profile->address = $request->address;
        $profile->about = $request->about;
        $profile->facebook = $request->facebook;
        $profile->youtube = $request->youtube;
        $profile->twitter = $request->twitter;
        $profile->linkedin = $request->linkedin;
        $profile->google = $request->google;
        $profile->instagram = $request->instagram;        
        $profile->resume = $request->resume;        
        $user_up->profile()->save($profile);
        

        if(@$user_up){
            return response()->json([
                'user' => $user_up,
                'success' => 'Form successfully submitted',
                'status' => 1
            ],201);
        }else{
            return response()->json([
                'alert' => 'Form submission failed',
                'status' => 0
            ],400);
        }

    }




        /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function forgot_password(Request $request)
    {
        
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->getMessageBag(),
                'alert' => 'Authentication failed.',
                'status' => 0,
            ],201);
        }

        $user_data = User::where('email',$request->email)->where('social_id',null)->first();
        if($user_data){

            $remember_token = Str::random(60);
            $user_data->remember_token = Hash::make($remember_token);
            $user_data->save(); 


            $data = [
                    'email' => $request->email,
                    'url' => 'http://qnaonline.tech/QnaAng/#/forgot/password/reset/'.$request->email.'/'.$remember_token
                    // 'url' => 'http://localhost:4200/#/forgot/password/reset/'.$request->email.'/'.$remember_token
                ];

            Mail::to($request->email)->send(new ForgotPassword($data));
            if (Mail::failures()) {
                $mail_status = 0;
                $mail_msg = 'Mail send failed.';
            }else{
                $mail_status = 1;
                $mail_msg = 'Mail sucessfully sent.';
            }


            return response()->json([
                    'success' => 'Email Successfull sended.',
                    'data' => $data,
                    'mail_status' => $mail_status,
                    'mail_msg' => $mail_msg,
                    'status' => 1,
                ], 200);

        }else{
            return response()->json([
                'status' => 0,
                'error' => 'Email does not exist.'
            ],200);
        }
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function forgot_password_reset($email,$remember_token)
    {
        $user_data = User::where('email',$email)->first();
        if($user_data){            
            if(Hash::check($remember_token,$user_data->remember_token)){

            $password = Str::random(8);

            $user_data->password = Hash::make($password);
            $user_data->remember_token = null;
            $user_data->save(); 

            $token = JWTAuth::fromUser($user_data);
            $data = [
                    'email' => $email,
                    'password' => $password,
                    'username' => $user_data->name
                ];

                Mail::to($email)->send(new ResetForgotPassword($data));
                if (Mail::failures()) {
                    $mail_status = 0;
                    $mail_msg = 'Mail send failed.';
                }else{
                    $mail_status = 1;
                    $mail_msg = 'Mail sucessfully sent.';
                }

                return response()->json([
                        'success' => 'Please check mail, auto generated password sended.',
                        'data' => $data,
                        'token' => $token,
                        'mail_status' => $mail_status,
                        'mail_msg' => $mail_msg,                        
                        'status' => 1,
                    ], 200);
            }else{
                return response()->json([
                    'status' => 0,
                    'error' => 'Url not valid.'
                ],200);
            }

        }else{
            return response()->json([
                'status' => 0,
                'error' => 'Email does not exist.'
            ],200);
        }
    }




    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function active_register_account($email,$remember_token)
    {
        $user_data = User::where('email',$email)->first();
        if($user_data){            
            if(Hash::check($remember_token,$user_data->remember_token)){

            $password = Str::random(8);

            $user_data->status = 1;
            $user_data->remember_token = null;
            $user_data->save(); 

            $data = [
                    'email' => $email,
                    'username' => $user_data->name
                ];

                Mail::to($email)->send(new ActiveRegisterAccount($data));
                if (Mail::failures()) {
                    $mail_status = 0;
                    $mail_msg = 'Mail send failed.';
                }else{
                    $mail_status = 1;
                    $mail_msg = 'Mail sucessfully sent.';
                }

                return response()->json([
                        'success' => 'Your account activated.',
                        'data' => $data,
                        'mail_status' => $mail_status,
                        'mail_msg' => $mail_msg,                        
                        'status' => 1,
                    ], 200);
            }else{
                return response()->json([
                    'status' => 0,
                    'error' => 'Url not valid.'
                ],200);
            }

        }else{
            return response()->json([
                'status' => 0,
                'error' => 'Email does not exist.'
            ],200);
        }
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
