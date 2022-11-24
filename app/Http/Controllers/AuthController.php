<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function __construct()
    {

        $this->middleware('auth:sanctum', ['except' => ['register', 'login', 'unauthorized']]);
    }

    public function register(Request $request)
    {
        $array = ['error' => ''];

        $data = $request->only([
            'username',
            'password'
        ]);

        $validator = Validator::make($data, [
            'username' => ['required', 'string', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'regex:/\w*$/', 'min:7'],
        ]);

        dd($data);

        if ($validator->fails()) {
            $array['error'] = $validator->getMessageBag();
            return $array;
        }

        $username = $request->input('username');
        $password = $request->input('password');

        $user = new User();
        $user->username = $username;
        $user->password = Hash::make($password);
        $user->save();

        $array['data'] = ['message' => 'Thank you! Your Account has been sucessfully created!'];
        $array['data'] = ['success' => true];

        return $array;
    }

    public function login(Request $request)
    {
        $array = ['error' => ''];

        $data = $request->only([
            'username',
            'password'
        ]);

        $validator = Validator::make($data, [
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'regex:/\w*$/', 'min:7'],
        ]);



        if ($validator->fails()) {
            $array['error'] = 'The username or password you entered did not macth our records. Please, try again.';
            return $array;
        }

        $username = $request->input('username');
        $password = $request->input('password');

        $access = Auth::attempt(
            ['username' => $username, 'password' => $password]
        );


        if (!$access) {
            $array['error'] = 'The username or password you entered did not macth our records. Please, try again.';
            return response()
                ->json($array, 401);
        }

        $user = User::find(Auth::id());

        $authToken = time() . rand(0, 9999);
        $token = $user->createToken($authToken)->plainTextToken;

        $array['data'] = ['message' => 'Welcome, ' . $user->username . '.'];
        $array['data'] = ['success' => true];
        $array['token'] = $token;
        $array['token_type'] = 'Bearer';

        return response()
            ->json($array);
    }

    public function logout()
    {
        $array = ['error' => ''];

        $true = Auth::user()->tokens->each(function ($token, $key) {
            $token->delete();
        });

        if ($true) {
            $array['data']  = ['message' => 'You\'ve been logged out'];
            $array['data'] = ['success' => true];
        }
        return response()
            ->json($array);
    }

    public function unauthorized()
    {
        $array = ['error' => 'Access denied due to invalid credentials.'];

        return response()
            ->json($array, 401);
    }
}
