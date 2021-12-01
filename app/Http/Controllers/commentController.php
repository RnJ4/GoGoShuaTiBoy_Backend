<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\comment;
use App\Models\users;
use App\Models\question;
use App\Models\bookCommentModel;
use App\Models\book;
use App\Models\likeRecordModel;


class commentController extends Controller
{
    function getAccessToken()
    {
        $token_file = 'token.json';
        if (is_file($token_file)) {
            $data = json_decode(file_get_contents($token_file));
        } else {
            $data = new \stdClass();
        }
        if (!isset($data->expire_time)||$data->expire_time < time()) {
            $curl = curl_init();
            $url='https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.env('WX_APPID').'&secret='.env('WX_APPSECRET');
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
            $res = json_decode($response);
            $access_token = $res->access_token;
            if ($access_token) {
                $data->expire_time = time() + 7000;
                $data->access_token = $access_token;
                file_put_contents($token_file, json_encode($data));
            }
        } else {
            $access_token = $data->access_token;
        }
        return $access_token;
    }


    function getComment(Request $request){
        $token=$request->input('token');
        $qid=$request->input('qid');
        $user=users::where("token",$token)->first();

        if(!$token||!$qid){
            return ['errcode'=>-1,'msg'=>'missing parameters'];
        }
        if(!$user){
            return ['errcode'=>-2,'msg'=>'user not found'];
        }
        $userArray=$user->toArray();
        $question=question::find($qid);
        if (!$question){
            return ['errcode'=>-3,'msg'=>'question not found'];
        }
        $comments=comment::join('users','users.id','=','comments.user_id')
            ->where(['question_id'=>$qid,'parent'=>0,'review'=>0,'deleted'=>0])
            ->select('users.nickname','users.avatar','comments.*')
            ->get();
        if ($comments->count()) {
            $output = array();
            foreach ($comments as $c) {
                $childComment = comment::join('users','users.id','=','comments.user_id')
                    ->where(['parent'=>$c->toArray()['id'],'review'=>0,'deleted'=>0])
                    ->select('users.nickname','users.avatar','comments.*')
                    ->limit(2)
                    ->get();
                if ($childComment->count()) {
                    $c->reply = $childComment;
                }
                $likeRecord=likeRecordModel::where(['user'=>$userArray['id'],'type'=>1,'object'=>$c->toArray()['id']])->first();
                if($likeRecord){
                    $c->liked=1;
                }else{
                    $c->liked=0;
                }

            }
        }
        return ['errcode'=>0,'body'=>$comments];

    }

    function getAllReply(Request $request){
        $token=$request->input('token');
        $rid=$request->input('rid');
        $user=users::where("token",$token)->first();
        if(!$token||!$rid){
            return ['errcode'=>-1,'msg'=>'missing parameters'];
        }
        if(!$user){
            return ['errcode'=>-2,'msg'=>'user not found'];
        }
        $parent=comment::find($rid);
        if(!$parent){
            return ['errcode'=>-3,'msg'=>'reply not found'];
        }
        $comments=comment::join('users','users.id','=','comments.user_id')
        ->where(['parent'=>$rid,'deleted'=>0,'review'=>0])
            ->select('users.nickname','users.avatar','comments.*')
            ->get();
        return ['errcode'=>0,'body'=>$comments];

    }


    function postComment(Request $request){

        $content=$request->input('comment');
        $token=$request->input('token');
        $qid=$request->input('qid');
        if(!$token||!$qid||mb_strlen($content)==0){
            return ['errcode'=>-1,'msg'=>'missing parameters'];
        }
        $user=users::where("token",$token)->first();

        if(!$user){
            return ['errcode'=>-2,'msg'=>'user not found'];
        }
        $now=time();
        $nextComment=$user->next_comment_at??0;
        if($now<$nextComment){
            return ['errcode'=>-5,'msg'=>"too frequent"];
        }
        $userArray=$user->toArray();
        $question=question::find($qid);
        if(!$question){
            return ['errcode'=>-3,'msg'=>'question not found'];
        }
        $openid=$user->openid;
        $accessToken=commentController::getAccessToken();



        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.weixin.qq.com/wxa/msg_sec_check?access_token='.$accessToken,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>'{
    "content":"'.$content.'",
    "version":2,
    "scene":2,
    "openid":"'.$openid.'"
}',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        $responseArray=json_decode($response,true);

        $comment=new comment;
        if($responseArray['result']['suggest']!="pass"){
            $comment->review=1;
            $needReview=1;
        }else{
            $comment->review=0;
            $needReview=0;
        }
        $comment->comment=$content;
        $comment->user_id=$userArray['id'];
        $comment->parent=$request->input('parent')??0;
        $comment->question_id=$qid;
        $res=$comment->save();
        if($res){
            $user->next_comment_at=time()+60;
            $user->save();
            if($needReview){
                return ['errcode'=>1,'msg'=>"need manual review"];
            }else{
                return ['errcode'=>0,'msg'=>'Success'];
            }
        }else{
            return ['errcode'=>-4,'msg'=>'DB error'];
        }




    }

    function postCommentBook(Request $request){
        $token=$request->input('token');
        $bookId=$request->input('book');
        $comment=$request->input('comment');
        if(!$token||!$bookId||!$comment){
            return ['errcode'=>-1,'msg'=>'missing parameters'];
        }
        $user=users::where("token",$token)->first();
        if(!$user){
            return ['errcode'=>-2,'msg'=>'user not found'];
        }

        $book=book::find($bookId);
        if(!$book){
            return ['errcode'=>-3,'msg'=>'book not found'];
        }
        $bookComment=new bookCommentModel();
        $bookComment->user_id=$user->toArray()['id'];
        $bookComment->book_id=$bookId;
        $bookComment->comment=$comment;
        $res=$bookComment->save();
        if($res){
            return ['errcode'=>0,'msg'=>"Success"];
        }else{
            return ['errcode'=>-4,'msg'=>"DB Error"];
        }
    }
}
