<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    public function questions()
    {
        return $this->belongsToMany('App\Question','category_question','category_id','question_id');
    }

}
