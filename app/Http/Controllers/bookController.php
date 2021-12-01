<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use  App\Models\book;

class bookController extends Controller
{
    //
    function likeBook(Request $request){
        $id=$request->input('id');
        $book=book::find($id)->first();


    }
}
