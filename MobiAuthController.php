<?php

namespace Mobidev\Auth;

use App\Status;
use App\Type;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Mobidev\Auth\User;
use \Firebase\JWT\JWT;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class MobiAuthController extends Controller
{

    /**
     * If User exists return its data if not create new User Record
     *
     * @param  [string] name
     * @param  [string] email
     * @param  [string] password
     * @param  [string] password_confirmation
     * @return [string] message
     */
    public function registerOrLogin(Request $request)
    {
        $jwt = $request->bearerToken();

        $decodet_JWT = $this->decodeFirbaseToken($jwt);

        if (array_key_exists('error', $decodet_JWT))
            return response()->json($decodet_JWT["error"], $decodet_JWT["type"]);

        $user = User::where('uid', '=', $decodet_JWT->user_id)->first();
        if ($user === null) {
            $user = new User();
            $user->uid = $decodet_JWT->user_id;
        }

        $user = $this->handleUserDataBasedOnProviderType($decodet_JWT, $user, "registerorlogin", $request);

        if (array_key_exists('error', $user))
            return response()->json($user["error"], $user["type"]);

        $tokenResult = $this->createNewTokenUsingPassport($request, $user);

        return $this->returnJsonResponseWithUserDataAndAccessToken($user, $tokenResult);

    }


    /**
     * Create user
     *
     * @param  [string] Firebase_Token
     * @return  mixed user_data
     * @return [string] access_token
     * @return [string] token_type
     * @return [string] expires_at
     */
    public function register(Request $request)
    {
        $jwt = $request->bearerToken();

        $decodet_JWT = $this->decodeFirbaseToken($jwt);

        if (array_key_exists('error', $decodet_JWT))
            return response()->json($decodet_JWT["error"], $decodet_JWT["type"]);


        //user exists?
        $user = User::where('uid', '=', $decodet_JWT->user_id)->first();
        if ($user !== null) {
            return response()->json([
                'error' => "user already exists",
                'user' => $user
            ], 401);
        }

        $user = new User();
        $user->uid = $decodet_JWT->user_id;

        $user = $this->handleUserDataBasedOnProviderType($decodet_JWT, $user, "register", $request);

        if (array_key_exists('error', $user))
            return response()->json($user["error"], $user["type"]);

        $tokenResult = $this->createNewTokenUsingPassport($request, $user);

        return $this->returnJsonResponseWithUserDataAndAccessToken($user, $tokenResult);
    }

    /**
     * Login user and create token
     *
     * @param  [string] Firebase_Token
     * @return  mixed user_data
     * @return [string] access_token
     * @return [string] token_type
     * @return [string] expires_at
     */
    public function login(Request $request)
    {
        $jwt = $request->bearerToken();

        $decodet_JWT = $this->decodeFirbaseToken($jwt);

        if (array_key_exists('error', $decodet_JWT))
            return response()->json($decodet_JWT["error"], $decodet_JWT["type"]);

        //user exists?
        $user = User::where('uid', '=', $decodet_JWT->user_id)->first();
        if ($user === null) {
            return response()->json([
                'error' => "user doesn't exist"
            ], 401);
        }

        $user = $this->handleUserDataBasedOnProviderType($decodet_JWT, $user, "login", $request);

        $tokenResult = $this->createNewTokenUsingPassport($request, $user);

        return $this->returnJsonResponseWithUserDataAndAccessToken($user, $tokenResult);
    }


    /**
     * Logout user (Revoke the token)
     *
     * @return [string] message
     */
    public function logout(Request $request)
    {
        $request->user()->token()->revoke();
        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }

    private function decodeFirbaseToken($jwt)
    {
        $publicKeyURL = Config::get('app.mobiauth.publickeyurl');

        try {
            $key = json_decode(file_get_contents($publicKeyURL), true);
        } catch (\Exception $e) {
            return array("error" => array(
                "error" => $e->getMessage()
            ), "type" => 503);
        }

        try {
            $decodet_JWT = JWT::decode($jwt, $key, array(Config::get('app.mobiauth.encalgorithm')));
        } catch (\Exception $e) {
            return array("error" => array(
                "error" => $e->getMessage()
            ), "type" => 401);
        }

        if ($decodet_JWT->aud !== Config::get('app.mobiauth.aud')) {
            return array("error" => array(
                "error" => "it's valid Firebase JWT but its not from our project"
            ), "type" => 401);
        }

        return $decodet_JWT;
    }

    private function handleUserDataBasedOnProviderType($decodet_JWT, $user, $auth_type, $request)
    {
        if($auth_type === 'register' || $auth_type === 'registerorlogin') {

            if(!isset($request->user_type))
                return array("error" => array(
                    "error" => "please provide user type"
                ), "type" => 503);

            $auth_type = DB::table('auth_type')->where('name', $request->user_type)->first();

            if($auth_type == null)
                return array("error" => array(
                    "error" => "please, provide valid type"
                ), "type" => 503);

            $user->auth_type_id = $auth_type->id;
            $user->auth_status_id = $auth_type->auth_status_id;

            if(!isset($decodet_JWT->name) && isset($request->name)){
                $splitName = explode(' ', $request->name, 2);
                $first_name = $splitName[0];
                $last_name = !empty($splitName[1]) ? $splitName[1] : '';

                $user->name = $request->name;
                $user->first_name = $first_name;
                $user->last_name = $last_name;
            }

            if(!isset($decodet_JWT->phone_number) && isset($request->phone))
            {
                $user->phone = $request->phone;
            }

            if(!isset($decodet_JWT->address) && isset($request->address))
            {
                $user->address = $request->address;
            }
        }

        if ($decodet_JWT->firebase->sign_in_provider === "phone") {
            $user->phone = $decodet_JWT->phone_number;
            $user->save();
            return;
        }

        $splitName = explode(' ', $decodet_JWT->name, 2);
        $first_name = $splitName[0];
        $last_name = !empty($splitName[1]) ? $splitName[1] : '';

        $user->name = $decodet_JWT->name;
        $user->first_name = $first_name;
        $user->last_name = $last_name;

        if ($decodet_JWT->firebase->sign_in_provider === "google.com" || $decodet_JWT->firebase->sign_in_provider === "facebook.com") {
            $user->email = $decodet_JWT->email;
            $user->photo = $decodet_JWT->picture;
        }
        else if ($decodet_JWT->firebase->sign_in_provider === "password")
            $user->email = $decodet_JWT->email;
        else if ($decodet_JWT->firebase->sign_in_provider === "twitter.com")
            $user->photo = $decodet_JWT->picture;

        $user->save();

        return $user;
    }

    private function createNewTokenUsingPassport($request, $user)
    {
        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->token;
        if ($request->remember_me)
            $token->expires_at = Carbon::now()->addWeeks(1);
        $token->save();

        return $tokenResult;
    }

    private function returnJsonResponseWithUserDataAndAccessToken($user, $tokenResult)
    {
        $user = User::where('uid', '=', $user->uid)->first();
        
        return response()->json([
            'user_data' => $user,
            'access_token' => $tokenResult->accessToken,
            'token_type' => 'Bearer',
            'expires_at' => Carbon::parse(
                $tokenResult->token->expires_at
            )->toDateTimeString()
        ], 200);
    }


    /**
     * Get the authenticated User
     *
     * @return [json] user object
     */
    public function user(Request $request)
    {
        return response()->json($request->user());
    }

}
