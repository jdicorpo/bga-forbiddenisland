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
               "air" => 13,
               "fire" => 14,
               "earth" => 15,
               "ocean" => 16,
               "players_win" => 17,
               "pilot_action" => 18,
            //      ...
            //    "my_first_game_variant" => 100,
            //    "my_second_game_variant" => 101,
            //      ...
            
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
        foreach( $players as $player_id => $player )
        {
            $player_card = $this->player_deck->pickCardForLocation( 'deck', 'hand', $player_id );
            $adventurer = $player_card['type'];
            $location = $this->player_list[$adventurer]['location'];
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
        //self::setGameStateInitialValue( 'my_first_global_variable', 0 );
        self::setGameStateInitialValue( 'remaining_actions', 3 );
        self::setGameStateInitialValue( 'remaining_flood_cards', 6 );
        self::setGameStateInitialValue( 'water_level', 1 );
        self::setGameStateInitialValue( 'air', 0 );
        self::setGameStateInitialValue( 'fire', 0 );
        self::setGameStateInitialValue( 'earth', 0 );
        self::setGameStateInitialValue( 'ocean', 0 );
        self::setGameStateInitialValue( 'players_win', 0 );
        self::setGameStateInitialValue( 'pilot_action', 1 );
        
        // Init game statistics
        // (note: statistics used in this file must be defined in your stats.inc.php file)
        //self::initStat( 'table', 'table_teststat1', 0 );    // Init a table statistics
        //self::initStat( 'player', 'player_teststat1', 0 );  // Init a player statistics (for all players)

        // TODO: setup the initial game situation here
       
        $card_deck = array();
        foreach ($this->tile_list as $x => $x_value) {
            // $card_deck[] = array( 'type' => $x, 'type_arg' =>  $x_value['img_id'], 'nbr' => 1);
            $card_deck[] = array( 'type' => $x, 'type_arg' =>  1, 'nbr' => 1);
        }

        $this->tiles->createCards( $card_deck, 'deck' );
        $this->tiles->shuffle( 'deck' );

        for( $x=1; $x<=6; $x++ )
        {
            for( $y=1; $y<=6; $y++ )
            {
                $tile_location = $x . "_" . $y;
                $tile_value = $x*10 + $y;
                if (!in_array($tile_location, $this->not_in_map)) {
                    $tile = $this->tiles->pickCardForLocation( 'deck', 'unflooded', $tile_value);
                }
            }        
        }

        // setup tokens

        // $result = array();
        // $sql = "INSERT INTO tokens (token_id, location) VALUES ";
        // $values = array ();
        // foreach ( $this->adventurers as $pawn_id => $pawn_info ) {
        //     $location = $pawn_info['location'];
        //     $values [] = "('$pawn_id', '$location')";
        // }
        // $sql .= implode($values, ',');
        // $this->DbQuery($sql);

        // $flood_cards = $this->flood_deck->pickCardsForLocation( 6, 'deck', 'flooded' );

        // Activate first player (which is in general a good idea :) )
        $this->activeNextPlayer();

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
        $result['not_in_map'] = $this->not_in_map;

        $result['remaining_actions'] = $this->getGameStateValue("remaining_actions");
        $result['water_level'] = $this->getGameStateValue("water_level");
        $result['remaining_flood_cards'] = $this->getGameStateValue("remaining_flood_cards");

        $result['air'] = $this->getGameStateValue("air");
        $result['fire'] = $this->getGameStateValue("fire");
        $result['earth'] = $this->getGameStateValue("earth");
        $result['ocean'] = $this->getGameStateValue("ocean");

        $result['pilot_action'] = $this->getGameStateValue("pilot_action");

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

        return 0;
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
            $result = array_merge($result, $this->getPossibleMoves( $player_id ));
            $result = array_merge($result, $this->getPossibleShoreUp( $player_id ));
            $result = array_merge($result, $this->getPossibleSandbags());
            $result = array_merge($result, $this->getPossibleHeliLift( $player_id ));
            return $result;

        }

        // Get the list of possible moves (x => y => true)
        function getPossibleMoves( $player_id )
        {
            $player_tile_id = $this->getPlayerLocation($player_id);
            $result = array();

            foreach ($this->tiles->getCardsInLocation('unflooded') as $id => $tile ) {
                    if ( $this->isTileAdjacent($tile['type'], $player_tile_id) )
                    {
                        $result['move'][] = $tile['type'];
                    }
            }

            foreach ($this->tiles->getCardsInLocation('flooded') as $id => $tile ) {
                if ( $this->isTileAdjacent($tile['type'], $player_tile_id) )
                {
                    $result['move'][] = $tile['type'];
                }
            }
                    
            return $result;
        }

        function getPossibleShoreUp( $player_id )
        {
            $player_tile_id = $this->getPlayerLocation($player_id);
            $result = array();

            foreach ($this->tiles->getCardsInLocation('flooded') as $id => $tile ) {
                if ( $this->isTileAdjacent($tile['type'], $player_tile_id) or ($tile['type'] == $player_tile_id))
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
                if ( $tile['type'] != $player_tile_id ) {
                    $result['heli_lift'][] = $tile['type'];
                }
            }
            foreach ($this->tiles->getCardsInLocation('flooded') as $id => $tile ) {
                if ( $tile['type'] != $player_tile_id ) {
                    $result['heli_lift'][] = $tile['type'];
                }
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

        function isTileAdjacent( $tile_id, $player_tile_id )
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
            elseif (($this->getAdventurer() == 'explorer') and 
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

        function getAdventurer() {
            $player_id = self::getActivePlayerId();
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
            // $id = $this->tiles->getCardsOfType($tile_id)[0];
           
            // return $this->tiles->getCard($id)['location_arg'];
            // return $this->tiles->getCardsOfType($tile_id);

            // return $this->tiles->getCardsOfTypeInLocation($tile_id,1,'unflooded');
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

        function waters_rise($card, $skip_reshuffle = false) {
            $player_id = self::getActivePlayerId();
            $water_level = $this->incGameStateValue("water_level", 1);

            $new_flood_draw = $this->getNumberFloodCards($water_level);

            $flood_discards = $this->flood_deck->countCardsInLocation("flood_area");

            if ($skip_reshuffle) {
                $message = "Waters Rise! Second card drawn in a row. Water level is now ".$water_level.". ".$new_flood_draw." flood cards will be drawn at the end of each turn.";
            } else {
                $this->flood_deck->shuffle("flood_area");
                // $this->flood_deck->moveAllCardsInLocationKeepOrder("flood_area", "deck");
                $cards = $this->flood_deck->getCardsInLocation( "flood_area");
                foreach ($cards as $id => $value) {
                    $this->flood_deck->insertCardOnExtremePosition( $id, 'deck', 'top');
                }
                $message = "Waters Rise!  Reshuffling ".$flood_discards." flood discards on top of deck. Water level is now ".$water_level.".  ".$new_flood_draw." flood cards will be drawn at the end of each turn.";           
            }

            self::notifyAllPlayers( "watersRise", clienttranslate( $message ), array(
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

        function treasureDeckReshuffle () {
            self::notifyAllPlayers( "reshuffleTreasureDeck", clienttranslate( 'Treasure deck reshuffled.' ), array(
            ) );
        }

        function isWinCondition() {
            $nPlayers = count($this->getPlayersAtLocation('fools_landing'));
            $nTreaures = ($this->getGameStateValue('air') != 0) ? 1 : 0;
            $nTreaures += ($this->getGameStateValue('earth') != 0) ? 1 : 0 ;
            $nTreaures += ($this->getGameStateValue('fire') != 0) ? 1 : 0;
            $nTreaures += ($this->getGameStateValue('ocean') != 0) ? 1 : 0;

            return (($nPlayers == $this->getPlayersNumber()) and ($nTreaures == 4)) ? true : false;
        }

        function isGameLost() {

            // check if water level is 10 or greater
            $water_level = $this->getGameStateValue("water_level");
            if ($water_level >= 10) {
                return true;
            }

            // check if Fool's Landing is sunk
            $tiles = $this->tiles->getCardsOfType('fools_landing');
            $tile = array_shift($tiles);
            if ($tile['location'] == 'sunk') {
                return true;
            }

            // check if tiles are sunk for unclaimed treasure
            $all_treasures = array('earth', 'air', 'fire', 'ocean');
            foreach($all_treasures as $treasure) {
                if ( $this->getGameStateValue($treasure) != 0 ) {
                    $tile_id_0 = $this->treasure_list[$treasure]['tiles'][0];
                    $tiles = $this->tiles->getCardsOfType($tile_id_0);
                    $tile_0 = array_shift($tiles);
                    $tile_id_1 = $this->treasure_list[$treasure]['tiles'][1];
                    $tiles = $this->tiles->getCardsOfType($tile_id_1);
                    $tile_1 = array_shift($tiles);
                    if (($tile_0['location'] == 'sunk') and ($tile_1['location'] == 'sunk'))  {
                        return true;
                    }
                }
            }

            return false;
        }
    
        function debugFlood($tile_id) {

            $tiles = $this->tiles->getCardsOfType($tile_id);
            $tile = array_shift($tiles);
            $this->tiles->moveCard($tile['id'], 'flooded', $tile['location_arg']);

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

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
//////////// 

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in forbiddenisland.action.php)
    */

    function moveAction( $tile_id, $pilot = false, $heli_lift = false, $card_id = 0, $players = NULL )
    {
        self::checkAction( 'move' );

        $player_id = self::getActivePlayerId();

        $player_tile_id = $this->getPlayerLocation($player_id);
        $tile_name = $this->tile_list[$tile_id]['name'];
        if ( !$heli_lift and !$pilot ) {
            $possibleMoves = $this->getPossibleMoves($player_id)['move'];
            if (!in_array($tile_id, $possibleMoves)) {
                return;
            }
        } else {
            $possibleHeliLift = $this->getPossibleHeliLift($player_id)['heli_lift'];
            if (!in_array($tile_id, $possibleHeliLift)) {
                return;
            }
        }

        if ($this->getGameStateValue("remaining_actions") > 0) {

            // if (array_key_exists($tile_id, $possibleMoves)) {
            if (!$heli_lift ) {
                $sql = "UPDATE player SET location='$tile_id'
                        WHERE player_id='$player_id'";
                self::DbQuery( $sql );
                $message = '${player_name} moved to ${tile_name}';
            } else {
                foreach ($players as $x) {
                    $sql = "UPDATE player SET location='$tile_id'
                        WHERE player_id='$x'";
                    self::DbQuery( $sql );
                }
                $message = '${player_name} played Helicopter Lift to ${tile_name}';
            }

                // $this->gamestate->nextState( 'action' );
            // } else {
            // }

            // Notify
            self::notifyAllPlayers( "moveAction", clienttranslate( $message ), array(
                'player_id' => $player_id,
                'player_tile_id' => $player_tile_id,
                'player_name' => self::getActivePlayerName(),
                'tile_id' => $tile_id,
                'tile_name' => $tile_name,
                'heli_lift' => $heli_lift,
                'card_id' => $card_id,
                'players' => implode($players, ',')
            ) );

            if (!$heli_lift) {
                $this->incGameStateValue("remaining_actions", -1);
            } else {
                $this->treasure_deck->moveCard($card_id, 'discard');
            }

            if ($pilot) {
                $this->setGameStateValue("pilot_action", 0);
            }

        } else {
            throw new feException( "No remaining actions" );
        }

        if ($this->getGameStateValue("remaining_actions") > 0) {
            $this->gamestate->nextState( 'action' );

        } else {
            $this->gamestate->nextState( 'draw_treasure' );
        }

    }

    function shoreUpAction( $tile_id, $bonus = false, $sandbags = false, $card_id = 0 )
    {
        self::checkAction( 'shore_up' );

        $player_id = self::getActivePlayerId();
        $player_tile_id = $this->getPlayerLocation($player_id);
        $tile_name = $this->tile_list[$tile_id]['name'];
        $possibleShoreUp = $this->getPossibleShoreUp($player_id)['shore_up'];

        // check if shore up is possible
        if (! $sandbags ) {
            if (!in_array($tile_id, $possibleShoreUp)) {
                return;
            }
        } else {
            $possibleSandbags = $this->getPossibleSandbags()['sandbags'];
            if (!in_array($tile_id, $possibleSandbags)) {
                return;
            }
        }

        if ($this->getGameStateValue("remaining_actions") > 0) {

            $tiles = $this->tiles->getCardsOfType($tile_id);
            $tile = array_shift($tiles);
            if ($tile['location'] == 'flooded') {

                $this->tiles->moveCard($tile['id'], 'unflooded', $tile['location_arg']);
    
                self::notifyAllPlayers( "shoreUpAction", clienttranslate( '${player_name} shored up ${tile_name}' ), array(
                    'player_id' => $player_id,
                    'player_tile_id' => $player_tile_id,
                    'player_name' => self::getActivePlayerName(),
                    'tile_id' => $tile_id,
                    'tile_name' => $tile_name,
                    'sandbags' => $sandbags,
                    'card_id' => $card_id
                ) );

                if (!$sandbags and !$bonus) {
                    $this->incGameStateValue("remaining_actions", -1);
                }

                if ($sandbags) {
                    // $card = $this->treasure_deck->getCard($card_id);
                    $this->treasure_deck->moveCard($card_id, 'discard');
                }
        
            }

        } else {
            throw new feException( "No remaining actions" );
        }

        if (($this->getAdventurer() == 'engineer') and (count($possibleShoreUp) > 1) and (!$bonus)) {
            $this->gamestate->nextState( 'bonus_shoreup' );
        } elseif ($this->getGameStateValue("remaining_actions") > 0) {
            $this->gamestate->nextState( 'action' );
        } else {
            $this->gamestate->nextState( 'draw_treasure' );
        }

    }

    function skipAction( )
    {
        self::checkAction( 'skip' );

        $player_id = self::getActivePlayerId();

        // $possibleMoves = $this->getPossibleMoves($player_id);
        $player_tile_id = $this->getPlayerLocation($player_id);

        if ($this->getGameStateValue("remaining_actions") > 0) {
            $this->incGameStateValue("remaining_actions", -1);
            // Notify
            self::notifyAllPlayers( "skipAction", clienttranslate( '${player_name} skipped an action' ), array(
                'player_id' => $player_id,
                'player_name' => self::getActivePlayerName(),
            ) );
        } else {
            throw new feException( "No remaining actions" );
        }

        if ($this->getGameStateValue("remaining_actions") > 0) {
            $this->gamestate->nextState( 'action' );
        } else {
            $this->gamestate->nextState( 'draw_treasure' );
        }

    }

    function discardTreasure( $id )
    {
        self::checkAction( 'discard' );

        $player_id = self::getActivePlayerId();

        $card = $this->treasure_deck->getCard($id);
        $card_name = $this->treasure_list[$card['type']]['name'];
        $this->treasure_deck->moveCard($id, 'discard');

        self::notifyAllPlayers( "discardTreasure", clienttranslate( '${player_name} discarded ${card_name}' ), array(
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'card' => $card,
            'card_name' => $card_name
        ) );

        $count = $this->treasure_deck->countCardsInLocation('hand', $player_id );
        if ($count > 5) {
            $this->gamestate->nextState( 'discard' );
        } else {
            $this->gamestate->nextState( 'set_flood' );
        }

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
            $this->treasure_deck->moveCard($id, 'hand', $target_player_id);
            
            $this->incGameStateValue("remaining_actions", -1);
            self::notifyAllPlayers( "giveTreasure", clienttranslate( '${player_name} gave ${card_name} to ${target_player_name}' ), array(
                'player_id' => $player_id,
                'target_player_id' => $target_player_id,
                'player_name' => self::getActivePlayerName(),
                'target_player_name' => $target_player_name,
                'card' => $card,
                'card_name' => $card_name
            ) );

            // TODO: need to check if target player must discard
        } else {
            throw new feException( "No remaining actions" );
        }

        if ($this->getGameStateValue("remaining_actions") > 0) {
            $this->gamestate->nextState( 'action' );
        } else {
            $this->gamestate->nextState( 'draw_treasure' );
        }

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

            if (($treasure != 'none') && ($this->getGameStateValue($treasure) == 0) 
                && (count($cards) >= 4) && ($tile['location'] == 'unflooded')) {

                $cards = array_slice($cards, 0, 4);
                $func = function($c) {
                    return $c['id'];
                };
                $card_ids = array_map($func, $cards);
                $this->treasure_deck->moveCards($card_ids, 'discard');
                
                $this->setGameStateValue($treasure, $player_id);
                $this->incGameStateValue("remaining_actions", -1);

                self::notifyAllPlayers( "captureTreasure", clienttranslate( '${player_name} captured ${treasure}!!' ), array(
                    'player_id' => $player_id,
                    'player_name' => self::getActivePlayerName(),
                    'cards' => $cards,
                    'treasure' => $treasure
                    ) );
            } else {
                throw new feException( "Invalid action" );
            }
        } else {
            throw new feException( "No remaining actions" );
        }

        if ($this->getGameStateValue("remaining_actions") > 0) {
            $this->gamestate->nextState( 'action' );
        } else {
            $this->gamestate->nextState( 'draw_treasure' );
        }

    }

    function playSpecial( $id, $player_id )
    {
        self::checkAction( 'special_action' );

        // $player_id = self::getActivePlayerId();

        $card = $this->treasure_deck->getCard($id);
        $card_name = $this->treasure_list[$card['type']]['name'];

        
        if ($card['type'] == 'sandbags') {

            $this->gamestate->nextState( 'sandbags' );

        } elseif ($card['type'] == 'heli_lift') {

            $this->gamestate->nextState( 'heli_lift' );

        }

        // self::notifyAllPlayers( "discardTreasure", clienttranslate( '${player_name} discarded ${card_name}' ), array(
        //     'player_id' => $player_id,
        //     'player_name' => self::getActivePlayerName(),
        //     'card' => $card,
        //     'card_name' => $card_name
        // ) );

        // $count = $this->treasure_deck->countCardsInLocation('hand', $player_id );
        // if ($count > 5) {
        //     $this->gamestate->nextState( 'discard' );
        // } else {
        //     $this->gamestate->nextState( 'set_flood' );
        // }

    }
    function cancelSpecial()
    {
        self::checkAction( 'cancel' );
        $this->gamestate->nextState( 'cancel' );
    }

    function winGame()
    {
        // self::checkAction( 'cancel' );
        if (self::isWinCondition()) {
            $this->setGameStateValue("players_win", 1);
            $this->gamestate->nextState( 'final' );
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
        // TODO: refactor this...
        return array(
            'possibleActions' => self::getPossibleActions( self::getActivePlayerId() ),
            'remaining_actions' => $this->getGameStateValue("remaining_actions"),
            'player_treasure_cards' => self::getTreasureCards( self::getActivePlayerId() ),
            'colocated_players' => self::getColocatedPlayers( self::getActivePlayerId() ),
            'playerLocations' => self::getPlayerLocations(),
            'isWinCondition' => self::isWinCondition(),
            'adventurer' => $this->getAdventurer(),
            'pilot_action' => $this->getGameStateValue("pilot_action")
        );
    }

    function argDrawFloodCards()
    {
        return array(
            'remaining_flood_cards' => $this->getGameStateValue("remaining_flood_cards")
        );
    }

    function argDiscardTreasure()
    {
        return array(
            'player_treasure_cards' => self::getTreasureCards( self::getActivePlayerId() )
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
        $this->gamestate->nextState( 'draw_flood' );

    }

    function stDrawFloodCards()
    {
        $remain_flood_cards = $this->getGameStateValue('remaining_flood_cards');

        $flood_card = $this->flood_deck->pickCardForLocation( 'deck', 'flood_area' );
        $tiles = $this->tiles->getCardsOfType($flood_card['type']);
        $tile = array_shift($tiles);
        $tile_name = $this->tile_list[$tile['type']]['name'];

        $remain_flood_cards -= 1;
        $this->setGameStateValue("remaining_flood_cards", $remain_flood_cards);

        if ($tile['location'] == 'unflooded') {

            $this->tiles->moveCard($tile['id'], 'flooded', $tile['location_arg']);

            self::notifyAllPlayers( "floodTile", clienttranslate( "${tile_name} flooded!!" ), array(
                'tile_id' => $tile['type'],
                'flood_card_type' => $flood_card['type'],
                'tile_name' => $tile_name
            ) );

            if ($this->isGameLost()) {
                $this->gamestate->nextState( 'final' );
            } elseif ($remain_flood_cards > 0) {
                $this->gamestate->nextState( 'draw_flood' );
            } else {
                $this->gamestate->nextState( 'next_player' );
            }

        } elseif ($tile['location'] == 'flooded') {

            $this->tiles->moveCard($tile['id'], 'sunk', $tile['location_arg']);
            $this->flood_deck->moveCard($flood_card['id'], 'sunk');

            self::notifyAllPlayers( "sinkTile", clienttranslate( "${tile_name} sank!!!" ), array(
                'tile_id' => $tile['type'],
                'flood_card_type' => $flood_card['type'],
                'tile_name' => $tile_name
            ) );

            // check for pawns to rescue

            $rescue_pawns = $this->getPlayersAtLocation($tile['type']);
            foreach ($rescue_pawns as $player_id => $value) {
                $possibleMoves = $this->getPossibleMoves($player_id);
                $player_tile_id = $this->getPlayerLocation($player_id);
                if (count($possibleMoves['move']) > 0) {
                    $new_tile_id = array_shift($possibleMoves['move']);
                    $tile_name = $this->tile_list[$new_tile_id]['name'];
                    $sql = "UPDATE player SET location='$new_tile_id'
                        WHERE player_id='$player_id'";
                    self::DbQuery( $sql );

                    self::notifyAllPlayers( "moveAction", clienttranslate( '${player_name} moved to ${tile_name}' ), array(
                        'player_id' => $player_id,
                        'player_tile_id' => $player_tile_id,
                        'player_name' => self::getActivePlayerName(),
                        'tile_id' => $new_tile_id,
                        'tile_name' => $tile_name
                    ) );
                } else {
                    // pawn cannot be rescued, p[ayers lose
                    $this->gamestate->nextState( 'final' );
                }
            }

            if ($this->isGameLost()) {
                $this->gamestate->nextState( 'final' );
            } elseif ($remain_flood_cards > 0) {
                $this->gamestate->nextState( 'draw_flood' );
            } else {
                $this->gamestate->nextState( 'next_player' );
            }

        } else {
            throw new feException( "Error: Flood card drawn for sunk tile." );
        }

        // $this->gamestate->nextState( 'nextPlayer' );

    }

    function stDrawTreasureCards()
    {
        $player_id = self::getActivePlayerId();

        $this->treasure_deck->autoreshuffle = true;
        $treasure_cards = $this->treasure_deck->pickCardsForLocation( 2, 'deck', 'hand', $player_id, false );

        $card_1 = array_shift($treasure_cards);
        $card_2 = array_shift($treasure_cards);

        $card_name_1 = $this->treasure_list[$card_1['type']]['name'];
        $card_name_2 = $this->treasure_list[$card_2['type']]['name'];

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

        $count = $this->treasure_deck->countCardsInLocation('hand', $player_id );
        if ($count > 5) {
            $this->gamestate->nextState( 'discard' );
        } else {
            $this->gamestate->nextState( 'set_flood' );
        }
        

    }

    function stNextPlayer()
    {
        // Active next player
        $player_id = self::activeNextPlayer();

        $this->setGameStateValue("remaining_actions", 3);
        $this->setGameStateValue("pilot_action", 1);

        $this->gamestate->nextState( 'next_turn' );

    }

    function stFinal()
    {
        $players = $this->loadPlayersBasicInfos();
        if ($this->getGameStateValue("players_win") != 0) {
            foreach ( $players as $player_id => $player_info ) {
                self::DbQuery( "UPDATE player SET player_score=1 WHERE player_id=$player_id" );
            }
            // self::DbQuery( "UPDATE player SET player_score=player_score+2 WHERE player_id='".self::getActivePlayerId()."'" );
        } else {
            foreach ( $players as $player_id => $player_info ) {
                self::DbQuery( "UPDATE player SET player_score=0 WHERE player_id=$player_id" );
            }
        }

        $this->gamestate->nextState( 'end' );
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
                default:
                    $this->gamestate->nextState( "zombiePass" );
                	break;
            }

            return;
        }

        if ($state['type'] === "multipleactiveplayer") {
            // Make sure player is in a non blocking status for role turn
            $this->gamestate->setPlayerNonMultiactive( $active_player, '' );
            
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
