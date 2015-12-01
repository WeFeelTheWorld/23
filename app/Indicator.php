<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Indicator extends Model
{
    //
    protected $table = "23_indicators";

    public function categories(){
      return $this->belongsToMany('App\Categories', 'indicator_categories', 'indicator_id', 'categorie_id');
    }
    public function userdata(){
      return $this->belongsTo('App\UserData');
    }
    public function type(){
      return $this->belongsTo('App\MeasureType', 'measure_type_id');
    }
    public function style(){
      return $this->belongsTo('App\Style', 'style_id');
    }

}
