<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Item;
use App\Style;
use App\ItemType;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Auth;
use JWTAuth;
use Input;
use Tymon\JWTAuth\Exceptions\JWTException;


class ItemController extends Controller
{
    protected $countries;
    protected $calced = array();
    protected $sum = array();
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {

        $items = Item::where('parent_id', null)->with('children', 'type', 'style', 'categories')->orderBy('title');
        if(Input::has('is_official')){
          $items = $items->where('is_official', true);
        }
        $items = $items->get();
        return response()->api($items);
    }
    public function alphabethical(){
        return Item::orderBy('title', 'ASC')->get();
    }

    public function types(){
      return response()->api(ItemType::all());
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    protected function saveSubIndex($data, $parent){
      if(count($data) > 0){
        foreach($data as $entry){
          if(!array_key_exists('parent_id', $entry) && !array_key_exists('indicator_id', $entry)){
            $index = new Item();
            $index->title = $entry['title'];
            $index->name = str_slug($entry['title']);

            if(isset($entry['item_type_id'])){
              $index->item_type_id = $entry['item_type_id'];
              $index->indicator_id = isset($entry['indicator_id']) ? $entry['indicator_id'] : null;
              $index->style_id = $entry['style_id'];
            }
            else{
              $index->item_type_id = 4;
              $index->indicator_id = isset($entry['id']) ? $entry['id'] : null;
            }

            $index->parent_id = $parent->id;
            $index->user_id = Auth::user()->id;
            $index->save();

            if(isset($index['categories']) && isset($entry['item_type_id'])){
              foreach($index['categories'] as $cat){
                $index->categories()->attach($cat['id']);
              }
            }
          }
          else{
            $index = Item::find($entry['id']);
            $index->title = $entry['title'];
            $index->name = str_slug($entry['title']);

            if(isset($entry['item_type_id'])){
              $index->item_type_id = $entry['item_type_id'];
              $index->indicator_id = isset($entry['indicator_id']) ? $entry['indicator_id'] : null;
              $index->style_id = $entry['style_id'];
            }
            else{
              $index->item_type_id = 4;
              $index->indicator_id = $entry['id'];
            }

            $index->parent_id = $parent->id;
            $index->user_id = Auth::user()->id;
            $index->weight = $entry['weight'];
            $index->save();

          }
          if($index->id && isset($entry['children'])){
            $this->saveSubIndex($entry['children'], $index);
          }
        }
      }

    }

    public function create(Request $request)
    {
        //
        $index = new Item();
        $index->title = $request->input('title');
        $index->name = str_slug($request->input('title'));
        $index->indicator_id = $request->has('indicator_id') ? $request->input('indicator_id') : null;
        $index->item_type_id = $request->input('item_type_id');
        $index->parent_id = $request->has('parent_id') ? $request->input('parent_id') : null;
        $index->style_id = $request->input('style_id');
        $index->is_public = $request->has('is_public') ? $request->input('is_public') : false;
        $index->is_official = $request->has('is_official') ? $request->input('is_official') : false;
        $index->user_id = Auth::user()->id;
        $index->save();

        if($request->input('categories')){
          foreach($request->input('categories') as $cat){
            $index->categories()->attach($cat['id']);
          }
          $index->load('categories');
        }

        if($index->id){
          $this->saveSubIndex($request->input('children'), $index);
        }
        return response()->api($index);
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function store(Request $request)
    {
        //
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id){

       if(is_int($id)){
           return response()->api(Item::find($id));
       }
       elseif(is_string($id)){
         return response()->api(Item::where('name', $id)->first());
       }
       return false;
    }

    public function showMine(){

         //return response()->api(Item::where('parent_id', 0)->where('user_id', \Auth::user()->id)->with('children')->get());
         return response()->api(Item::where('parent_id', null)->with('type','categories', 'style', 'indicator','children')->get());


    }
    public function showWithChildren($id)
    {

        $index = array();
        if(is_numeric($id)){
            $index = Item::where('id',$id)->first();
            $index = $index->load('children', 'style', 'indicator', 'type', 'parent', 'categories');
        }
        elseif(is_string($id)){
          $index =  Item::where('name', $id)->first();
          $index->load('children', 'style', 'indicator', 'type', 'parent', 'categories');
        }
        //$index->style = $index->getStyle();
        return response()->api($index);
    }
    public function getLatestYear($index){
      if($index->indicator_id != null){
        return \DB::table($index->indicator->table_name)->max('year');
      }

    }


    public function showByIso($id, $iso){
      if(is_numeric($id)){
        $item = Item::findOrFail($id)->load('type', 'children');
      }
      elseif(is_string($id)){
        $item = Item::where('name', $id)->firstOrFail()->load('type', 'children');
      }

        $response = [
           'iso' => $iso,
           'data' => $this->calcValuesForStatistic($this->fetchDataForCountry($item, $iso))
        ];
        return response()->api($response);
    }
    public function showByYear($id, $year)
    {
        if(is_numeric($id)){
          $item = Item::find($id)->with('type','children');
        }
        elseif(is_string($id)){
          $item = Item::where('name', $id)->with('type','children')->firstOrFail();
        }

        $data =  $this->calcValues($this->fetchData($item, $year));
        return response()->api($data);
    }

    public function showLatestYear($id)
    {
        if(is_numeric($id)){
          $item = Item::findOrFail($id)->load('type', 'children');
        }
        elseif(is_string($id)){
          $item = Item::where('name', $id)->with('type', 'children')->firstOrFail()->load('type');
        }
        $year = $this->searchForYear($item);
        $data =  $this->calcValues($this->fetchData($item, $year));
        return response()->api($data);
    }
    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        //
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return Response
     */

    public function updateSubIndex($children, $item){
      if(count($children) > 0){
          $items = Item::where('parent_id', $item->id)->get();
          foreach($children as $child){
            if(!array_key_exists('parent_id') && !array_key_exists('measure_type_id')){
              $this->saveSubIndex($children, $item);
            }
          }
      }
      return false;
    }
    public function update(Request $request, $id)
    {
        //
        $cats = array();

        $item = Item::find($id);
        $item->title = $request->input('title');
        $item->name = str_slug($request->input('title'));
        $item->description = $request->input('description');
        $item->style_id = $request->input('style_id');
        $item->item_type_id = $request->input('type')['id'];
        $item->is_official = $request->input('is_official');
        $item->is_public = $request->input('is_public');
        if($request->has('categories')){
          foreach($request->input('categories') as $cat){
            $cats[] = $cat['id'];
          };
          $item->categories()->sync($cats);
        }
        $this->saveSubIndex($request->input('children'), $item);
        $item->save();
        return response()->api($item);
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function deleteChildren($item){
      if(count($item->children) > 0){
        foreach ($item->children as $key => $child) {
          if(count($child->children) > 0 ){
            $this->deleteChildren($child);
          }
          Item::find($child->id)->delete();
        }
      }
    }

    public function destroy($id)
    {
        //
        $item = Item::find($id);
        $this->deleteChildren($item);
        $item->delete();
        return response()->api($item);
    }




    /**
    *
    *   FOLLOWING FUNCTIONS ARE FOR CALCULATIONS
    *   SHOULD BE MOVED TO OWN PROVIDER
    */

    public function fetchData($index, $year){
        if(count($index->children) > 0){
          foreach($index->children as $key => &$item){
            $item = $this->fetchData($item, $year);
          }
        }
        if($index->indicator_id != null){
          $index->indicator->load('userdata');
          $iso_field = $index->indicator->userdata->iso_type == 'iso-3166-1' ? 'adm0_a3': 'iso_a2';
          $data = \DB::table($index->indicator->table_name)
            ->where('year', $year)
            ->leftJoin('countries', $index->indicator->table_name.".".$index->indicator->iso_name, '=', 'countries.'.$iso_field)
            ->select($index->indicator->table_name.".".$index->indicator->column_name.' as score', $index->indicator->table_name.".year",'countries.'.$iso_field.' as iso','countries.admin as country')
          //  ->select($index->indicator->table_name.".".$index->indicator->column_name.' as score', $index->indicator->table_name.".year",'countries.'.$iso_field.' as iso','countries.admin as country',\DB::raw('row_number() over (order by '.$index->indicator->column_name.' desc) as rank'))
            ->orderBy($index->indicator->table_name.".".$index->indicator->column_name, 'desc')->get();
          $index->data = $data;
        }

        return $index;
    }
    public function fetchDataForCountry($index, $iso){

      if(isset($index->children)){
        if(count($index->children)){
          foreach($index->children as $key => &$item){
            if($item->type->name != "group"){
              $item->load('indicator');
              $data = \DB::table($item->indicator->table_name)
                ->where($item->indicator->iso_name, $iso)
                // ->select($item->indicator->column_name.' as score', $item->indicator->table_name.".year", \DB::raw('row_number() over (order by '.$item->indicator->column_name.' desc) as rank'))
                ->select($item->indicator->column_name.' as score', $item->indicator->table_name.".year")
                ->orderBy('year', 'desc')->get();
              $item->data = $data;
            }
            if(count($item->children)){
                $item = $this->fetchDataForCountry($item, $iso);
            }
          }
        }
        else{
          $data = \DB::table($index->indicator->table_name)
            ->where($index->indicator->iso_name, $iso)
            // ->select($index->indicator->column_name.' as score', $index->indicator->table_name.".year", \DB::raw('row_number() over (order by '.$index->indicator->column_name.' desc) as rank'))
            ->select($index->indicator->column_name.' as score', $index->indicator->table_name.".year")
            ->orderBy('year', 'desc')->get();
          $index->data = $data;
        }
      }
      return $index;
    }
    public function calcAverage($item, $length, $name){

      foreach ($item as $key => &$country) {
        $calc[$name]['value'] = 0;
        foreach ($country as $k => $index) {
          if(isset($index['calc'])){
              if($index['calc']){
                $calc[$name]['value'] += floatval($index['value']);
              }
          }
          $country[$name]['year'] = $index['year'];
        }

        $country[$name]['value'] = $calc[$name]['value']/$length;
      }
      return $item;
    }
    public function averageDataForCountry($item){
      $sum = array();
      if(count($item['children'])){
        foreach($item['children'] as $child){
          if($child->type->name != 'group'){
              foreach($child['data'] as $data){
                if(!isset($sum[$data->year][$child->name])){
                  $sum[$data->year][$child->name]['value'] = 0;
                  $sum[$data->year][$child->name]['year'] = $data->year;
                  $sum[$data->year][$child->name]['calc'] = true;
                  // $sum[$data->year][$child->name]['rank'] = $data->rank;
                }
                $sum[$data->year][$child->name]['value'] += $data->score;
              }
          }
          else{
              $sub = $this->averageDataForCountry($child);
              $su = $this->calcAverage($sub, $this->fieldCount($sub), $child->name);
              foreach($su as $key => &$s){
                foreach($s as $k => &$dat){
                  $sum[$key][$k]['value'] = $dat['value'];
                  $sum[$key][$k]['year'] = $dat['year'];
                  $sum[$key][$k]['calc'] = false;
                  if($k == $child->name){
                    $sum[$key][$k]['calc'] = true;
                  }
                }
              }
          }
        }
      }
      else{
        foreach($item['data'] as $data){
          if(!isset($sum[$data->year][$item->name])){
            $sum[$data->year][$item->name]['value'] = 0;
            $sum[$data->year][$item->name]['year'] = $data->year;
            $sum[$data->year][$item->name]['calc'] = true;
            // $sum[$data->year][$item->name]['rank'] = $data->rank;
          }
          $sum[$data->year][$item->name]['value'] += $data->score;
        }
      }
      return $sum;
    }

    public function rawData($item){
      foreach($item->data as $data){
        $this->sum[$data->iso][$item->name]['value'] = $data->score;
        $this->sum[$data->iso][$item->name]['year'] = $data->year;
        $this->sum[$data->iso][$item->name]['calc'] = false;
        // $this->sum[$data->iso][$item->name]['rank'] = $data->rank;
      }
      foreach($item->children as $child){
          $this->rawData($child);
      }

    }
    public function averageData($item){
      $sum = array();

      if(count($item['children']) > 0){
        foreach ($item['children'] as $child) {
          if($child->type->name == "index"){
            if(isset($child->data)){
              foreach($child->data as $data){
                if(!isset($sum[$data->iso][$child->name])){
                  $sum[$data->iso][$child->name]['value'] = 0;
                  $sum[$data->iso][$child->name]['year'] = $data->year;
                  if($item->type->name == "group"){
                      $sum[$data->iso][$child->name]['calc'] = true;
                  }
                  else{
                    $sum[$data->iso][$child->name]['calc'] = false;
                  }
                }
                $sum[$data->iso][$child->name]['value'] += $data->score;
              }
            }
          }
          else{
            $soup = $this->averageData($child);
            $sub = $this->calcAverage($soup, $this->fieldCount($soup), $child->name);

            foreach($sub as $iso => &$s){
              foreach($s as $key => &$data){
                if(!isset($sum[$iso][$key])){
                  $sum[$iso][$key]['value'] = $data['value'];
                  $sum[$iso][$key]['year'] = $data['year'];
                  if($key == $child->name){
                    $sum[$iso][$key]['calc'] = true;
                  }
                  else{
                    $sum[$iso][$key]['calc'] = false;
                  }
                }
              }
            }
          }
        }
      }
      else{
        foreach($item->data as $data){
          if(!isset($sum[$data->iso][$item->name])){
              $sum[$data->iso][$item->name]['value'] = 0;
              $sum[$data->iso][$item->name]['year'] = $data->year;
              $sum[$data->iso][$item->name]['calc'] = true;
          }
          $sum[$data->iso][$item->name]['value'] += $data->score;
        }
      }
      return $sum;
    }

    public function fieldCount($items){
      $fields = array();

      foreach($items as $key => $item) {

        foreach($item as $k => $i){

          if($i['calc'] && !isset($fields[$k])){

            $fields[$k] = true;
          }
        }

      }
      return count($fields);
    }
    public function calcValuesForStatistic($index){
      $scores = $this->averageDataForCountry($index);
      $data = array();
      $scores = $this->calcAverage($scores, $this->fieldCount($scores), $index->name);
      foreach ($scores as $key => $value) {
        foreach($value as $k => $column){
          $entry[$k] = $column['value'];
        }
        $entry['year']  = $key;
        $data[] = $entry;
      }
      return $data;
    }
    public function calcValues($index){
      $data = array();
      if($index->type->name == "group"){
        $score = $this->averageData($index);
        $score = $this->calcAverage($score, $this->fieldCount($score), $index->name);
      }
      else if($index->type->name == "index"){
        $this->rawData($index);
        $score = $this->sum;
      }
      else{
        $score = $this->averageData($index);
      }
      $rank = 1;
      foreach ($score as $key => $value) {
        $entry = [
          'iso' => $key,
          'rank' => $rank
        ];
        foreach($value as $k => $column){
          $entry[$k] = $column['value'];
        }
        $rank++;
        $data[] = $entry;
      }
      return $data;
    }
    public function searchForYear($data){

      if($data->type->name == 'group'){
        foreach($data->children as $child){
          if($child->type->name != 'group'){
            $year = $this->getLatestYear($child);
          }
          else{
            $year = $this->searchForYear($child);
          }
        }
      }
      else{
        $year = $this->getLatestYear($data);
      }
      return $year;
    }
}
