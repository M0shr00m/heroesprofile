<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Session;

class HeroController extends Controller
{
  protected $data;

  private $session_data;

  private $timeframe_type = "";
  private $timeframe = array();
  private $game_type = array();
  private $league_tier = array();
  private $game_map =  array();
  private $hero_level = array();
  private $hero = "";
  private $stat = "";
  private $role = "";
  private $build_type = "Popular";
  private $talent_levels = ["level_one", "level_four", "level_seven", "level_ten", "level_thirteen", "level_sixteen", "level_twenty"];
/*private function convertSessionToArray($sessionData){
  $returnSession = [];
  foreach($sessionData as $key => $value){
    array_push($returnSession, ["name" : $key, "value" : $value]);

  }
}*/


  public function show()
  {
    $global = \GlobalFunctions::instance();


// Add code here to get markdown file for the site content.
/*  $termsFile = file_exists(base_path('terms.'.app()->getLocale().'.md'))
    ? base_path('privacy-policy.'.app()->getLocale().'.md')
    : base_path('privacy-policy.md');
*/
    return view('table', [
      'dataurl' => '/get_heroes_stats_table_data', // URL used for calling the table data
      'title' => 'Global Win Rates', // Page title
      'paragraph' => 'Hero win rates based on differing increments, stat types, game type, or league tier. Click on a Hero to see detailed talent information.', // Summary paragraph
      'tableheading' => 'Win Rates', // Table heading
      'rawfields' => [
        /*"timeframe" => session('all_major_patch'),
      /*  "timeframe2" => $this->convertSessionToArray(session('all_minor_patch')),
        "hero_level" => $this->convertSessionToArray(session('hero_levels')),
        "game_map" => $this->convertSessionToArray(session('maps_by_id')),
      */

      "timeframe_type" => array(
          [
            "key" => "major",
            "value" => "major"
          ],
          [
            "key" => "minor",
            "value" => "minor",
          ]
        ),


      "major_patch" =>  $global->convertToFilter(Session::get('all_major_patch')),  //conditional on whether timeframe type is equal to major
      "minor_patch" =>  $global->convertToFilter(Session::get('all_minor_patch')),  //conditional on whether timeframe type is equal to minor
      "game_type" => array(
          [
            "key" => "Quick Match",
            "value" => 1
          ],
          [
            "key" => "Unranked Draft",
            "value" => 2,
          ],
          [
            "key" => "Storm League",
            "value" => 5,
          ],
          [
            "key" => "Brawl",
            "value" => -1,
          ]
        ),
      "game_map" => session('maps_by_name_filter_format'),
      "league_tier" => $global->convertToFilter($global->getLeagueTiersByName()),

      "type" => $global->convertToFilter(array_flip(Session::get('stat_columns'))),
      "hero_level" => $global->convertToFilter($global->getHerolevels()),
      "role" => $global->convertToFilter(Session::get('role_names')),
      "hero" => $global->convertToFilter(Session::get('heroes_by_name')),


      //"timeframe_type" =>  '[{"key": "Major", "value": "major"}, { "key": "Minor", "value": "minor"}]'   /// this is not a multi select

      ],
    ]);
  }



  public function profile(){
    return view('hero/profile');
  }

  public function getFields(Request $request){
    $this->session_data = $request->session()->all();
    $this->session_data = json_decode(json_encode($this->session_data),true);

    $return_data = array();

    $heroes = array();
    //these should be retreived from session variables - set those in the main controller that this one is extended from.
    //Session::set('variableName', $value);
    //Session::get('variableName');

    $hero_array = $this->session_data["heroes_by_name"];
    $hero_names = array_keys($hero_array);
    $hero_ids = array_values($hero_array);


    $heroes["options"] = $hero_names;
    $heroes["inputtype"] = "select";
    $heroes["multiselect"] = false;
    $heroes["inputname"] = "Hero";

    $game_map_array = $this->session_data["maps_by_name"];
    $map_names = array_keys($game_map_array);
    $map_ids = array_values($game_map_array);

    $maps = array();
    $maps["options"] = $map_names;
    $maps["inputtype"] = "select";
    $maps["multiselect"] = false;
    $maps["inputname"] = "Map";
    /* $maps = new stdClass();

    $heroes->names = ["Battlefield of Eternity","Other Map"];
    $hereos->inputtype = "select";
    $heroes->multiselect = false;*/

    array_push($return_data, $heroes);
    array_push($return_data, $maps);
    return json_encode($return_data);

  }

