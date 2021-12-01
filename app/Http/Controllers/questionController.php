<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\users;
use App\Models\question;
use App\Models\book;
use App\Models\likeRecordModel;
use App\Models\wrongRecord;
use App\Models\favouriteModel;

class questionController extends Controller
{
    function getBooks(Request $request){
        $books=book::all();
        $token=$request->input('token');
        $user=users::where('token',$token)->first();
        if(!$user){
            return ['errcode'=>-1,'msg'=>'user not found'];
        }
        $userArray=$user->toArray();
        foreach ($books as $b){
            $b->likes=likeRecordModel::where(['type'=>2,'object'=>$b->toArray()['id']])->get()->count()+100;
            $b->liked=likeRecordModel::where(['type'=>2,'object'=>$b->toArray()['id'],'user'=>$userArray['id']])->first()?1:0;
        }
        return $books;

    }

    function getQuestion(Request $request){
        $book=$request->input('book');
        $chapter=$request->input('chapter');
        $token=$request->input('token');
        $user=users::where('token',$token)->first();
        if(!$user){
            return ['errcode'=>-1,'msg'=>'user not found'];
        }
        $userArray=$user->toArray();
        if($book&&$chapter){
            $questions=question::where(['book'=>$book,'chapter'=>$chapter])->get();
            $result=array();
            foreach($questions as $q){
                $singleQuestion=question::getRawQuestion($q->id);
                $singleQuestion['favourite']=0;
                $fav=favouriteModel::where(['user'=>$userArray['id'],'question'=>$q->id])->cacheTags(["fav:{$userArray['id']}"])->first();
                if($fav){
                    $singleQuestion['favourite']=1;
                }

                array_push($result,$singleQuestion);

            }
            return ['errcode'=>0,'body'=>$result];
        }else{
            return ['errcode'=>-1,'msg'=>'Book and chapter id required'];
        }
    }

    function getSingleQuestion(Request $request){
        $id=$request->input('id');
        $question=question::where('id',$id)->get();
        if($question){
            return ['errcode'=>0,'body'=>$question];
        }else{
            return ['errcode'=>-1,'errmsg'=>'qid not found'];
        }
    }



    function getRandomQuestionFromBook(Request $request){
        $book=$request->input('book');
        $token=$request->input('token');
        $user=users::where('token',$token)->first();
        if(!$user){
            return ['errcode'=>-1,'msg'=>'user not found'];
        }
        $userArray=$user->toArray();
        if(!$book){
            return ['errcode'=>-1,'msg'=>'book id required'];
        }
        $amount=$request->input('amount')??15;
        if($amount>40){
            return ['errcode'=>-2,'msg'=>'too much'];
        }
        if($amount<=0){
            return ['errcode'=>'?','msg'=>'¿'];
        }
        $questions=question::where('book',$book)->get();
        $count=$questions->count();
        $questionArray=array();
        foreach($questions as $q){
            $singleQuestion=question::getRawQuestion($q->id);
            $singleQuestion['favourite']=0;
            $fav=favouriteModel::where(['user'=>$userArray['id'],'question'=>$q->id])->cacheTags(["fav:{$userArray['id']}"])->first();
            if($fav){
                $singleQuestion['favourite']=1;
            }

            array_push($questionArray,$singleQuestion);
        }
        $index=range(0,$count-1);
        //shuffle($index);

        $output=array();
        $questionsCount=0;
        while($questionsCount<$amount){
            array_push($output,$questionArray[array_rand($index)]);
            $questionsCount++;
        }



        return ['errcode'=>0,'body'=>$output];
    }

    function saveUserRecord(Request $request){
        //
        $token=$request->input('token');
        $qid=$request->input('qid')??0;
        $user=users::where('token',$token)->first();
        if(!$user){
            return ['errcode'=>-1,'msg'=>'user not found'];
        }
        $userArray=$user->toArray();

        $question=question::find($qid);
        if(!$question){
            return ['errcode'=>-1,'msg'=>'question not found'];
        }
        $record=wrongRecord::where(['user_id'=>$userArray['id'],'question_id'=>$qid])->first();
        if($record){
            $record->times+=1;
            $res=$record->save();
        }else{
            $newRecord=new wrongRecord();
            $newRecord->user_id=$userArray['id'];
            $newRecord->question_id=$qid;
            $newRecord->book=$question->book;
            $newRecord->times=1;
            $res=$newRecord->save();
        }
        if($res){
            return ['errcode'=>0,'msg'=>'Success'];
        }else{
            return ['errcode'=>-3,'msg'=>'DB fail'];
        }

    }

    function getMostWrongPersonal(Request $request){
        $token=$request->input('token');
        $user=users::where('token',$token)->first();
        $book=$request->input('book')??0;
        if(!$user){
            return ['errcode'=>-1,'msg'=>'user not found'];
        }
        $userArray=$user->toArray();
        $record=wrongRecord::where(['user_id'=>$userArray['id'],'book'=>$book])->orderBy('times','desc')->get();
        $questionCollect=array();
        foreach ($record as $r){
            $q=question::getRawQuestion($r->question_id);
            $q['favourite']=0;
            $fav=favouriteModel::where(['user'=>$userArray['id'],'question'=>$q['id']])->first();
            if($fav){
                $q['favourite']=1;
            }
            array_push($questionCollect,$q);
        }
            return ['errcode'=>0,'body'=>$questionCollect];
    }

    function getMostWrongGlobal(Request $request){
        $token=$request->input('token');
        $user=users::where('token',$token)->first();
        $book=$request->input('book')??0;
        if(!$user){
            return ['errcode'=>-1,'msg'=>'user not found'];
        }
        $userArray=$user->toArray();
        $record=wrongRecord::where(['book'=>$book])->orderBy('times','desc')->get();
        $questionCollect=array();
        foreach ($record as $r){
            $q=question::getRawQuestion($r->question_id);
            $q['favourite']=0;
            $fav=favouriteModel::where(['user'=>$userArray['id'],'question'=>$q['id']])->first();
            if($fav){
                $q['favourite']=1;
            }
            array_push($questionCollect,$q);
        }
        return ['errcode'=>0,'body'=>$questionCollect];
    }
}
