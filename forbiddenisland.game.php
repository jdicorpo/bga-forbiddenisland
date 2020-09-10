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
  * forbiddenisland.game.php
  *
  * This is the main file for your game logic.
  *
  * In this PHP file, you are going to defines the rules of the game.
  *
  */  
  

require_once( APP_GAMEMODULE_PATH.'module/table/table.game.php' );


class forbiddenisland extends Table
{
	function __construct( )
	{
        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
        parent::__construct();
        
        self::initGameStateLabels( array( 
               "remaining_actions" => 10,
               "water_level" => 11,
               "remaining_flood_cards" => 12,
               "drawn_treasure_cards" => 13,
               "air" => 14,
               "fire" => 15,
               "earth" => 16,
               "ocean" => 17,
               "players_win" => 18,
               "pilot_action" => 19,
               "special_action_player" => 20,
               "discard_treasure_player" => 21,
               "rescue_pawn_player" => 22,
               "rescue_pawn_tile" => 23,
               "special_card_id" => 24,
               "undo_move_tile" => 25,
               "difficulty" => 100,
               "island_map" => 101,
        ) );        

        $this->tiles = self::getNew( "module.common.deck");
        $this->tiles->init("tiles");

        $this->flood_deck = self::getNew( "module.common.deck");
        $this->flood_deck->init("flood_deck");

        $this->treasure_deck = self::getNew( "module.common.deck");
        $this->treasure_deck->init("treasure_deck");

        $this->player_deck = self::getNew( "module.common.deck");
        $this->player_deck->init("player_deck");

	}
	
    protected function getGameName( )
    {
		// Used for translations and stuff. Please do not modify.
        return "forbiddenisland";
    }	

    /*
        setupNewGame:
        
        This method is called only once, when a new game is launched.
        In this method, you must setup the game according to the game rules, so that
        the game is ready to be played.
    */
    protected function setupNewGame( $players, $options = array() )
    {    
        // Set the colors of the players with HTML color code
        // The default below is red/green/blue/orange/brown
        // The number of colors defined here must correspond to the maximum number of players allowed for the gams
        $gameinfos = self::getGameinfos();
        $default_colors = $gameinfos['player_colors'];

        $card_deck = array();
        foreach ($this->player_list as $x => $x_value) {
            // $card_deck[] = array( 'type' => $x, 'type_arg' =>  $x_value['img_id'], 'nbr' => 1);
            $card_deck[] = array('type' => $x, 'type_arg' =>  1, 'nbr' => 1);
        }
        $this->player_deck->createCards($card_deck, 'deck');
        $this->player_deck->shuffle('deck');

        $card_deck = array();
        foreach ($this->treasure_list as $x => $x_value) {
            $card_deck[] = array('type' => $x, 'type_arg' =>  1, 'nbr' => $x_value['nbr']);
        }
        $this->treasure_deck->createCards($card_deck, 'deck');
        $this->treasure_deck->shuffle('deck');
        $this->treasure_deck->autoreshuffle = true;
        $this->treasure_deck->autoreshuffle_trigger = array('obj' => $this, 'method' => 'treasureDeckReshuffle');

        $card_deck = array();
        foreach ($this->flood_list as $x => $x_value) {
            $card_deck[] = array( 'type' => $x, 'type_arg' =>  1, 'nbr' => 1);
        }
        $this->flood_deck->createCards( $card_deck, 'deck' );
        $this->flood_deck->shuffle( 'deck' );
 
        // Create players
        // Note: if you added some extra field on "player" table in the database (dbmodel.sql), you can initialize it there.
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar, adventurer, location) VALUES ";
        $values = array();
        $new_players = 0;
        foreach( $players as $player_id => $player )
        {
            if ($player['beginner'] == 1)  $new_players++;
            $player_card = $this->player_deck->pickCardForLocation( 'deck', 'hand', $player_id );
            $adventurer = $player_card['type'];
            $location = $this->player_list[$adventurer]['location'];
            self::initStat("player", "adventurer", $this->player_list[$adventurer]['pawn_idx'], $player_id);
            // $color = array_shift( $default_colors );
            $color = $this->player_list[$adventurer]['color'];
            $values[] = "('" . $player_id . "','$color','" . $player['player_canal'] . "','" . addslashes( $player['player_name'] ) . "','".addslashes( $player['player_avatar'] ) . "','$adventurer','$location')";
            for( $x=1; $x<=2; $x++ ) {
                $card = $this->treasure_deck->pickCardForLocation('deck', 'hand', $player_id );
                while ($card['type'] == 'waters_rise') {
                    $this->treasure_deck->moveCard($card['id'], 'discard');
                    $card = $this->treasure_deck->pickCardForLocation('deck', 'hand', $player_id );
                }
            }
        }
        $this->treasure_deck->moveAllCardsInLocation('discard', 'deck');
        $this->treasure_deck->shuffle('deck');

        $sql .= implode( $values, ',' );
        self::DbQuery( $sql );
        // self::reattributeColorsBasedOnPreferences( $players, $gameinfos['player_colors'] );
        self::reloadPlayersBasicInfos();
        
        /************ Start the game initialization *****/

        // Init global values with their initial values
        self::setGameStateInitialValue( 'remaining_actions', 3 );
        self::setGameStateInitialValue( 'remaining_flood_cards', 6 );
        self::setGameStateInitialValue( 'drawn_treasure_cards', 0 );
        self::setGameStateInitialValue( 'water_level', self::getGameStateValue("difficulty") );
        self::setGameStateInitialValue( 'air', 0 );
        self::setGameStateInitialValue( 'fire', 0 );
        self::setGameStateInitialValue( 'earth', 0 );
        self::setGameStateInitialValue( 'ocean', 0 );
        self::setGameStateInitialValue( 'players_win', 0 );
        self::setGameStateInitialValue( 'pilot_action', 1 );
        self::setGameStateInitialValue( 'special_card_id', 0 );
        self::setGameStateInitialValue( 'undo_move_tile', 0 );
        
        // Init game statistics
        self::initStat( 'table', 'turns_number', 0 );
        self::initStat( 'table', 'players_number', count( $players ) );
        self::initStat( 'table', 'beginners_number', $new_players );
        self::initStat( 'table', 'difficulty', self::getGameStateValue("difficulty") );
        self::initStat( 'table', 'island_map', self::getGameStateValue("island_map") );
        self::initStat( 'table', 'tiles_flooded', 0 );
        self::initStat( 'table', 'tiles_sunk', 0 );
        self::initStat( 'table', 'tiles_shored_up', 0 );
        self::initStat( 'table', 'treasures_captured', 0 );
        self::initStat( 'table', 'water_level', self::getGameStateValue("water_level") );
        self::initStat( 'table', 'players_won', false );

        self::initStat( 'player', 'turns_number', 0 ); 
        self::initStat( 'player', 'move', 0 );  
        self::initStat( 'player', 'shore_up', 0 );  
        self::initStat( 'player', 'give_card', 0 );  
        self::initStat( 'player', 'capture', 0 );  
        self::initStat( 'player', 'skip', 0 );  
        self::initStat( 'player', 'sandbags', 0 );  
        self::initStat( 'player', 'heli_lift', 0 );  
        self::initStat( 'player', 'pilot', 0 );  
        self::initStat( 'player', 'navigator', 0 );  
        self::initStat( 'player', 'bonus_shoreup', 0 );  

       
        $card_deck = array();
        foreach ($this->tile_list as $x => $x_value) {
            $card_deck[] = array( 'type' => $x, 'type_arg' =>  1, 'nbr' => 1);
        }

        $this->tiles->createCards( $card_deck, 'deck' );
        $this->tiles->shuffle( 'deck' );

        $island_map_id = self::getGameStateValue("island_map");
        $island_map = $this->island_map[$island_map_id]['map'];
        $max_x = $this->island_map[$island_map_id]['max_x'];
        $max_y = $this->island_map[$island_map_id]['max_y'];

        for( $x=1; $x<=$max_x; $x++ )
        {
            for( $y=1; $y<=$max_y; $y++ )
            {
                $tile_location = $x . "_" . $y;
                $tile_value = $x*10 + $y;
                if (in_array($tile_location, $island_map)) {
                    $tile = $this->tiles->pickCardForLocation( 'deck', 'unflooded', $tile_value);
                }
            }        
        }

        $player_id = $this->activeNextPlayer();
        self::incStat(1, "turns_number", $player_id);


