<?php

namespace JobMarket\Http\Controllers;

use JobMarket\Domain\AuthenticationService;
use JobMarket\Http\Request;
use JobMarket\Infrastructure\AuthenticationRepository;

class AuthenticationController extends Controller
{
    public function register(Request $request)
    {
        (new AuthenticationService(new AuthenticationRepository()))->register([
            "name"     => $request->postParams["name"],
            "email"    => $request->postParams["email"],
            "password" => $request->postParams["password"],
            "role"     => $request->postParams["role"]
        ]);

        return [
            "status" => "user registration successful"
        ];
    }

    public function login(Request $request)
    {
        $token = (new AuthenticationService(new AuthenticationRepository()))->login([
            $request->postParams["name"],
            $request->postParams["email"],
            $request->postParams["password"],
            $request->postParams["role"]
        ]);

        return [
            "token" => $token
        ];
    }

    public function logout(Request $request)
    {
        (new AuthenticationService(new AuthenticationRepository()))->logout($request->postParams["email"]);

        return [
            "message" => "user logged out."
        ];
    }
}
