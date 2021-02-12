<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;

class LoginController extends Controller
{
    public function showLogin()
    {
        return view('auth.login')->extend('auth.layout');
    }
}
