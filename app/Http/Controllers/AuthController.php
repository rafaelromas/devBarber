<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class AuthController extends Controller
{
    //

    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['create', 'login', 'unauthorized']]);
    }

    public function create(Request $request)
    {
        $array = ['errors' => ''];

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if(!$validator->fails())
        {
            $name = $request->input('name');
            $email = $request->input('email');
            $password = $request->input('password');

            $emailExists = User::where('email', $email)->count();

            if($emailExists === 0)
            {
                $hash = password_hash($password, PASSWORD_DEFAULT);

                $newUser = new User;
                $newUser->name = $name;
                $newUser-> email = $email;
                $newUser->password = $hash;
                $newUser->save();

                $token = auth()->attempt([
                    'email' => $email,
                    'password' => $password
                ]);

                if(!$token)
                {
                    $array['errors'] = 'Ocorreu um erro!';
                    return $array;
                }

                $info = auth()->user();
                $info['avatar'] = url('media/avatars/'.$info['avatar']);
                $array['data'] = $info;
                $array['token'] = $token;

            } else{
                $array['errors'] = 'E-mail já cadastrado.';

                return $array;
            }

        }else{

            $array['errors'] = 'Dados incorretos.';

            return $array;

        }

        return $array;

    }


    public function login(Request $request)
    {
        $array = ['errors' => ''];

        $email = $request->input('email');
        $password = $request->input('password');
        
        $token = auth()->attempt([
            'email' => $email,
            'password' => $password
        ]);

        if(!$token)
        {
            $array['errors'] = "Usuário e/ou senha inválidos";
            return response()->json($array, 401);
        }

        $info = auth()->user();
        $info['avatar'] = url('media/avatars/'.$info['avatar']);
        $array['data'] = $info;
        $array['token'] = $token;

        return $array;
    }

    public function logout()
    {
        auth()->logout();
        return ['error' => ''];
    }

    public function refresh()
    {
        $array = ['error' => ''];
        $token = auth()->refresh(); 
        $array['token'] = $token;

        return $array;
    }

    public function unauthorized()
    {
        return response()->json(['error' => 'Não Autorizado'], 401);
    }
}
