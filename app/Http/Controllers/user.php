<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\users;

class user extends Controller
{
    function uuid()
    {
        $chars = md5(uniqid(mt_rand(), true));
        $uuid = substr ( $chars, 0, 8 ) . '-'
            . substr ( $chars, 8, 4 ) . '-'
            . substr ( $chars, 12, 4 ) . '-'
            . substr ( $chars, 16, 4 ) . '-'
            . substr ( $chars, 20, 12 );
        return $uuid ;
    }

    function wxLogin(Request $request){
        $code=$request->input('code');
        $appid=env('WX_APPID');
        $appsecret=env('WX_APPSECRET');
        $curl = curl_init();
        $url = 'https://api.weixin.qq.com/sns/jscode2session?appid='.$appid.'&secret='.$appsecret.'&js_code='.$code;
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        $json = new \stdClass();
        $responseArray = json_decode($response,true);

        if(isset($responseArray['openid'])){
            $user=users::where('openid',$responseArray['openid'])->first();
            if($user){
                if($user->ban==1){
                    return ['errcode'=>-999,'msg'=>'user banned'];
                }
                if(isset($user->token)){
                    $json->token=$user->token;

                }else{
                    $token=user::uuid();
                    $user->token=$token;
                    $user->save();
                    $json->token=$token;
                }
                $json->errcode=0;
            }else{
                $newUser=new users;
                $token=user::uuid();
                $newUser->token=$token;
                $newUser->openid=$responseArray['openid'];
                $newUser->save();
                $json->token=$token;

            }


        }else{
            $json->errcode=-1;$json->errmsg=$responseArray['errmsg'];
        }
        return $json;

    }

    function getUser(Request $request){
        $token=$request->input('token');
        if(!$token){
            return ['errcode'=>-2,'msg'=>'user token required'];;
        }
        $user=users::where('token',$token)->first();
        if($user){
            return ['errcode'=>0,'data'=>$user];
        }else{
            return ['errcode'=>-1,'msg'=>'user not found'];
        }
    }

    function syncUserInfo(Request $request){
        $token=$request->input('token');
        $nickname=$request->input('nickname');
        $avatar=$request->input('avatar');
        $user=users::where('token',$token)->first();
        if(!$user){
            return ['errcode'=>-1,'msg'=>'user not found'];
        }else{
            $user->nickName=$nickname;
            $user->avatar=$avatar;
            $res=$user->save();
            if($res){
                return ['errcode'=>0,'msg'=>'OK'];
            }else{
                return ['errcode'=>-2,'msg'=>'DB Error '];
            }

        }
    }

}
