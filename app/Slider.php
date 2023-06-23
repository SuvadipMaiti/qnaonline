<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Slider extends Model
{
    public function getImageAttribute($image)
    {
        if($image){
            if(strpos($image, 'http') === 0) {
                return $image;
            }else{
                if(file_exists(public_path().'/upload/images/{{ $avatar }}')){
                    return asset('upload/images/'.$image);
                }else{
                    return 'https://qnaonline.tech/panel/public/upload/images/'.$image;
                }
            }
        }else{
            return '';
        }    	
    }
}
