<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    public function categories()
    {
        return $this->belongsToMany('App\Category','category_question','question_id','category_id');
    }

    public function tags()
    {
        return $this->belongsToMany('App\Tag','tag_question','question_id','tag_id');
    }

    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function answers()
    {
        return $this->hasMany('App\Answer')->orderBy('created_at','ASC');
    }

    public function answersActive()
    {
        return $this->hasMany('App\Answer')->where('status',1)->orderBy('created_at','ASC');
    }



    public function visitors()
    {
        return $this->hasMany('App\Visitor');
    }


}
