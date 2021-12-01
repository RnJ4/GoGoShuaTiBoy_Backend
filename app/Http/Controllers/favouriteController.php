<?php

namespace App\Http\Controllers;
use App\Models\favouriteModel;
use App\Models\users;
use App\Models\question;
use Illuminate\Http\Request;

class favouriteController extends Controller
{
    function addFavourite(Request $request){
        $token=$request->input('token');
        $id=$request->input('id');
        $user=users::where('token',$token)->get()->first()->toArray();
        if(!$user||!$id){
            return ['errcode'=>-1,'errmsg'=>"invalid parameters"];
        }
        $question=question::find($id);
        if(!$question){
            return ['errcode'=>-2,'errmsg'=>"question not found"];
        }
        $fav=favouriteModel::where('question',$id)
            ->where('user',$user['id'])
            ->get();
        if($fav->count()){
            return ['errcode'=>-3,'errmsg'=>"already favourite"];
        }
        $newFav=new favouriteModel;
        $newFav->question=$id;
        $newFav->user=$user['id'];
        $res=$newFav->save();
        if($res){
            return ['errcode'=>0,'errmsg'=>'Success'];
        }else{
            return ['errcode'=>-4,'errmsg'=>"DB fail"];
        }
    }

    function getFavouritesAll(Request $request){
        $token=$request->input('token');
        $user=users::where('token',$token)->get()->first();
        if(!$user){
            return ['errcode'=>-1,'errmsg'=>"no such user"];
        }
        $user=$user->toArray();
        $favs=favouriteModel::where('user',$user['id'])->cacheTags(["fav:{$user['id']}"])->get();
        $output=array();
        foreach($favs as $f){
            $q=question::getRawQuestion($f->question);
            $q['favourite']=1;
            array_push($output, $q);
        }
        return ['errcode'=>0,'body'=>$output];
    }

    function removeFavourite(Request $request){
        $token=$request->input('token');
        $qid=$request->input('id');
        $user=users::where('token',$token)->get()->first();
        if(!$user){
            return ['errcode'=>-1,'errmsg'=>"no such user"];
        }
        $user=$user->toArray();
        $res=favouriteModel::where('question',$qid)
            ->where('user',$user['id'])
            ->delete();
        favouriteModel::flushQueryCache(["fav:{$user['id']}"]);
        if($res){
            return ['errcode'=>0,'errmsg'=>'Success'];
        }else{
            return ['errcode'=>-1,'errmsg'=>'Fail'];
        }
    }
}
