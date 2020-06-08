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
 * forbiddenisland.action.php
 *
 * forbiddenisland main action entry point
 *
 *
 * In this file, you are describing all the methods that can be called from your
 * user interface logic (javascript).
 *       
 * If you define a method "myAction" here, then you can call it from your javascript code with:
 * this.ajaxcall( "/forbiddenisland/forbiddenisland/myAction.html", ...)
 *
 */
  
  
class action_forbiddenisland extends APP_GameAction
{ 
  // Constructor: please do not modify
     public function __default()
    {
        if( self::isArg( 'notifwindow') )
        {
          $this->view = "common_notifwindow";
            $this->viewArgs['table'] = self::getArg( "table", AT_posint, true );
        }
        else
        {
          $this->view = "forbiddenisland_forbiddenisland";
          self::trace( "Complete reinitialization of board game" );
        }
    } 
    
    public function moveAction()
    {
        self::setAjaxMode();     
        $tile_id = self::getArg( "tile_id", AT_alphanum, true );
        $heli_lift = self::getArg( "heli_lift", AT_bool, false, false );
        $pilot = self::getArg( "pilot", AT_bool, false, false );
        $navigator = self::getArg( "navigator", AT_bool, false, false );
        $rescue = self::getArg( "rescue", AT_bool, false, false );
        $card_id = self::getArg( "card_id", AT_alphanum, false, 0 );
        $players_raw = self::getArg( "players", AT_numberlist, false, null );
        
        // Removing last ';' if exists
        if( substr( $players_raw, -1 ) == ';' )
            $players_raw = substr( $players_raw, 0, -1 );
        if( $players_raw == '' )
            $players = array();
        else
            $players = explode( ';', $players_raw );

        $result = $this->game->moveAction( $tile_id, $pilot, $navigator, $heli_lift, $card_id, $players, $rescue );
        self::ajaxResponse( );
    }

    public function shoreUpAction()
    {
        self::setAjaxMode();     
        $tile_id = self::getArg( "tile_id", AT_alphanum, true );
        $sandbags = self::getArg( "sandbags", AT_bool, false, false );
        $bonus = self::getArg( "bonus", AT_bool, false, false );
        $card_id = self::getArg( "card_id", AT_alphanum, false, 0 );
        $result = $this->game->shoreUpAction( $tile_id, $bonus, $sandbags, $card_id );
        self::ajaxResponse( );
    }

    public function skipAction()
    {
        self::setAjaxMode();     
        $result = $this->game->skipAction();
        self::ajaxResponse( );
    }

    public function captureTreasure()
    {
        self::setAjaxMode();     
        $result = $this->game->captureTreasure();
        self::ajaxResponse( );
    }

    public function discardTreasure()
    {
        self::setAjaxMode();     
        $id = self::getArg( "id", AT_alphanum, true );
        $result = $this->game->discardTreasure($id);
        self::ajaxResponse( );
    }

    public function giveTreasure()
    {
        self::setAjaxMode();     
        $id = self::getArg( "id", AT_alphanum, true );
        $target_player_id = self::getArg( "target_player_id", AT_alphanum, true );
        $result = $this->game->giveTreasure($id, $target_player_id);
        self::ajaxResponse( );
    }

    public function playSpecial()
    {
        self::setAjaxMode();     
        $id = self::getArg( "id", AT_alphanum, true );
        $player_id = self::getArg( "player_id", AT_alphanum, true );
        $result = $this->game->playSpecial($id, $player_id);
        self::ajaxResponse( );
    }

    public function cancelSpecial()
    {
        self::setAjaxMode();     
        $result = $this->game->cancelSpecial();
        self::ajaxResponse( );
    }

    public function winGame()
    {
        self::setAjaxMode();     
        $result = $this->game->winGame();
        self::ajaxResponse( );
    }

}


