<?php

namespace App\Http\Controllers;

use App\Models\book;
use Illuminate\Http\Request;
use App\Models\users;
use App\Models\comment;
use App\Models\likeRecordModel;
use Illuminate\Support\Facades\DB;


class likeController extends Controller
{
    //
    function addLike(Request $request){
        $token=$request->input('token');
        $type=$request->input('type');
        $object=$request->input('object');
        if(!$token||!$type||!$object){
            return ['errcode'=>-1,'msg'=>'missing parameters'];
        }
        $user=users::where("token",$token)->get()->first();
        $userArray=$user->toArray();
        if(!$user->count()){
            return ['errcode'=>-2,'msg'=>'user not found'];
        }

        $likeRecord=likeRecordModel::where(['user'=>$userArray['id'],'type'=>$type,'object'=>$object])->first();
        if($likeRecord){
            return ['errcode'=>-4,'msg'=>'already liked'];
        }

        //comment like
        if($type==1){
            $comment=comment::find($object);
            if(!$comment){
                return ['errcode'=>-3,'msg'=>'comment not found'];
            }
            $comment->like+=1;
            $record=new likeRecordModel();
            $record->type=1;
            $record->user=$userArray['id'];
            $record->object=$object;

            DB::beginTransaction();
            try {
                $record->save();
                $res=$comment->save();
                DB::commit();
                return ['errcode'=>0,'msg'=>'Success'];
            } catch (\Exception $e) {
                DB::rollback();
                return ['errcode'=>-6,'msg'=>'DB Error'.$e];
            }



        }
        if($type==2){
            $book=book::find($object);
            if(!$book){
                return ['errcode'=>-3,'msg'=>'book not found'];
            }
            $record=new likeRecordModel();
            $record->type=2;
            $record->user=$userArray['id'];
            $record->object=$object;

            DB::beginTransaction();
            try {
                $record->save();
                DB::commit();
                return ['errcode'=>0,'msg'=>'Success'];
            } catch (\Exception $e) {
                DB::rollback();
                return ['errcode'=>-6,'msg'=>'DB Error'.$e];
            }
        }
    }
}
