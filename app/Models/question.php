<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Rennokki\QueryCache\Traits\QueryCacheable;


class question extends Model
{
    use HasFactory;
    protected $table = 'question';
    use QueryCacheable;
    public $cacheFor = 7200;
    public $cacheTags = ['questions'];
    public $cacheDriver = 'redis';

    static function getRawQuestion(int $id){
        $q=question::find($id);
        if($q){
            $singleQuestion=array();
            $selections=array();
            $singleQuestion['id']=$q->id;
            $singleQuestion['book']=$q->book;
            $singleQuestion['chapter']=$q->chapter;
            $singleQuestion['type']=$q->type;
            array_push($selections,$q->selectionA,$q->selectionB);
            if(isset($q->selectionC)){
                array_push($selections,$q->selectionC);
                if(isset($q->selectionD)){
                    array_push($selections,$q->selectionD);
                    if(isset($q->selectionOther)){
                        array_push($selections,$q->selectionOther);
                    }
                }
            }
            $singleQuestion['favourite']=
            $singleQuestion['content']=$q->content;
            $singleQuestion['selection']=$selections;
            $singleQuestion['answer']=$q->answer;
            return $singleQuestion;
        }else{
            return -1;
        }
    }
}
