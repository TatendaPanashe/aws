<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash; 
use Session;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Auth;


class SpinController extends Controller
{
 public function index()
 {
     return view('spinandwin.index');
 }
}