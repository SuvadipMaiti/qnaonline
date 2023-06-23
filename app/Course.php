<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    public function question()
    {
        return $this->belongsTo('App\Question')->orderBy('created_at','ASC');
    }
}