        /************ End of the game initialization *****/
    }

    /*
        getAllDatas: 
        
        Gather all informations about current game situation (visible by the current player).
        
        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */
    protected function getAllDatas()
    {
        // ********  FOR DEBUG ONLY -- REMOVE for PRODUCTION!!
        // self::debugLoadReport();
        // ********

        $result = array();
    
        $current_player_id = self::getCurrentPlayerId();    // !! We must only return informations visible by this player !!
    
        // Get information about players
        // Note: you can retrieve some extra field you added for "player" table in "dbmodel.sql" if you need it.
        $sql = "SELECT player_id id, player_score score, adventurer, location FROM player ";
        $result['players'] = self::getCollectionFromDb( $sql );

        // $sql = "SELECT token_id, location FROM tokens ";
        // $result['tokens'] = self::getCollectionFromDb( $sql );

        $players = $this->loadPlayersBasicInfos();
        foreach ( $players as $player_id => $player_info ) {
            $result['player_card_area'][$player_id]['adventurer'] = $this->player_deck->getCardsInLocation( 'hand', $player_id );
            $result['player_card_area'][$player_id]['treasure_cards'] = $this->treasure_deck->getCardsInLocation( 'hand', $player_id );
        }

        $island_map_id = self::getGameStateValue("island_map");
        $island_map = $this->island_map[$island_map_id]['map'];
        $max_x = $this->island_map[$island_map_id]['max_x'];
        $max_y = $this->island_map[$island_map_id]['max_y'];
        $result['interface_max_width'] = ($max_x + 1) * (147+13);
        $result['interface_max_height'] = ($max_y) * (147+13);

        $result['island_name'] = $this->island_map[$island_map_id]['name'];
        $result['difficulty'] = $this->difficulty[self::getGameStateValue("difficulty")]['name'];
            
        $result['flood_card_area'] = $this->flood_deck->getCardsInLocation( 'flood_area' );
        $result['treasure_discards'] = $this->treasure_deck->getCardsInLocation( 'discard' );
  
        // TODO: Gather all information about current game situation (visible by player $current_player_id).
  
        $result['unflooded'] = $this->tiles->getCardsInLocation( 'unflooded' );
        $result['flooded'] = $this->tiles->getCardsInLocation( 'flooded' );
        $result['sunk'] = $this->tiles->getCardsInLocation( 'sunk' );
        $result['tile_list'] = $this->tile_list;
        $result['player_list'] = $this->player_list;
        $result['flood_list'] = $this->flood_list;
        $result['treasure_list'] = $this->treasure_list;

        $result['remaining_actions'] = $this->getGameStateValue("remaining_actions");
        $result['water_level'] = $this->getGameStateValue("water_level");
        $result['remaining_flood_cards'] = $this->getGameStateValue("remaining_flood_cards");

        $result['air'] = $this->getGameStateValue("air");
        $result['fire'] = $this->getGameStateValue("fire");
        $result['earth'] = $this->getGameStateValue("earth");
        $result['ocean'] = $this->getGameStateValue("ocean");

        $result['pilot_action'] = $this->getGameStateValue("pilot_action");

        $result['flood_deck_count'] = $this->flood_deck->countCardInLocation('deck');
        $result['treasure_deck_count'] = $this->treasure_deck->countCardInLocation('deck');

        return $result;
    }

    /*
        getGameProgression:
        
        Compute and return the current game progression.
        The number returned must be an integer beween 0 (=the game just started) and
        100 (= the game is finished or almost finished).
    
        This method is called each time we are in a game state with the "updateGameProgression" property set to true 
        (see states.inc.php)
    */
    function getGameProgression()
    {
        // TODO: compute and return the game progression
        $progress = 0;
        $all_treasures = array('earth', 'air', 'fire', 'ocean');
        foreach($all_treasures as $treasure) {
            $progress += ($this->getGameStateValue($treasure) != 0) ? 25 : 0;
        }

        return $progress;
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////    

    /*
        In this space, you can put any utility methods useful for your game logic
    */

        function getPossibleActions( $player_id )
        {
            $result = array('move' => array(), 'shore_up' => array());
            // $result = array_merge($result, $this->getPossibleMoves( $player_id ));
            $result = array_merge($result, $this->getPossibleMoves( $player_id ));
            $result = array_merge($result, $this->getPossibleShoreUp( $player_id ));
            $result = array_merge($result, $this->getPossibleSandbags());
            $result = array_merge($result, $this->getPossibleHeliLift( $player_id ));

            $players = $this->loadPlayersBasicInfos();
            foreach ( $players as $pid => $player_info ) {
                $player_moves = $this->getPossibleNavigator( $pid );
                // $result = array_merge_recursive($result, $player_moves);
                if (array_key_exists('navigator', $player_moves)) {
                    $result['navigator'][$pid] = $player_moves['navigator'][$pid];
                }
            }

            return $result;

        }

        function getPossibleMoves( $player_id )
        {
            $player_tile_id = $this->getPlayerLocation($player_id);
            $result = array( 'move' => array(), 'navigator' => array());
            $diverMoveTiles = array();
            $new_tile_added = true;
            $isDiver = ($this->getAdventurer( $player_id ) == 'diver');
            $all_tiles = array_merge(
                $this->tiles->getCardsInLocation('unflooded'),
                $this->tiles->getCardsInLocation('flooded'),
                $this->tiles->getCardsInLocation('sunk')
            );

            # keep looping until a new tile is not added
            while ($new_tile_added) {
                $new_tile_added = false;

                # on each pass, check every tile once
                foreach ($all_tiles as $id => $tile ) {

                    # check if adjacent to the player tile
                    if ($this->isTileAdjacent($tile['type'], $player_tile_id, $player_id) ) {

                        # if not sunk and not added already, add to possible tiles
                        if (($tile['location'] != 'sunk') and (! in_array($tile['type'], $result['move'] ))) {
                            $result['move'][] = $tile['type'];
                            $new_tile_added = true;
                        }

                        # if a diver, not unflooded and not in diver tile list, add to diver tile list
                        if ( ($isDiver) and ($tile['location'] != 'unflooded') and (! in_array($tile['type'], $diverMoveTiles )) ) {
                            $diverMoveTiles[] = $tile['type'];
                            $new_tile_added = true;
                        }
                    }

                    if ($isDiver) {
                        foreach ($diverMoveTiles as $move_id => $move_tile ) {
                            
                            # if isDiver, check each tile in diver tile list to see if adjacent
                            if ($this->isTileAdjacent($tile['type'], $move_tile, $player_id) ) {

                                # if not sunk and not added already, add to possible tiles
                                if (($tile['location'] != 'sunk') and (! in_array($tile['type'], $result['move'] ))) {
                                    $result['move'][] = $tile['type'];
                                    $new_tile_added = true;
                                }

                                # if a diver, not unflooded and not in diver tile list, add to diver tile list
                                if (($isDiver) and ($tile['location'] != 'unflooded') and (! in_array($tile['type'], $diverMoveTiles ))) {
                                    $diverMoveTiles[] = $tile['type'];
                                    $new_tile_added = true;
                                }
                            }
                        }
                    }
                }
            }

            return $result;

        }

        function nextLevelCheck($player_id, $player_tile_id, $result, $tile_id) {

            foreach ($this->tiles->getCardsInLocation('unflooded') as $id => $tile ) {
                if (array_key_exists('navigator', $result)) {
                    if (! in_array($tile['type'], $result['navigator'][$player_id] ) and ($tile['type'] != $player_tile_id)) {
                        if ( $this->isTileAdjacent($tile['type'], $tile_id, $player_id) )
                        {
                            $result['navigator'][$player_id][] = $tile['type'];
                        }
                    }
                } else {
                    if (( $this->isTileAdjacent($tile['type'], $tile_id, $player_id)) and ($tile['type'] != $player_tile_id))
                    {
                        $result['navigator'][$player_id][] = $tile['type'];
                    }
                }
            }

            foreach ($this->tiles->getCardsInLocation('flooded') as $id => $tile ) {
                if (array_key_exists('navigator', $result)) {
                    if (! in_array($tile['type'], $result['navigator'][$player_id] ) and ($tile['type'] != $player_tile_id)) {
                        if ( $this->isTileAdjacent($tile['type'], $tile_id, $player_id) )
                        {
                            $result['navigator'][$player_id][] = $tile['type'];
                        }
                    }
                } else {
                    if (( $this->isTileAdjacent($tile['type'], $tile_id, $player_id)) and ($tile['type'] != $player_tile_id))
                    {
                        $result['navigator'][$player_id][] = $tile['type'];
                    }
                }
            }

            return $result;
        }

        function getPossibleNavigator($player_id) {

            $player_tile_id = $this->getPlayerLocation($player_id);
            $result = array();

            foreach ($this->tiles->getCardsInLocation('unflooded') as $id => $tile ) {
                    if ( $this->isTileAdjacent($tile['type'], $player_tile_id, $player_id) )
                    {
                        $result['navigator'][$player_id][] = $tile['type'];
                    }
            }

            foreach ($this->tiles->getCardsInLocation('flooded') as $id => $tile ) {
                if ( $this->isTileAdjacent($tile['type'], $player_tile_id, $player_id) )
                {
                    $result['navigator'][$player_id][] = $tile['type'];
                }
            }

            if (array_key_exists('navigator', $result)) {
                foreach ($result['navigator'][$player_id] as $tile_id) {
                    $result = $this->nextLevelCheck($player_id, $player_tile_id, $result, $tile_id);
                }
            }

            if ($this->getAdventurer( $player_id ) == 'diver') {

                foreach ($this->tiles->getCardsInLocation('sunk') as $id => $tile ) {
                    if ( $this->isTileAdjacent($tile['type'], $player_tile_id, $player_id) ) {
                        $result = $this->nextLevelCheck($player_id, $player_tile_id, $result, $tile['type']);
                    }
                }

            }

            // self::notifyAllPlayers( "log", "getPossibleNavigator", array(
            //     'player_id' => $player_id,
            //     'player_tile_id' => $player_tile_id,
            //     'tile_id' => $tile_id,
            //     // 'checked' => $checked,
            //     'result' => $result,
            // ) );

            return $result;

        }

        function getPossibleShoreUp( $player_id )
        {
            $player_tile_id = $this->getPlayerLocation($player_id);
            $result = array();

            foreach ($this->tiles->getCardsInLocation('flooded') as $id => $tile ) {
                if ( $this->isTileAdjacent($tile['type'], $player_tile_id, $player_id) or ($tile['type'] == $player_tile_id))
                {
                    $result['shore_up'][] = $tile['type'];
                }
            }

            // $result['shore_up'][] = $this->getPlayerLocation($player_id);

            return $result;
        }

        function getPossibleSandbags()
        {
            $result = array();

            foreach ($this->tiles->getCardsInLocation('flooded') as $id => $tile ) {
                $result['sandbags'][] = $tile['type'];
            }
            return $result;


        }

        function getPossibleHeliLift( $player_id )
        {
            $result = array();
            $player_tile_id = $this->getPlayerLocation($player_id);

            foreach ($this->tiles->getCardsInLocation('unflooded') as $id => $tile ) {
                // if ( $tile['type'] != $player_tile_id ) {
                    $result['heli_lift'][] = $tile['type'];
                // }
            }
            foreach ($this->tiles->getCardsInLocation('flooded') as $id => $tile ) {
                // if ( $tile['type'] != $player_tile_id ) {
                    $result['heli_lift'][] = $tile['type'];
                // }
            }
            return $result;

        }

        function getPlayerLocations()
        {
            $result = array();

            $players = $this->loadPlayersBasicInfos();
            foreach ( $players as $player_id => $player_info ) {
                $result[] = array('id' => $player_id, 'location' => $this->getPlayerLocation($player_id));
            }

            return $result;

        }

        function isTileAdjacent( $tile_id, $player_tile_id, $player_id )
        {
            $result = TRUE;
            $player_tile_location = $this->getTileLocation($player_tile_id);
            $tile_location = $this->getTileLocation($tile_id);

            $player_x = floor($player_tile_location / 10);
            $player_y = $player_tile_location % 10;
            $tile_x = floor($tile_location / 10);
            $tile_y = $tile_location % 10;

            if ((abs($player_x - $tile_x) == 1) and ($player_y == $tile_y))
            {
                return TRUE;
            } 
            elseif ((abs($player_y - $tile_y) == 1) and ($player_x == $tile_x)) 
            {
                return TRUE;
            } 
            elseif (($this->getAdventurer( $player_id ) == 'explorer') and 
                    (abs($player_x - $tile_x) == 1) and (abs($player_y - $tile_y) == 1)) {
                return true;
            } 
            else 
            {
                return FALSE;
            }
        }

        function getPlayerLocation($player_id) {
            $sql = "SELECT location FROM player WHERE  player_id='$player_id' ";
            return $this->getUniqueValueFromDB($sql);
        }

        function getPlayerName($player_id) {
            $sql = "SELECT player_name FROM player WHERE  player_id='$player_id' ";
            return $this->getUniqueValueFromDB($sql);
        }

        function getAdventurer( $player_id ) {
            // $player_id = self::getActivePlayerId();
            $sql = "SELECT adventurer FROM player WHERE  player_id='$player_id' ";
            return $this->getUniqueValueFromDB($sql);
        }

        function getPlayersAtLocation($tile_id) {
            $sql = "SELECT player_id FROM player WHERE location='$tile_id' ";
            return self::getCollectionFromDb( $sql );
        }

        function getColocatedPlayers($player_id) {
            $tile_id = self::getPlayerLocation($player_id);
            return self::getPlayersAtLocation($tile_id);
        }

        function getPlayers() {
            $sql = "SELECT player_id id, player_score score, adventurer, location FROM player ";
            return self::getCollectionFromDb( $sql );
        }

        function getTileLocation($tile_id) {
            $sql = "SELECT card_location_arg FROM tiles WHERE  card_type='$tile_id' ";
            return $this->getUniqueValueFromDB($sql);
        }

        function getNumberFloodCards($water_level) {

            switch ($water_level) {
                case 1:
                case 2:
                    return 2;
                    break;
                case 3:
                case 4:
                case 5:
                    return 3;
                    break;
                case 6:
                case 7:
                    return 4;
                    break;
                case 8:
                case 9:
                    return 5;
                    break;
                default:
                    return 0;
            }
        }

        function getTreasureCards($player_id) {
            $cards = array();
            $cards = $this->treasure_deck->getCardsInLocation( 'hand', $player_id);
            return $cards;
        }

        function getMultiactivePlayers() {
            $result = array(self::getActivePlayerId());

            $players = $this->loadPlayersBasicInfos();
            foreach ( $players as $player_id => $player_info ) {
                $player_cards = $this->getTreasureCards($player_id);
                foreach ($player_cards as $id => $value) {
                    $c = $this->treasure_deck->getCard( $id);
                    if (($c['type'] == 'sandbags') or ($c['type'] == 'heli_lift')) {
                        if (! in_array($player_id, $result)) {
                            $result[] = $player_id;
                        }
                    }
                }
            }
            return $result;
        }

        function waters_rise($card, $skip_reshuffle = false) {
            $player_id = self::getActivePlayerId();
            $water_level = $this->incGameStateValue("water_level", 1);
            self::setStat($water_level, "water_level");

            $new_flood_draw = $this->getNumberFloodCards($water_level);

            $flood_discards = $this->flood_deck->countCardsInLocation("flood_area");

            if ($skip_reshuffle) {
                $message = clienttranslate( 'Waters Rise! Second card drawn in a row. Water level is now ${water_level}. ${new_flood_draw} flood cards will be drawn at the end of each turn.');
            } else {
                $this->flood_deck->shuffle("flood_area");
                // $this->flood_deck->moveAllCardsInLocationKeepOrder("flood_area", "deck");
                $cards = $this->flood_deck->getCardsInLocation( "flood_area");
                foreach ($cards as $id => $value) {
                    $this->flood_deck->insertCardOnExtremePosition( $id, 'deck', 'top');
                }
                $message = clienttranslate( 'Waters Rise!  Reshuffling ${flood_discards} flood discards on top of deck. Water level is now ${water_level}.  ${new_flood_draw} flood cards will be drawn at the end of each turn.');           
            }

            self::notifyAllPlayers( "watersRise", $message, array(
                'water_level' => $water_level,
                'flood_discards' => $flood_discards,
                'new_flood_draw' => $new_flood_draw,
                'card' => $card,
                'player_id' => $player_id
            ) );

        }

        function getLocationTreasure($player_tile_id) {

            switch ($player_tile_id) {
                case 'cave_embers':
                case 'cave_shadows':
                    return 'fire';
                    break;
                case 'coral_palace':
                case 'tidal_palace':
                    return 'ocean';
                    break;
                case 'howling_garden':
                case 'whispering_garden':
                    return 'air';
                    break;
                case 'temple_moon':
                case 'temple_sun':
                    return 'earth';
                    break;
                default:
                    return 'none';
                    break;
            }
        }

        function getMatchingCards($player_id, $treasure) {
            $cards = array();
            $player_cards = $this->getTreasureCards($player_id);
            foreach ($player_cards as $id => $value) {
                $c = $this->treasure_deck->getCard( $id);
                if ($c['type'] == $treasure) {
                    $cards[] = $c;
                }
            }
            return $cards;
        }

        function treasureDeckReshuffle() {
            self::notifyAllPlayers( "reshuffleTreasureDeck", clienttranslate( 'Treasure deck reshuffled.' ), array(
            ) );
        }

        function floodDeckReshuffle() {
            $this->flood_deck->moveAllCardsInLocation("flood_area", "deck");
            $this->flood_deck->shuffle("deck");
            self::notifyAllPlayers( "reshuffleFloodDeck", clienttranslate( 'Flood deck reshuffled.' ), array(
            ) );
        }

        function countTreasures() {
            $nTreaures = ($this->getGameStateValue('air') != 0) ? 1 : 0;
            $nTreaures += ($this->getGameStateValue('earth') != 0) ? 1 : 0 ;
            $nTreaures += ($this->getGameStateValue('fire') != 0) ? 1 : 0;
            $nTreaures += ($this->getGameStateValue('ocean') != 0) ? 1 : 0;
            return $nTreaures;
        }

        function isWinCondition() {
            $nPlayers = count($this->getPlayersAtLocation('fools_landing'));
            $nTreaures = $this->countTreasures();

            return (($nPlayers == $this->getPlayersNumber()) and ($nTreaures == 4)) ? true : false;
        }

        function checkFoolsLanding() {
            $tiles = $this->tiles->getCardsOfType('fools_landing');
            $tile = array_shift($tiles);
            return $tile['location'];
        }

        function checkTreasureTiles($treasure) {
            $tile_id_0 = $this->treasure_list[$treasure]['tiles'][0];
            $tiles = $this->tiles->getCardsOfType($tile_id_0);
            $tile_0 = array_shift($tiles);
            $tile_id_1 = $this->treasure_list[$treasure]['tiles'][1];
            $tiles = $this->tiles->getCardsOfType($tile_id_1);
            $tile_1 = array_shift($tiles);

            $nTiles = ($tile_0['location'] != 'sunk') ? 1 : 0;
            $nTiles += ($tile_1['location'] != 'sunk') ? 1 : 0;

            return $nTiles;
        }

        function isGameLost( $current_tile = null ) {

            // check if water level is 10 or greater
            $water_level = $this->getGameStateValue("water_level");
            if ($water_level >= 10) {
                return true;
            }

            // check if Fool's Landing is sunk
            if ($this->checkFoolsLanding() == 'sunk') {
                return true;
            }

            // check if tiles are sunk for unclaimed treasure
            $all_treasures = array('earth', 'air', 'fire', 'ocean');
            foreach($all_treasures as $treasure) {
                if ( $this->getGameStateValue($treasure) == 0 ) {
                    $tile_id_0 = $this->treasure_list[$treasure]['tiles'][0];
                    $tiles = $this->tiles->getCardsOfType($tile_id_0);
                    $tile_0 = array_shift($tiles);
                    $tile_id_1 = $this->treasure_list[$treasure]['tiles'][1];
                    $tiles = $this->tiles->getCardsOfType($tile_id_1);
                    $tile_1 = array_shift($tiles);
                    if (($tile_0['location'] == 'sunk') and ($tile_1['location'] == 'sunk'))  {
                        return true;
                    }
                    if (($tile_0['type'] == $current_tile) and ($tile_1['location'] == 'sunk'))  {
                        return true;
                    }
                    if (($tile_0['location'] == 'sunk') and ($tile_1['type'] == $current_tile))  {
                        return true;
                    }
                }
            }

            return false;
        }

        function updateCardCount() {
            $ncards = array();
            $players = $this->loadPlayersBasicInfos();
            foreach ( $players as $player_id => $player_info ) {
                $ncards[$player_id] = count($this->treasure_deck->getCardsInLocation( 'hand', $player_id ));
            }

            self::notifyAllPlayers( "updateCardCount", '', array(
                'ncards' => $ncards,
                'flood_deck_count' => $this->flood_deck->countCardInLocation('deck'),
                'treasure_deck_count' => $this->treasure_deck->countCardInLocation('deck')
            ) );
        }

        function setNextState( $action = null, $player_id = null, $end_turn = false ) {

            // determine next state
            $state = $this->gamestate->state();
            switch ($state['name']) {
                case 'playerActions':
                // case 'discardTreasure':
                    if ($player_id != null) {
                        $count = $this->treasure_deck->countCardsInLocation('hand', $player_id );
                    } else $count = 0;
                    if ($count > 5) {
                        $this->setGameStateValue("discard_treasure_player", $player_id);
                        $this->gamestate->nextState( 'discard' );
                    } elseif (($this->getGameStateValue("remaining_actions") >= 0) and (!$end_turn)) {
                        $this->gamestate->nextState( 'action' );
                    } elseif ($this->getGameStateValue("drawn_treasure_cards") < 2) {
                        $this->gamestate->nextState( 'draw_treasure' );
                    } else {
                        $this->gamestate->nextState( 'continue' );
                    }
                    break;

                case 'bonusShoreup':
                case 'sandbags':
                case 'heli_lift':
                case 'discardTreasure':
                    if ($player_id != null) {
                        $count = $this->treasure_deck->countCardsInLocation('hand', $player_id );
                    } else $count = 0;
                    if ($count > 5) {
                        $this->setGameStateValue("discard_treasure_player", $player_id);
                        $this->gamestate->nextState( 'discard' );
                    } elseif ($this->getGameStateValue("remaining_actions") > 0) {
                        $this->gamestate->nextState( 'action' );
                    } elseif ($this->getGameStateValue("drawn_treasure_cards") < 2) {
                        $this->gamestate->nextState( 'draw_treasure' );
                    } else {
                        $this->gamestate->nextState( 'continue' );
                    }
                    break;

                case 'continue':
                    $this->gamestate->nextState( 'set_flood' );
                    break;

                case 'setFlood':
                    $this->setGameStateValue("discard_treasure_player", 0);
                    $this->gamestate->nextState( 'draw_flood' );
                    break;

                case 'drawFlood':
                    if ( $this->getGameStateValue("rescue_pawn_tile") != 0) {
                        $this->gamestate->nextState( 'rescue_pawn' );                
                    } else {
                        if ($this->getGameStateValue("remaining_flood_cards") > 0) {
                            $this->gamestate->nextState( 'draw_flood' );
                        } else {
                            $this->gamestate->nextState( 'next_player' );
                        }
                    }
                    break;

                case 'rescuePawn':
                    // played rescue or heli_lift
                    $this->gamestate->setPlayerNonMultiactive( $player_id, 'draw_flood'); 
                    break;

                case 'drawTreasure':
                    $count = $this->treasure_deck->countCardsInLocation('hand', $player_id );
                    if ($count > 5) {
                        $this->setGameStateValue("discard_treasure_player", $player_id);
                        $this->gamestate->nextState( 'discard' );
                    } else {
                        $this->gamestate->nextState( 'continue' );
                    }
                    break;

                case 'nextPlayer':
                    $this->setGameStateValue("discard_treasure_player", 0);
                    $this->gamestate->nextState( 'next_turn' );
                    break;

                default:
                    break;
            }

        }

        function getFloodDiscards() {
            $discards = array();
            return array_merge( 
                $discards, 
                array_map( function($p){ return $p['type']; }, $this->flood_deck->getCardsInLocation( 'flood_area' ))
            );
        }

        function debugFlood($tile_id) {

            $tiles = $this->tiles->getCardsOfType($tile_id);
            $tile = array_shift($tiles);
            $this->tiles->moveCard($tile['id'], 'flooded', $tile['location_arg']);

            $cards = $this->flood_deck->getCardsOfType( $tile_id );
            $card = array_shift($cards);
            $this->flood_deck->moveCard($card['id'], 'flood_area');

        }

        function debugShoreUp($tile_id) {

            $tiles = $this->tiles->getCardsOfType($tile_id);
            $tile = array_shift($tiles);
            $this->tiles->moveCard($tile['id'], 'unflooded', $tile['location_arg']);

        }

        function debugSink($tile_id) {

            $tiles = $this->tiles->getCardsOfType($tile_id);
            $tile = array_shift($tiles);
            $this->tiles->moveCard($tile['id'], 'sunk', $tile['location_arg']);

            $cards = $this->flood_deck->getCardsOfType( $tile_id );
            $card = array_shift($cards);
            $this->flood_deck->moveCard($card['id'], 'sunk');

        }

        function debugMove($tile_id) {

            $player_id = self::getActivePlayerId();

            $sql = "UPDATE player SET location='$tile_id'
            WHERE player_id='$player_id'";
            self::DbQuery( $sql );

        }

        function debugRaiseWater() {

            $this->incGameStateValue("water_level", 1);

        }

        function debugSetWatersRise() {

            $cards = $this->treasure_deck->getCardsOfTypeInLocation('waters_rise', null, 'deck');
            $card = array_shift($cards);
            $this->treasure_deck->insertCardOnExtremePosition($card['id'], 'deck', true);

        }

        function debugGetFigure($treasure) {

            $player_id = self::getActivePlayerId();
            $this->setGameStateValue($treasure, $player_id);

        }

        function debugGetTreasure($treasure) {

            $player_id = self::getActivePlayerId();
            $cards = $this->treasure_deck->getCardsOfTypeInLocation($treasure, null, 'deck');
            $card = array_shift($cards);
            $this->treasure_deck->moveCard($card['id'], 'hand', $player_id);

        }

        function debugGetAdventurer($adventurer) {

            $player_id = self::getActivePlayerId();
            // $player_card = $this->player_deck->Get
            $player_card_raw = $this->player_deck->getCardsInLocation( 'hand', $player_id );
            $player_card = array_shift($player_card_raw);
            $this->player_deck->moveCard($player_card['id'], 'deck');
            $player_card_raw = $this->player_deck->getCardsOfTypeInLocation( $adventurer, null, 'deck');
            if ($player_card_raw > 0) {
                $player_card = array_shift($player_card_raw);
                $this->player_deck->moveCard( $player_card['id'], 'hand', $player_id );
                $adventurer = $player_card['type'];
                $color = $this->player_list[$adventurer]['color'];
                $sql = "UPDATE player SET adventurer='$adventurer', player_color='$color' WHERE player_id='$player_id'";
                self::DbQuery( $sql );
            }
        }

        public function debugLoadReport()
        {
            
            // bug #20491
            $id0 = '87105665';
            $id1 = '87100479';
            $id2 = '87105729';	
            $id3 = '5815028';	
            
            //player
            self::DbQuery("UPDATE player SET player_id=2320829 WHERE player_id = '" . $id0 . "'" );
            self::DbQuery("UPDATE player SET player_id=2320830 WHERE player_id = '" . $id1 . "'" );
            self::DbQuery("UPDATE player SET player_id=2320831 WHERE player_id = '" . $id2 . "'" );
            self::DbQuery("UPDATE player SET player_id=2320832 WHERE player_id = '" . $id3 . "'" );
            
            //global 
            self::DbQuery("UPDATE global SET global_value=2320829 WHERE global_value = '" . $id0 . "'" );
            self::DbQuery("UPDATE global SET global_value=2320830 WHERE global_value = '" . $id1 . "'" );
            self::DbQuery("UPDATE global SET global_value=2320831 WHERE global_value = '" . $id2 . "'" );
            self::DbQuery("UPDATE global SET global_value=2320832 WHERE global_value = '" . $id3 . "'" );
            
            //stats
            self::DbQuery("UPDATE stats SET stats_player_id=2320829 WHERE stats_player_id = '" . $id0 . "'" );
            self::DbQuery("UPDATE stats SET stats_player_id=2320830 WHERE stats_player_id = '" . $id1 . "'" );			
            self::DbQuery("UPDATE stats SET stats_player_id=2320831 WHERE stats_player_id = '" . $id2 . "'" );			
            self::DbQuery("UPDATE stats SET stats_player_id=2320832 WHERE stats_player_id = '" . $id3 . "'" );			
            
            // 'other' game specific tables. example:
            // tables specific to your schema that use player_ids
            self::DbQuery("UPDATE treasure_deck SET card_location_arg=2320829 WHERE card_location_arg = '" . $id0 . "'" );
            self::DbQuery("UPDATE treasure_deck SET card_location_arg=2320830 WHERE card_location_arg = '" . $id1 . "'" );
            self::DbQuery("UPDATE treasure_deck SET card_location_arg=2320831 WHERE card_location_arg = '" . $id2 . "'" );
            self::DbQuery("UPDATE treasure_deck SET card_location_arg=2320832 WHERE card_location_arg = '" . $id3 . "'" );

            self::DbQuery("UPDATE player_deck SET card_location_arg=2320829 WHERE card_location_arg = '" . $id0 . "'" );
            self::DbQuery("UPDATE player_deck SET card_location_arg=2320830 WHERE card_location_arg = '" . $id1 . "'" );
            self::DbQuery("UPDATE player_deck SET card_location_arg=2320831 WHERE card_location_arg = '" . $id2 . "'" );
            self::DbQuery("UPDATE player_deck SET card_location_arg=2320832 WHERE card_location_arg = '" . $id3 . "'" );
        
        }

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
//////////// 

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in forbiddenisland.action.php)
    */

    function moveAction( $tile_id, $pilot = false, $navigator = false, $heli_lift = false, $card_id = 0, $players = NULL, $rescue = false, $undo = false )
    {
        self::checkAction( 'move' );
 
        if ($rescue) {
            $player_id = $players[0];
        } else {
            $player_id = self::getCurrentPlayerId();
        }

        $target_player_id = $player_id;
        $player_tile_id = $this->getPlayerLocation($player_id);

        // check if legal move
        if ( !$heli_lift and !$pilot and !$navigator and !$undo) {
            // $possibleMoves = $this->getPossibleMoves($player_id)['move'];
            $possibleMoves = $this->getPossibleMoves2($player_id)['move'];
            if (!in_array($tile_id, $possibleMoves)) {
                return;
            }
        } elseif ( $navigator ) {
            $target_player_id = $players[0];
            $possibleMoves = $this->getPossibleNavigator($target_player_id)['navigator'][$target_player_id];
            if (!in_array($tile_id, $possibleMoves)) {
                return;
            }
        } elseif ( $undo ) {
            if ($this->getGameStateValue("undo_move_tile") == 0) {
                return;
            } else {
                $tile_id = $this->tiles->getCard($this->getGameStateValue("undo_move_tile"))['type'];
            }
        } else {
            $possibleHeliLift = $this->getPossibleHeliLift($player_id)['heli_lift'];
            if (!in_array($tile_id, $possibleHeliLift)) {
                return;
            }
        }

        $tile_name = $this->tile_list[$tile_id]['name'];
        $tiles = $this->tiles->getCardsOfType($tile_id);
        $tile = array_shift($tiles);
        $tiles = $this->tiles->getCardsOfType($player_tile_id);
        $prev_tile = array_shift($tiles);

        // move if action available
        if (($this->getGameStateValue("remaining_actions") > 0) or $heli_lift or $rescue or $undo) {

            // if (array_key_exists($tile_id, $possibleMoves)) {
            if (!$heli_lift && !$rescue) {
                if ($navigator) {
                    $sql = "UPDATE player SET location='$tile_id'
                            WHERE player_id='$target_player_id'";
                    self::DbQuery( $sql );
                    $message = clienttranslate('${player_name} moved ${target_name} to ${tile_name}');
                } else {
                    $sql = "UPDATE player SET location='$tile_id'
                            WHERE player_id='$player_id'";
                    self::DbQuery( $sql );
                    if ($undo) {
                        $message = clienttranslate('${player_name} undid last move');
                    } else {
                        $message = clienttranslate('${player_name} moved to ${tile_name}');
                    }
                }
            } else {
                foreach ($players as $x) {
                    $sql = "UPDATE player SET location='$tile_id'
                        WHERE player_id='$x'";
                    self::DbQuery( $sql );
                }
                if ($heli_lift) {
                    $message = clienttranslate('${player_name} played Helicopter Lift to ${tile_name}');
                } else {
                    $message = clienttranslate('${player_name} rescued pawn to ${tile_name}');
                }
            }

            // Notify
            self::notifyAllPlayers( "moveAction", $message, array(
                'player_id' => $player_id,
                'target_player_id' => $target_player_id,
                'player_tile_id' => $player_tile_id,
                'player_name' => self::getPlayerName($player_id),
                'target_name' => self::getPlayerName($target_player_id),
                'tile_id' => $tile_id,
                'tile_name' => $tile_name,
                'heli_lift' => $heli_lift,
                'navigator' => $navigator,
                'rescue' => $rescue,
                'undo' => $undo,
                'card_id' => $card_id,
                'players' => implode($players, ',')
            ) );

            if (!$heli_lift) {
                if ($undo) {
                    $this->incGameStateValue("remaining_actions", 1);
                    self::incStat(-1, "move", $player_id);
                } elseif (!$rescue) {
                    $this->incGameStateValue("remaining_actions", -1);
                    self::incStat(1, "move", $player_id);
                }
            } else {
                $this->treasure_deck->moveCard($card_id, 'discard');
                $this->updateCardCount();
                self::incStat(1, "heli_lift", $player_id);
            }

            if ($pilot) {
                $this->setGameStateValue("pilot_action", 0);
                self::incStat(1, "pilot", $player_id);
            }

            if ($navigator) {
                self::incStat(1, "navigator", $player_id);
            }

            if (!$heli_lift and !$pilot and !$navigator and !$rescue and !$undo) {
                $this->setGameStateValue("undo_move_tile", $prev_tile['id']);
            } else {
                $this->setGameStateValue("undo_move_tile", 0);
            }

        } else {
            throw new feException( "No remaining actions" );
        }

        $this->setNextState( 'move', $player_id ); 
        
    }

    function shoreUpAction( $tile_id, $bonus = false, $sandbags = false, $card_id = 0 )
    {
        self::checkAction( 'shore_up' );

        $player_id = self::getCurrentPlayerId();
        $player_tile_id = $this->getPlayerLocation($player_id);
        $tile_name = $this->tile_list[$tile_id]['name'];
        
        // check if shore up is possible
        if (! $sandbags ) {
            if (array_key_exists('shore_up', $this->getPossibleShoreUp($player_id))) {
                $possibleShoreUp = $this->getPossibleShoreUp($player_id)['shore_up'];  // TODO: cover case where function returns empty
                if (!in_array($tile_id, $possibleShoreUp)) {
                    return;
                }
            } else {
                return;
            }
        } else {
            if (array_key_exists('sandbags', $this->getPossibleSandbags($player_id))) {
                $possibleSandbags = $this->getPossibleSandbags()['sandbags'];
                if (!in_array($tile_id, $possibleSandbags)) {
                    return;
                }
            } else {
                return;
            }
        }

        // self::notifyAllPlayers( "log", "shoreUpAction", array(
        //     'player_id' => $player_id,
        //     'player_tile_id' => $player_tile_id,
        //     'tile_id' => $tile_id,
        //     'sandbags' => $sandbags,
        //     'bonus' => $bonus,
        // ) );

        if (($this->getGameStateValue("remaining_actions") > 0) or $sandbags or $bonus) {

            $tiles = $this->tiles->getCardsOfType($tile_id);
            $tile = array_shift($tiles);
            if ($tile['location'] == 'flooded') {

                $this->tiles->moveCard($tile['id'], 'unflooded', $tile['location_arg']);
                self::incStat(1, "tiles_shored_up");

                if ($sandbags) {
                    $message = clienttranslate( '${player_name} shored up ${tile_name} with Sandbags' );
                } elseif ($bonus) {
                    $message = clienttranslate( '${player_name} shored up ${tile_name} using Engineer bonus' );
                } else {
                    $message = clienttranslate( '${player_name} shored up ${tile_name}' );
                }
    
                self::notifyAllPlayers( "shoreUpAction", $message, array(
                    'player_id' => $player_id,
                    'player_tile_id' => $player_tile_id,
                    'player_name' => self::getPlayerName($player_id),
                    'tile_id' => $tile_id,
                    'tile_name' => $tile_name,
                    'sandbags' => $sandbags,
                    'card_id' => $card_id
                ) );

                if (!$sandbags and !$bonus) {
                    $this->incGameStateValue("remaining_actions", -1);
                    self::incStat(1, "shore_up", $player_id);
                }

                if ($sandbags) {
                    $this->treasure_deck->moveCard($card_id, 'discard');
                    $this->updateCardCount();
                    self::incStat(1, "sandbags", $player_id);
                }

                if ($bonus) {
                    self::incStat(1, "bonus_shoreup", $player_id);
                }
        
            }

            $this->setGameStateValue("undo_move_tile", 0);

        } else {
            throw new feException( "No remaining actions" );
        }

        $state = $this->gamestate->state();
        if (($state['name'] == 'playerActions') and 
            ($this->getAdventurer( $player_id ) == 'engineer') and 
            (count($possibleShoreUp) > 1) and 
            (!$bonus)) 
        {
            $this->gamestate->nextState( 'bonus_shoreup' );
        } else {
            $this->setNextState( 'shore_up', $player_id );
        }

    }

    function continue( )
    {
        self::checkAction( 'continue' );
        $player_id = self::getActivePlayerId();

        $this->setNextState( 'continue', $player_id); 

    }

    function skipAction( )
    {
        self::checkAction( 'skip' );

        $player_id = self::getActivePlayerId();

        $player_tile_id = $this->getPlayerLocation($player_id);

        $remaining_actions = $this->getGameStateValue("remaining_actions");
        $state = $this->gamestate->state();

        if (($remaining_actions > 0) and ($state['name'] == 'playerActions')) {
            self::incStat($remaining_actions, "skip", $player_id);
            $this->setGameStateValue("remaining_actions", 0);

            // Notify
            self::notifyAllPlayers( "skipAction", clienttranslate( '${player_name} ended their turn.' ), array(
                'player_id' => $player_id,
                'player_name' => self::getActivePlayerName(),
            ) );
        } 

        $this->setGameStateValue("undo_move_tile", 0);

        $this->setNextState( 'skip', $player_id, $end_turn = true); 
        
    }

    function discardTreasure( $id )
    {
        $this->gamestate->checkPossibleAction( 'discard' );

        $player_id = $this->getGameStateValue("discard_treasure_player");

        $card = $this->treasure_deck->getCard($id);
        $card_name = $this->treasure_list[$card['type']]['name'];
        $this->treasure_deck->moveCard($id, 'discard');
        self::incStat(1, "discard", $player_id);

        self::notifyAllPlayers( "discardTreasure", clienttranslate( '${player_name} discarded ${card_name}' ), array(
            'player_id' => $player_id,
            'player_name' => self::getPlayerName($player_id),
            'card' => $card,
            'card_name' => $card_name
        ) );
        
        $this->updateCardCount();

        $this->setNextState( 'discard', $player_id ); 

    }

    function giveTreasure( $id, $target_player_id )
    {
        self::checkAction( 'give_card' );

        $player_id = self::getActivePlayerId();
        $players = $this->loadPlayersBasicInfos();
        $target_player_name = $players[$target_player_id]['player_name'];

        if ($this->getGameStateValue("remaining_actions") > 0) {
            
            $card = $this->treasure_deck->getCard($id);
            $card_name = $this->treasure_list[$card['type']]['name'];

            if ($card['type'] != 'sanbags' and $card['type'] != 'heli_lift') {

                $this->treasure_deck->moveCard($id, 'hand', $target_player_id);
                
                $this->incGameStateValue("remaining_actions", -1);
                self::incStat(1, "give_card", $player_id);

                self::notifyAllPlayers( "giveTreasure", clienttranslate( '${player_name} gave ${card_name} to ${target_player_name}' ), array(
                    'player_id' => $player_id,
                    'target_player_id' => $target_player_id,
                    'player_name' => self::getActivePlayerName(),
                    'target_player_name' => $target_player_name,
                    'card' => $card,
                    'card_name' => $card_name
                ) );

                $this->updateCardCount();
            }

            $this->setGameStateValue("undo_move_tile", 0);

        } else {
            throw new feException( "No remaining actions" );
        }

        // $count = $this->treasure_deck->countCardsInLocation('hand', $target_player_id );
        // if ($count > 5) {
        //     $this->setGameStateValue("discard_treasure_player", $target_player_id);
        //     $this->gamestate->nextState( 'discard' );

        $this->setNextState( 'give_card', $target_player_id ); 

    }

    function captureTreasure()
    {
        self::checkAction( 'capture' );

        $player_id = self::getActivePlayerId();
        $player_tile_id = $this->getPlayerLocation($player_id);

        if ($this->getGameStateValue("remaining_actions") > 0) {

            // find treasure location & matching cards
            $treasure = $this->getLocationTreasure($player_tile_id);
            $cards = $this->getMatchingCards($player_id, $treasure);
            $tiles = $this->tiles->getCardsOfType($player_tile_id);
            $tile = array_shift($tiles);

            if ($treasure == 'none') {
                throw new BgaUserException( self::_("You are not on a treasure tile." ));
            } elseif ($this->getGameStateValue($treasure) != 0) {
                throw new BgaUserException( self::_("This treasure is already captured." ));
            } elseif (count($cards) < 4) {
                throw new BgaUserException( self::_("You need 4 matching treasure cards." ));
            } elseif ($tile['location'] == 'sunk') {
                throw new BgaUserException( self::_("The must not be sunk." ));
            } else {

                $cards = array_slice($cards, 0, 4);
                $func = function($c) {
                    return $c['id'];
                };
                $card_ids = array_map($func, $cards);
                $this->treasure_deck->moveCards($card_ids, 'discard');
                
                $this->setGameStateValue($treasure, $player_id);
                $this->incGameStateValue("remaining_actions", -1);
                self::incStat(1, "treasures_captured");
                self::incStat(1, "capture", $player_id);
                $treasure_name = $this->treasure_list[$treasure]['name'];

                self::notifyAllPlayers( "captureTreasure", clienttranslate( '${player_name} captured ${treasure_name}!!' ), array(
                    'player_id' => $player_id,
                    'player_name' => self::getActivePlayerName(),
                    'cards' => $cards,
                    'treasure' => $treasure,
                    'treasure_name' => $treasure_name
                    ) );

                $this->updateCardCount();

                if ($this->countTreasures() == 4) {
                    self::notifyAllPlayers( "captureAllTreasure", clienttranslate( "Your team has <b>All Four Treasures</b>!!  Return to <b>Fool's Landing</b> and play <b>Helicopter Lift</b> to escape the island and win the game!!" ), array(
                        ) );
                }

                $this->setGameStateValue("undo_move_tile", 0);

            }

        } else {
            throw new feException( "No remaining actions" );
        }

        $this->setNextState( 'capture', $player_id ); 

    }

    function playSpecial( $id, $player_id )
    {
        $this->gamestate->checkPossibleAction( 'special_action' );

        // $player_id = self::getActivePlayerId();
        $this->setGameStateValue("special_action_player", $player_id);

        $this->setGameStateValue("special_card_id", $id);
        $card = $this->treasure_deck->getCard($id);
        $card_name = $this->treasure_list[$card['type']]['name'];

        
        if ($card['type'] == 'sandbags') {

            $this->gamestate->nextState( 'sandbags' );

        } elseif ($card['type'] == 'heli_lift') {

            $this->gamestate->nextState( 'heli_lift' );

        }

    }

    function cancelSpecial()
    {
        $this->gamestate->checkPossibleAction( 'cancel' );

        $player_id = $this->getGameStateValue("discard_treasure_player");
        if (($player_id != 0) and ($this->treasure_deck->countCardsInLocation('hand', $player_id ) > 5)) {
            $this->setNextState( 'cancel', $player_id ); 
        } else {
            $this->setNextState( 'cancel' ); 
        }
    }

    function winGame()
    {
        self::checkAction( 'win' );
        if (self::isWinCondition()) {
            $this->setGameStateValue("players_win", 1);
            $this->gamestate->nextState( 'final' );
        }
    }

    function rescueZombie( $player_id )
    {
        if ($this->isGameLost()) {
            $this->gamestate->nextState( 'final' );
        } else {
            if ($this->getAdventurer( $player_id ) == 'pilot') {
                $result = $this->getPossibleHeliLift( $player_id );
                if (count($result['heli_lift']) > 0) {
                    $tile_id = array_shift($result['heli_lift']);
                    $this->moveAction($tile_id, $pilot = true, $navigator = false, $heli_lift = false, $card_id = 0, $players = array($player_id), $rescue = true, $undo = false );
                }
            } else {
                // $result = $this->getPossibleMoves( $player_id );
                $result = $this->getPossibleMoves2( $player_id );
                if (count($result['move']) > 0) {
                    $tile_id = array_shift($result['move']);
                    $this->moveAction($tile_id, $pilot = false, $navigator = false, $heli_lift = false, $card_id = 0, $players = array($player_id), $rescue = true, $undo = false );
                }
            }
        }
    }

    