  public function getHeroStatsTableData(Request $request){
    $this->session_data = $request->session()->all();
    $this->session_data = json_decode(json_encode($this->session_data),true);

    $maps = $this->session_data["maps_by_name"];

    if(isset($request["timeframe_type"])){
      $this->timeframe_type = $request["timeframe_type"];
    }else{
      $this->timeframe_type = "major";
    }

    if(isset($request["timeframe"])){
      $this->timeframe = array($request["timeframe"]);
    }else{
      $this->timeframe = array($this->session_data["major_patch"]);
    }

    if(isset($request["game_type"]) && $request["game_type"] != ""){
      $this->game_type = array($request["game_type"]);
    }else{
      $this->game_type = array($this->session_data["default_game_mode_id"]);
    }

    if(isset($request["league_tier"]) && $request["league_tier"] != ""){
      $this->league_tier =  explode(',', $request["league_tier"]);
    }

    if(isset($request["game_map"]) && $request["game_map"] != ""){
      $this->game_map =  explode(',', $request["game_map"]);

      for($i = 0; $i < count($this->game_map); $i++){
        $this->game_map[$i] = $maps[$this->game_map[$i]];
      }

    }

    if(isset($request["hero_level"]) && $request["hero_level"] != ""){
      $this->hero_level = array($request["hero_level"]);
    }

    if(isset($request["hero"]) && $request["hero"] != ""){
      $this->hero = $request["hero"];
    }

    if(isset($request["stat"]) && $request["stat"] != ""){
      $this->stat = $request["stat"];
    }

    if(isset($request["role"]) && $request["role"] != ""){
      $this->role = $request["role"];
    }

    $query = DB::table('heroesprofile.global_hero_stats');


    if(count($this->timeframe) != 0){
      if($this->timeframe_type == "major"){
        $patches_array = array();
        for($i = 0; $i < count($this->timeframe); $i++){
          if(count($patches_array) == 0){
            $patches_array = $this->session_data['major_to_minor_patch_mapping'][$this->timeframe[$i]];
          }else{
            array_merge($patches_array, $this->session_data['major_to_minor_patch_mapping'][$this->timeframe[$i]]);
          }
        }
        $query->whereIn('game_version', $patches_array);
      }else{
        $query->whereIn('game_version', $this->timeframe);
      }
    }
    if(count($this->game_type) != 0){
      $query->whereIn('game_type', $this->game_type);
    }

    if(count($this->league_tier) != 0){
      $query->whereIn('league_tier', $this->league_tier);
    }

    if(count($this->game_map) != 0){
      $query->whereIn('game_map', $this->game_map);
    }

    if(count($this->hero_level) != 0){
      $query->whereIn('hero_level', $this->hero_level);
    }

    if($this->hero != ""){
      $query->whereIn('hero', $this->hero);
    }

    $query->join('heroes', 'heroes.id', '=', 'global_hero_stats.hero');
    if($this->stat != ""){
      $query->select('heroes.name', 'global_hero_stats.win_loss', DB::raw('SUM(games_played) as games_played'), DB::raw('SUM(' . $this->stat . ') as total_' . $this->stat ));
    }else{
      $query->select('heroes.name', 'global_hero_stats.win_loss', DB::raw('SUM(games_played) as games_played'));
    }
    $query->groupBy('hero', 'win_loss');
    $data = $query->get();
    //print_r($query->toSql());
    //print_r($query->getBindings());

    $data = json_decode(json_encode($data),true);

    $return_data = array();
    $counter = 0;
    $prev_name = "";

    $total_games = 0;
    for($i = 0; $i < count($data); $i++){
      if($this->role != ""){
        if($roles[$data[$i]["name"]] != $this->role){
          continue;
        }
      }
      if($prev_name != "" && $prev_name != $data[$i]["name"]){
        $counter++;
      }

    //  $return_data[$counter]["name"] = $data[$i]["name"];

      $return_data[$counter]["name"]["hero_name"] = $data[$i]["name"];
      $return_data[$counter]["name"]["short_name"] = $this->session_data["heroes_name_to_short"][$data[$i]["name"]];


      if(!array_key_exists("games_played",$return_data[$counter])){
        $return_data[$counter]["games_played"] = $data[$i]["games_played"];
      }else{
        $return_data[$counter]["games_played"] += $data[$i]["games_played"];
      }

      if($data[$i]["win_loss"] == 1){
        $return_data[$counter]["wins"] = floatval($data[$i]["games_played"]);
      }else{
        $return_data[$counter]["losses"] = floatval($data[$i]["games_played"]);
      }

      $prev_name = $data[$i]["name"];
      $total_games += $data[$i]["games_played"];
    }


    $query = DB::table('heroesprofile.global_hero_stats_bans');

    if(count($this->timeframe) != 0){
      if($this->timeframe_type == "major"){
        $patches_array = array();
        for($i = 0; $i < count($this->timeframe); $i++){
          if(count($patches_array) == 0){
            $patches_array = $this->session_data['major_to_minor_patch_mapping'][$this->timeframe[$i]];
          }else{
            array_merge($patches_array, $this->session_data['major_to_minor_patch_mapping'][$this->timeframe[$i]]);
          }
        }
        $query->whereIn('game_version', $patches_array);
      }else{
        $query->whereIn('game_version', $this->timeframe);
      }
    }
    if(count($this->game_type) != 0){
      $query->whereIn('game_type', $this->game_type);
    }

    if(count($this->league_tier) != 0){
      $query->whereIn('league_tier', $this->league_tier);
    }

    if(count($this->game_map) != 0){
      $query->whereIn('game_map', $this->game_map);
    }

    if(count($this->hero_level) != 0){
      $query->whereIn('hero_level', $this->hero_level);
    }


    if($this->hero != ""){
      $query->whereIn('hero', $this->hero);
    }

    $query->join('heroes', 'heroes.id', '=', 'global_hero_stats_bans.hero')
    ->select('heroes.name', DB::raw('SUM(bans) as bans'))
    ->groupBy('hero');
    $data = $query->get();
    $data = json_decode(json_encode($data),true);

    $ban_data = array();
    $total_ban_games = 0;
    for($i = 0; $i < count($data); $i++){
      $ban_data[$data[$i]["name"]] = $data[$i]["bans"];
    }


    for($i = 0; $i < count($return_data); $i++){
      if(!array_key_exists("wins", $return_data[$i])){
        $return_data[$i]["wins"] = 0;
      }

      if(!array_key_exists("losses", $return_data[$i])){
        $return_data[$i]["losses"] = 0;
      }

      if($return_data[$i]["wins"] == 0){
          $return_data[$i]["win_rate"] = 0;
      }else if($return_data[$i]["losses"] == 0){
        $return_data[$i]["win_rate"] = 100;
      }else{
        $return_data[$i]["win_rate"] = round(($return_data[$i]["wins"] / ($return_data[$i]["wins"] + $return_data[$i]["losses"])) * 100, 2);
      }


      if(!array_key_exists($return_data[$i]["name"]["hero_name"], $ban_data)){
        $return_data[$i]["bans"] = 0;
        $return_data[$i]["ban_rate"] = 0;
        $return_data[$i]["popularity"] = round(($return_data[$i]["games_played"] / ($total_games / 10)) * 100, 2);

      }else{
        $return_data[$i]["bans"] = floatval($ban_data[$return_data[$i]["name"]["hero_name"]]);
        $return_data[$i]["ban_rate"] = round(($return_data[$i]["bans"] / ($total_games / 10)) * 100, 2);
        $return_data[$i]["popularity"] = round((($return_data[$i]["bans"] + $return_data[$i]["games_played"]) / ($total_games / 10)) * 100, 2);

      }
    }

    return $return_data;
  }

