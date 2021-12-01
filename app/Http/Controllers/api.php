<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use app\Models\users;

class api extends Controller
{


    function getUserInfo(Request $request){
        $openID=$request->input("openid");
        $user =users::where('openid',$openID)->get();
        if($user){
            return $user;
        }
        else{
            return ['errcode'=>0,'msg'=>'not found'];
        }
    }
    //
}
