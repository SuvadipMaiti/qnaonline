<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    public function questions()
    {
        return $this->belongsToMany('App\Question','tag_question','tag_id','question_id');
    }
}
