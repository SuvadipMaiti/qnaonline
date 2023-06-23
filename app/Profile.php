<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{

    public  function user()
    {
        return $this->belongsTo('App\User');
    }


    public function getAvatarAttribute($avatar)
    {
        if($avatar){
            if(strpos($avatar, 'http') === 0) {
                return $avatar;
            }else{
                if(file_exists(public_path().'/upload/images/'.$avatar)){
                    return asset('upload/images/'.$avatar);
                }else{                    
                    return 'https://qnaonline.tech/panel/public/upload/images/'.$avatar;
                }
            }
        }else{
            return '';
        }
    }
 
}