//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */

    function argPlayerActions()
    {
        $player_treasure_cards = array();
        $players = $this->loadPlayersBasicInfos();
        foreach ( $players as $player_id => $player_info ) {
            $player_treasure_cards[$player_id] = $this->getTreasureCards( $player_id );
        }
        return array(
            'possibleActions' => self::getPossibleActions( self::getActivePlayerId() ),
            'remaining_actions' => $this->getGameStateValue("remaining_actions"),
            'player_treasure_cards' => $player_treasure_cards,
            'colocated_players' => self::getColocatedPlayers( self::getActivePlayerId() ),
            'playerLocations' => self::getPlayerLocations(),
            'isWinCondition' => self::isWinCondition(),
            'adventurer' => $this->getAdventurer( self::getActivePlayerId() ),
            'pilot_action' => $this->getGameStateValue("pilot_action"),
            'special_card_id' => $this->getGameStateValue("special_card_id"),
            'undo' => ($this->getGameStateValue("undo_move_tile") == 0) ? false : true,
            'flood_discards'=> $this->getFloodDiscards(),
        );
    }

    function argMultiPlayerActions()
    {
        $player_treasure_cards = array();
        $possibleActions = array();
        $adventurer = array();
        $players = $this->loadPlayersBasicInfos();
        foreach ( $players as $player_id => $player_info ) {
            $player_treasure_cards[$player_id] = $this->getTreasureCards( $player_id );
            $possibleActions[$player_id] = $this->getPossibleActions( $player_id );
            $adventurer[$player_id] = $this->getAdventurer( $player_id );
        }
        return array(
            'possibleActions' => $possibleActions,
            'remaining_actions' => $this->getGameStateValue("remaining_actions"),
            'player_treasure_cards' => $player_treasure_cards,
            'playerLocations' => self::getPlayerLocations(),
            'isWinCondition' => self::isWinCondition(),
            'adventurer' => $adventurer,
            'pilot_action' => $this->getGameStateValue("pilot_action"),
            'flood_discards'=> $this->getFloodDiscards(),
        );
    }

    function argDrawFloodCards()
    {
        return array(
            'remaining_flood_cards' => $this->getGameStateValue("remaining_flood_cards"),
            'flood_discards'=> $this->getFloodDiscards(),
        );
    }

    function argDiscardTreasure()
    {
        $player_treasure_cards = array();
        $players = $this->loadPlayersBasicInfos();
        foreach ( $players as $player_id => $player_info ) {
            $player_treasure_cards[$player_id] = $this->getTreasureCards( $player_id );
        }
        return array(
            'player_treasure_cards' => $player_treasure_cards,
            'discard_treasure_player' => $this->getGameStateValue("discard_treasure_player"),
            'flood_discards'=> $this->getFloodDiscards(),
        );
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    /*
        Here, you can create methods defined as "game state actions" (see "action" property in states.inc.php).
        The action method of state X is called everytime the current game state is set to X.
    */
    
    function stSetFloodCards()
    {
        $water_level = $this->getGameStateValue('water_level');
        $flood_cards = $this->getNumberFloodCards($water_level);
        $this->setGameStateValue("remaining_flood_cards", $flood_cards);
        $this->setNextState( 'set_flood' );

    }

    function stDrawFloodCards()
    {
        $remain_flood_cards = $this->getGameStateValue('remaining_flood_cards');

        $flood_card = $this->flood_deck->pickCardForLocation( 'deck', 'flood_area' );
        
        if ($flood_card == null) {
            $this->floodDeckReshuffle();
            $flood_card = $this->flood_deck->pickCardForLocation( 'deck', 'flood_area' );
        }

        $tiles = $this->tiles->getCardsOfType($flood_card['type']);
        $tile = array_shift($tiles);

        $remain_flood_cards -= 1;
        $this->setGameStateValue("remaining_flood_cards", $remain_flood_cards);
            
        $tile_name = $this->tile_list[$tile['type']]['name'];

        if ($tile['location'] == 'unflooded') {

            $this->tiles->moveCard($tile['id'], 'flooded', $tile['location_arg']);
            self::incStat(1, "tiles_flooded");

            self::notifyAllPlayers( "floodTile", clienttranslate( "${tile_name} has flooded!!" ), array(
                'tile_id' => $tile['type'],
                'flood_card_type' => $flood_card['type'],
                'tile_name' => $tile_name
            ) );

            $this->setGameStateValue("rescue_pawn_tile", 0);

            $current_tile = null;

        } elseif ($tile['location'] == 'flooded') {

            $this->tiles->moveCard($tile['id'], 'sunk', $tile['location_arg']);
            $this->flood_deck->moveCard($flood_card['id'], 'sunk');
            self::incStat(1, "tiles_sunk");

            self::notifyAllPlayers( "sinkTile", clienttranslate( "${tile_name} has sunk!!!" ), array(
                'tile_id' => $tile['type'],
                'flood_card_type' => $flood_card['type'],
                'tile_name' => $tile_name
            ) );

            // check for pawns to rescue

            $rescue_pawns = count($this->getPlayersAtLocation($tile['type']));
            if ( $rescue_pawns > 0 ) {
                $this->setGameStateValue("rescue_pawn_tile", $tile['id']);
            } else {
                $this->setGameStateValue("rescue_pawn_tile", 0);
            }
            $current_tile = $tile['type'];

        } else {
            throw new feException( "Error: Flood card drawn for sunk tile." );
        }

        $this->updateCardCount();

        if ($this->isGameLost($current_tile)) {
            $this->gamestate->nextState( 'final' );
        } else {
            $this->setNextState( 'draw_flood' );
        }

    }

    function stDrawTreasureCards()
    {
        $player_id = self::getActivePlayerId();

        $this->treasure_deck->autoreshuffle = true;
        $this->treasure_deck->autoreshuffle_trigger = array('obj' => $this, 'method' => 'treasureDeckReshuffle');
        $treasure_cards = $this->treasure_deck->pickCardsForLocation( 2, 'deck', 'hand', $player_id, false );

        $card_1 = array_shift($treasure_cards);
        $card_2 = array_shift($treasure_cards);

        $card_name_1 = $this->treasure_list[$card_1['type']]['name'];
        $card_name_2 = $this->treasure_list[$card_2['type']]['name'];

        $this->setGameStateValue("drawn_treasure_cards", 2);

        self::notifyAllPlayers( "drawTreasure", clienttranslate( '${player_name} drew ${card_name_1} and ${card_name_2} cards.' ), array(
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'card_1' => $card_1,
            'card_2' => $card_2,
            'card_name_1' => $card_name_1,
            'card_name_2' => $card_name_2,
        ) );

        $waters_rise = false;
        if ($card_1['type'] == 'waters_rise') {
            $this->waters_rise($card_1);
            $waters_rise = true;
            $this->treasure_deck->moveCard($card_1['id'], 'discard');
        }

        if ($card_2['type'] == 'waters_rise') {
            if ($waters_rise) {
                $this->waters_rise($card_2, $skip_reshuffle=true);
            } else {
                $this->waters_rise($card_2);
            }
            $this->treasure_deck->moveCard($card_2['id'], 'discard');
        }

        $this->updateCardCount();

        if ($this->isGameLost()) {
            $this->gamestate->nextState( 'final' );
        } else {
            $this->setNextState( 'draw_treasure', $player_id );
        }

    }

    function stNextPlayer()
    {
        // Active next player
        $player_id = self::activeNextPlayer();

        $this->setGameStateValue("remaining_actions", 3);
        $this->setGameStateValue("drawn_treasure_cards", 0);
        $this->setGameStateValue("pilot_action", 1);
        $this->setGameStateValue("undo_move_tile", 0);

        self::incStat(1, "turns_number");
        self::incStat(1, "turns_number", $player_id);

        $this->giveExtraTime($player_id);

        $this->setNextState( 'next_turn', $player_id );

    }

    function stSpecialAction()
    {
        // Setup multiactive players (active plus players with special action cards)
        // $players = $this->getMultiactivePlayers();
        $players = array($this->getGameStateValue("special_action_player"));
        $this->gamestate->setPlayersMultiactive( $players, 'draw_treasure', $bExclusive = true );
    }

    function stRescuePawn()
    {
        if ($this->isGameLost()) {
            $this->gamestate->nextState( 'final' );
        } else {
            // Setup multiactive players (players with pawns on sunk tile)
            $tile = $this->tiles->getCard($this->getGameStateValue("rescue_pawn_tile"));
            $rescue_pawns = $this->getPlayersAtLocation($tile['type']);

            $players = array_map( function($p){ return $p['player_id']; }, $rescue_pawns );

            foreach ( $players as $player_id => $player_info ) {
                if ($this->getAdventurer( $player_id ) == 'pilot') {
                    $move_count = $this->getPossibleHeliLift($player_id)['heli_lift'];
                } else {
                    // $move_count = $this->getPossibleMoves( $player_id );
                    $move_count = $this->getPossibleMoves2( $player_id );
                }
                if ($move_count == 0) {
                    // no possible move for this pawn
                    $this->gamestate->nextState( 'final' );
                    break;
                } else {
                    $this->gamestate->setPlayersMultiactive( $players, 'draw_flood', $bExclusive = true );
                }
            }

        }
    }

    function stDiscardTreasure()
    {
        // Setup multiactive players (active plus players with special action cards)
        // $players = $this->getMultiactivePlayers();
        $players = array($this->getGameStateValue("discard_treasure_player"));
        $this->gamestate->setPlayersMultiactive( $players, 'draw_treasure', $bExclusive = true );
    }

    function stFinal()
    {
        $players = $this->loadPlayersBasicInfos();
        if ($this->getGameStateValue("players_win") != 0) {
            foreach ( $players as $player_id => $player_info ) {
                self::DbQuery( "UPDATE player SET player_score=1 WHERE player_id=$player_id" );
            }
            self::setStat(true, "players_won");
            $title = clienttranslate("The Players Won!!");
        } else {
            foreach ( $players as $player_id => $player_info ) {
                self::DbQuery( "UPDATE player SET player_score=0 WHERE player_id=$player_id" );
            }
            $title = clienttranslate("The Players Lost");
        }

        $table = [];
        $table[] = array(clienttranslate("Treasures <b>Captured</b>"), $this->countTreasures());
        $table[] = array(" ", " ");

        $all_treasures = array('earth', 'air', 'fire', 'ocean');
        foreach( $all_treasures as $treasure ) {

            $treasureName = $this->treasure_list[$treasure]['name'];
            $captured = ($this->getGameStateValue($treasure) != 0) ? true : false;
            $captured_text = ($captured) ? clienttranslate('<p style="color:green; font-weight:bold">Captured</p>') : clienttranslate('Not Captured');

            $table[] = array( 
                array(
                    'str' => '     '.clienttranslate('Treasure <b>${treasure}</b>'), 
                    'args' => array('treasure' => $treasureName)
                ), 
                $captured_text
            );
            $count = $this->checkTreasureTiles($treasure);
            $count_text = (($count == 0) and (!$captured)) ? '<p style="color:red; font-weight:bold">0</p>' : $count;
            $table[] = array( 
                array(
                    // 'str' => '     '.clienttranslate('${treasure} Tiles Remaining'), 
                    'str' => '     '.clienttranslate('Tiles Remaining'), 
                    'args' => array('treasure' => $treasureName)
                ), 
                $count_text
            );
            $table[] = array(" ", " ");
        }

        switch($this->checkFoolsLanding()) {
            case 'sunk':
                $status = clienttranslate('<p style="color:red; font-weight:bold">Sunk</p>');
                break;
            case 'flooded':
                $status = clienttranslate('Flooded');
                break;
            default:
                $status = clienttranslate('Unflooded');
                break;
        }

        $table[] = array(clienttranslate("<b>Fools' Landing<b>"), $status);

        $water_level = $this->getGameStateValue('water_level');
        $water_level_text = ($water_level >= 10) ? '<p style="color:red; font-weight:bold">10</p>' : $water_level;
        $table[] = array(clienttranslate("<b>Water Level<b>"), $water_level_text);

        $table[] = array(clienttranslate("Island Tiles Sunk"),  
            $this->tiles->countCardsInLocation("sunk")
        );

        $table[] = array(clienttranslate("Island Tiles Remaining"),  
            $this->tiles->countCardsInLocation("unflooded") +
            $this->tiles->countCardsInLocation("flooded")
        );

        $this->notifyAllPlayers("tableWindow", '', array (
            "id" => 'finalresults',
            "title" => $title,
            "table" => $table,
            // "header" => clienttranslate('Final Scoring'),
            "closing" => clienttranslate("Close"),
            "footer" => '' 
        ));

        $this->gamestate->nextState( 'end' );
        // $this->gamestate->nextState( 'debug' );
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Zombie
////////////

    /*
        zombieTurn:
        
        This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
        You can do whatever you want in order to make sure the turn of this player ends appropriately
        (ex: pass).
        
        Important: your zombie code will be called when the player leaves the game. This action is triggered
        from the main site and propagated to the gameserver from a server, not from a browser.
        As a consequence, there is no current player associated to this action. In your zombieTurn function,
        you must _never_ use getCurrentPlayerId() or getCurrentPlayerName(), otherwise it will fail with a "Not logged" error message. 
    */

    function zombieTurn( $state, $active_player )
    {
    	$statename = $state['name'];
    	
        if ($state['type'] === "activeplayer") {

            switch ($statename) {
                case 'playerActions':
                default:
                    $this->skipAction();
                	break;
            }

            return;

        } elseif ($state['type'] === "multipleactiveplayer") {

            switch ($statename) {
                case 'discardTreasure':
                    $cards = $this->getTreasureCards( $active_player );
                    $card = array_shift( $cards );
                    $this->discardTreasure( $card['id'] );
                    break;

                case 'rescuePawn';
                // Make sure player is in a non blocking status for role turn
                $this->rescueZombie( $active_player );
                break;
            default:
                break;
            }

            return;

        }

        throw new feException( "Zombie mode not supported at this game state: ".$statename );
    }
    
///////////////////////////////////////////////////////////////////////////////////:
////////// DB upgrade
//////////

    /*
        upgradeTableDb:
        
        You don't have to care about this until your game has been published on BGA.
        Once your game is on BGA, this method is called everytime the system detects a game running with your old
        Database scheme.
        In this case, if you change your Database scheme, you just have to apply the needed changes in order to
        update the game database and allow the game to continue to run with your new version.
    
    */
    
    function upgradeTableDb( $from_version )
    {
        // $from_version is the current version of this game database, in numerical form.
        // For example, if the game was running with a release of your game named "140430-1345",
        // $from_version is equal to 1404301345
        
        // Example:
//        if( $from_version <= 1404301345 )
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "ALTER TABLE DBPREFIX_xxxxxxx ....";
//            self::applyDbUpgradeToAllDB( $sql );
//        }
//        if( $from_version <= 1405061421 )
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "CREATE TABLE DBPREFIX_xxxxxxx ....";
//            self::applyDbUpgradeToAllDB( $sql );
//        }
//        // Please add your future database scheme changes here
//
//


    }    
}