  public function getHeroTalentsTableData(Request $request){
  }

  public function getHeroBuildsTableData(Request $request){
    $most_played_builds = $this->getTopFiveBuilds($request);


    for($i = 0; $i < count($most_played_builds); $i++){
      $most_played_builds[$i]["wins"] = 0;
      $most_played_builds[$i]["losses"] = 0;

      $most_played_builds[$i]["wins"] += $this->getWinLoss(1, "wins", $most_played_builds[$i], 10);
      $most_played_builds[$i]["wins"] += $this->getWinLoss(1, "wins", $most_played_builds[$i], 13);
      $most_played_builds[$i]["wins"] += $this->getWinLoss(1, "wins", $most_played_builds[$i], 16);
      $most_played_builds[$i]["wins"] += $this->getWinLoss(1, "wins", $most_played_builds[$i], 20);


      $most_played_builds[$i]["losses"] += $this->getWinLoss(0, "losses", $most_played_builds[$i], 10);
      $most_played_builds[$i]["losses"] += $this->getWinLoss(0, "losses", $most_played_builds[$i], 13);
      $most_played_builds[$i]["losses"] += $this->getWinLoss(0, "losses", $most_played_builds[$i], 16);
      $most_played_builds[$i]["losses"] += $this->getWinLoss(0, "losses", $most_played_builds[$i], 20);

    }

    for($i = 0; $i < count($most_played_builds); $i++){
      if($most_played_builds[$i]["wins"] == 0 && $most_played_builds[$i]["losses"] == 0){
        $most_played_builds[$i]["wins"] = 0;
        $most_played_builds[$i]["losses"] = 0;
        $most_played_builds[$i]["win_rate"] = 0;
      }else if($most_played_builds[$i]["wins"] != 0 && $most_played_builds[$i]["losses"] == 0){
        $most_played_builds[$i]["losses"] = 0;
        $most_played_builds[$i]["win_rate"] = 100;
      }else if($most_played_builds[$i]["wins"] != 0 && $most_played_builds[$i]["losses"] != 0){
        $most_played_builds[$i]["win_rate"] = ($most_played_builds[$i]["wins"] / ($most_played_builds[$i]["wins"] + $most_played_builds[$i]["losses"])) * 100;
      }
    }

    usort($most_played_builds, [$this, 'custom_win_rate_sort']);

    for($i = 0; $i < count($most_played_builds); $i++){
      foreach($this->talent_levels as $key=>$level){
        $most_played_builds[$i]['talents'][$key]['level'] = $level;
        $most_played_builds[$i]['talents'][$key]['id'] = $most_played_builds[$i][$level];
        $most_played_builds[$i]['talents'][$key]['name'] = $this->session_data['talent_data'][$most_played_builds[$i][$level]]['title'];
        $most_played_builds[$i]['talents'][$key]['icon'] = $this->session_data['talent_data'][$most_played_builds[$i][$level]]['icon'];
        $most_played_builds[$i]['talents'][$key]['description'] = $this->session_data['talent_data'][$most_played_builds[$i][$level]]['description'];
      }





    /*  $most_played_builds[$i]['talents']['level_four'] = $most_played_builds[$i]['level_four'];
      $most_played_builds[$i]['talents']['level_seven'] = $most_played_builds[$i]['level_seven'];
      $most_played_builds[$i]['talents']['level_ten'] = $most_played_builds[$i]['level_ten'];
      $most_played_builds[$i]['talents']['level_thirteen'] = $most_played_builds[$i]['level_thirteen'];
      $most_played_builds[$i]['talents']['level_sixteen'] = $most_played_builds[$i]['level_sixteen'];
      $most_played_builds[$i]['talents']['level_twenty'] = $most_played_builds[$i]['level_twenty'];*/

    }

    return $most_played_builds;
  }

