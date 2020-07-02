<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * forbiddenisland implementation : © Jeff DiCorpo <jdicorpo@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * forbiddenisland.view.php
 *
 * This is your "view" file.
 *
 * The method "build_page" below is called each time the game interface is displayed to a player, ie:
 * _ when the game starts
 * _ when a player refreshes the game page (F5)
 *
 * "build_page" method allows you to dynamically modify the HTML generated for the game interface. In
 * particular, you can set here the values of variables elements defined in forbiddenisland_forbiddenisland.tpl (elements
 * like {MY_VARIABLE_ELEMENT}), and insert HTML block elements (also defined in your HTML template file)
 *
 * Note: if the HTML of your game interface is always the same, you don't have to place anything here.
 *
 */
  

  /*
      
      // Examples: set the value of some element defined in your tpl file like this: {MY_VARIABLE_ELEMENT}

      // Display a specific number / string
      $this->tpl['MY_VARIABLE_ELEMENT'] = $number_to_display;

      // Display a string to be translated in all languages: 
      $this->tpl['MY_VARIABLE_ELEMENT'] = self::_("A string to be translated");

      // Display some HTML content of your own:
      $this->tpl['MY_VARIABLE_ELEMENT'] = self::raw( $some_html_code );
      
      */
      
      /*
      
      // Example: display a specific HTML block for each player in this game.
      // (note: the block is defined in your .tpl file like this:
      //      <!-- BEGIN myblock --> 
      //          ... my HTML code ...
      //      <!-- END myblock --> 
      

      $this->page->begin_block( "forbiddenisland_forbiddenisland", "myblock" );
      foreach( $players as $player )
      {
          $this->page->insert_block( "myblock", array( 
                                                  "PLAYER_NAME" => $player['player_name'],
                                                  "SOME_VARIABLE" => $some_value
                                                  ...
                                                   ) );
      }
      
      */

require_once( APP_BASE_PATH."view/common/game.view.php" );
  
class view_forbiddenisland_forbiddenisland extends game_view
{
    function getGameName() {
        return "forbiddenisland";
    }
    function getTemplateName() {
        return self::getGameName() . "_" . self::getGameName();
    }    
  	function build_page( $viewArgs )
    {		
        // Get players & players number
        $players = $this->game->loadPlayersBasicInfos();
        $players_nbr = count( $players );

        /*********** Place your code below:  ************/

        $template = self::getTemplateName();
        $num = $players_nbr;

        $island_map_id = $this->game->getGameStateValue("island_map");
        $island_map = $this->game->island_map[$island_map_id]['map'];
        $max_x = $this->game->island_map[$island_map_id]['max_x'];
        $max_y = $this->game->island_map[$island_map_id]['max_y'];

        $this->page->begin_block($template, "island_tile" );
        
        $hor_scale = 128+8;
        $ver_scale = 128+8;
        
        for( $x=1; $x<=$max_x; $x++ )
        {
            for( $y=1; $y<=$max_y; $y++ )
            {
                $tile_location = $x . "_" . $y;
                if (in_array($tile_location, $island_map)) {
                    $this->page->insert_block( "island_tile", array(
                        'X' => $x,
                        'Y' => $y,
                        'LEFT' => round( ($x-1)*$hor_scale ),
                        'TOP' => round( ($y-1)*$ver_scale ),
                    ) );
                }
            }        
        }

        $water_level_meter = $this->game->island_map[$island_map_id]['water_level_meter'];

        $ver_pos = array( 10 => 16, 9 => 50, 8 => 83, 7 => 112, 6 => 144, 5 => 178, 4 => 210, 
            3 => 244, 2 => 278, 1 => 311 );
        $this->page->begin_block($template, "water_level" );
        $this->page->begin_block($template, "water_level_meter" );

        for( $y=10; $y>=1; $y-- )
        {
            $this->page->insert_block( "water_level", array(
                'LVL' => $y,
                'TOP_LVL' => $ver_pos[$y],
            ) );
        }   

        $this->page->insert_block( "water_level_meter", array(
            'LEFT' => (explode("_", $water_level_meter)[0] - 1) * $hor_scale,
            'TOP' => (explode("_", $water_level_meter)[1] - 1) * $ver_scale,
        ) );

        $this->page->begin_block($template, "treasure_starting_area" );

        $all_treasures = array('earth', 'air', 'fire', 'ocean');
        foreach($all_treasures as $treasure) {
            $treasure_location = $this->game->island_map[$island_map_id][$treasure];
            $this->page->insert_block( "treasure_starting_area", array(
                'TREASURE' => $treasure,
                'LEFT' => (explode("_", $treasure_location)[0] - 1) * $hor_scale,
                'TOP' => (explode("_", $treasure_location)[1] - 1) * $ver_scale,
            ) );
        }

        $this->page->begin_block($template, "player");
        foreach ( $players as $player_id => $info ) {
            $this->page->insert_block("player", array (
                    "PLAYER_ID" => $player_id,
                    "PLAYER_NAME" => $players [$player_id] ['player_name'],
                    "PLAYER_COLOR" => $players [$player_id] ['player_color']
                ));
        }

        // var_dump( $viewArgs );
        // var_dump( $this );

      /*********** Do not change anything below this line  ************/
    }
}