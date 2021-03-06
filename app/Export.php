<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Export extends Model
{
    //
    public function style(){
      return $this->belongsTo('App\Style', 'style_id')->with('basemap', 'image');
    }
    public function items(){
      return $this->hasMany('App\Exportitem')->with(['children','style', 'indicator','image', 'parent'])->orderBy('id');
    }
    public function image(){
      return $this->belongsTo('App\Image');
    }
}