  function custom_win_rate_sort( $a, $b ) {
    if($a["win_rate"] ==  $b["win_rate"] ){ return 0 ; }
    return ($a["win_rate"] > $b["win_rate"]) ? -1 : 1;
  }

  private function getTopFiveBuilds(Request $request){
    $this->session_data = $request->session()->all();
    $this->session_data = json_decode(json_encode($this->session_data),true);

    $maps = $this->session_data["maps_by_name"];
    $hero_array = $this->session_data["heroes_by_name"];

    if(isset($request["timeframe_type"])){
      $this->timeframe_type = $request["timeframe_type"];
    }else{
      $this->timeframe_type = "major";
    }

    if(isset($request["timeframe"])){
      $this->timeframe = array($request["timeframe"]);
    }else{
      $this->timeframe = array($this->session_data["major_patch"]);
    }

    if(isset($request["game_type"]) && $request["game_type"] != ""){
      $this->game_type = array($request["game_type"]);
    }else{
      $this->game_type = array($this->session_data["default_game_mode_id"]);
    }

    if(isset($request["league_tier"]) && $request["league_tier"] != ""){
      $this->league_tier = array($request["league_tier"]);
    }

    if(isset($request["game_map"]) && $request["game_map"] != ""){
      $this->game_map =  array($request["game_map"]);
      for($i = 0; $i < count($this->game_map); $i++){
        $this->game_map[$i] = $maps[$this->game_map[$i]];
      }
    }

    if(isset($request["hero_level"]) && $request["hero_level"] != ""){
      $this->hero_level = array($request["hero_level"]);
    }

    if(isset($request["hero"]) && $request["hero"] != ""){
      $this->hero = $request["hero"];
    }

    if(isset($request["build_type"]) && $request["build_type"] != ""){
      $this->build_type = $request["build_type"];
    }
    /* Testing */
    $this->build_type = "Popular";    // For Testing

    $query = DB::table('heroesprofile.global_hero_talents');
    if(count($this->timeframe) != 0){
      if($this->timeframe_type == "major"){
        $patches_array = array();
        for($i = 0; $i < count($this->timeframe); $i++){
          if(count($patches_array) == 0){
            $patches_array = $this->session_data['major_to_minor_patch_mapping'][$this->timeframe[$i]];
          }else{
            array_merge($patches_array, $this->session_data['major_to_minor_patch_mapping'][$this->timeframe[$i]]);
          }
        }
        $query->whereIn('game_version', $patches_array);
      }else{
        $query->whereIn('game_version', $this->timeframe);
      }
    }
    if(count($this->game_type) != 0){
      $query->whereIn('game_type', $this->game_type);
    }
    $query->where('hero', $hero_array[$this->hero]);

    if(count($this->league_tier) != 0){
      $query->whereIn('league_tier', $this->league_tier);
    }

    if(count($this->game_map) != 0){
      $query->whereIn('game_map', $this->game_map);
    }

    if(count($this->hero_level) != 0){
      $query->whereIn('hero_level', $this->hero_level);
    }


    $query->where('level_twenty', '<>', '0');
    $query->select('global_hero_talents.game_type', 'global_hero_talents.hero', 'global_hero_talents.level_one','global_hero_talents.level_four','global_hero_talents.level_seven','global_hero_talents.level_ten','global_hero_talents.level_thirteen','global_hero_talents.level_sixteen','global_hero_talents.level_twenty', DB::raw('SUM(games_played) as games_played'));
    $query->groupBy('global_hero_talents.game_type', 'global_hero_talents.hero', 'global_hero_talents.level_one','global_hero_talents.level_four','global_hero_talents.level_seven','global_hero_talents.level_ten','global_hero_talents.level_thirteen','global_hero_talents.level_sixteen','global_hero_talents.level_twenty');
    $query->orderBy('games_played', 'DESC');

    if($this->build_type == "Popular"){
      $query->limit(5);
    }else{
      $query->limit(100);
    }
    $build_data = $query->get();
    //print_r($query->toSql());
    //echo "<br>";
    //echo "<br>";
    //print_r($query->getBindings());

    $build_data = json_decode(json_encode($build_data),true);
    $return_data = array();
    $counter = 0;

    for($i = 0; $i < count($build_data); $i++){
      $data = array();
      foreach($this->talent_levels as $level){
        $data[$level] = $build_data[$i][$level];
      }


      if($this->build_type == "Popular"){
        $return_data[$counter] = $data;
        $counter++;
      }else{

        if($counter != 0){
          $foundMatch = false;
          for($j = 0; $j < count($return_data); $j++){

            if($this->build_type == "HP"){
              if($data["level_one"] == $return_data[$j]["level_one"] && $data["level_four"] == $return_data[$j]["level_four"] && $data["level_seven"] == $return_data[$j]["level_seven"]){
                $foundMatch = true;
              }
            }else if($this->build_type == "1"){
              if($data["level_one"] == $return_data[$j]["level_one"]){
                $foundMatch = true;
              }
            }else if($this->build_type == "4"){
              if($data["level_four"] == $return_data[$j]["level_four"]){
                $foundMatch = true;
              }
            }else if($this->build_type == "7"){
              if($data["level_seven"] == $return_data[$j]["level_seven"]){
                $foundMatch = true;
              }
            }else if($this->build_type == "10"){
              if($data["level_ten"] == $return_data[$j]["level_ten"]){
                $foundMatch = true;
              }
            }else if($this->build_type == "13"){
              if($data["level_thirteen"] == $return_data[$j]["level_thirteen"]){
                $foundMatch = true;
              }
            }else if($this->build_type == "16"){
              if($data["level_sixteen"] == $return_data[$j]["level_sixteen"]){
                $foundMatch = true;
              }
            }else if($this->build_type == "20"){
              if($data["level_twenty"] == $return_data[$j]["level_twenty"]){
                $foundMatch = true;
              }
            }
          }

          if(!$foundMatch){
              $return_data[$counter] = $data;
              $counter++;
          }

        }else{
          $return_data[$counter] = $data;
          $counter++;
        }

        if($counter == 5){
          break;
        }

      }
    }

    return $return_data;



  }

  private function getWinLoss($value, $winLoss, $most_played_builds, $level){

    $maps = $this->session_data["maps_by_name"];
    $hero_array = $this->session_data["heroes_by_name"];

    if(isset($request["timeframe_type"])){
      $this->timeframe_type = $request["timeframe_type"];
    }else{
      $this->timeframe_type = "major";
    }

    if(isset($request["timeframe"])){
      $this->timeframe = array($request["timeframe"]);
    }else{
      $this->timeframe = array($this->session_data["major_patch"]);
    }

    if(isset($request["game_type"]) && $request["game_type"] != ""){
      $this->game_type = array($request["game_type"]);
    }else{
      $this->game_type = array($this->session_data["default_game_mode_id"]);
    }

    if(isset($request["league_tier"]) && $request["league_tier"] != ""){
      $this->league_tier = array($request["league_tier"]);
    }

    if(isset($request["game_map"]) && $request["game_map"] != ""){
      $this->game_map =  array($request["game_map"]);
      for($i = 0; $i < count($this->game_map); $i++){
        $this->game_map[$i] = $maps[$this->game_map[$i]];
      }
    }

    if(isset($request["hero_level"]) && $request["hero_level"] != ""){
      $this->hero_level = array($request["hero_level"]);
    }

    if(isset($request["hero"]) && $request["hero"] != ""){
      $this->hero = $request["hero"];
    }

    if(isset($request["build_type"]) && $request["build_type"] != ""){
      $this->build_type = $request["build_type"];
    }

    $query = DB::table('heroesprofile.global_hero_talents');
    if(count($this->timeframe) != 0){
      if($this->timeframe_type == "major"){
        $patches_array = array();
        for($i = 0; $i < count($this->timeframe); $i++){
          if(count($patches_array) == 0){
            $patches_array = $this->session_data['major_to_minor_patch_mapping'][$this->timeframe[$i]];
          }else{
            array_merge($patches_array, $this->session_data['major_to_minor_patch_mapping'][$this->timeframe[$i]]);
          }
        }
        $query->whereIn('game_version', $patches_array);
      }else{
        $query->whereIn('game_version', $this->timeframe);
      }
    }
    if(count($this->game_type) != 0){
      $query->whereIn('game_type', $this->game_type);
    }
    $query->where('hero', $hero_array[$this->hero]);

    if(count($this->league_tier) != 0){
      $query->whereIn('league_tier', $this->league_tier);
    }

    if(count($this->game_map) != 0){
      $query->whereIn('game_map', $this->game_map);
    }

    if(count($this->hero_level) != 0){
      $query->whereIn('hero_level', $this->hero_level);
    }


    $query->where('win_loss', $value);
    $query->where('level_one', $most_played_builds["level_one"]);
    $query->where('level_four', $most_played_builds["level_four"]);
    $query->where('level_seven', $most_played_builds["level_seven"]);
    $query->where('level_ten', $most_played_builds["level_ten"]);

    if($level == 13){
      $query->where('level_thirteen', $most_played_builds["level_thirteen"]);
    }else if($level == 16){
      $query->where('level_thirteen', $most_played_builds["level_thirteen"]);
      $query->where('level_sixteen', $most_played_builds["level_sixteen"]);
    }else if($level == 20){
      $query->where('level_thirteen', $most_played_builds["level_thirteen"]);
      $query->where('level_sixteen', $most_played_builds["level_sixteen"]);
      $query->where('level_twenty', $most_played_builds["level_twenty"]);
    }

    $query->select(DB::raw('SUM(games_played) as games_played'));

    $build_data = $query->get();
    $build_data = json_decode(json_encode($build_data),true);
    return $build_data[0]["games_played"];
  }
}
