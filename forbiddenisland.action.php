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
        $result = $this->game->moveAction( $tile_id );
        self::ajaxResponse( );
    }

    public function shoreUpAction()
    {
        self::setAjaxMode();     
        $tile_id = self::getArg( "tile_id", AT_alphanum, true );
        $result = $this->game->shoreUpAction( $tile_id );
        self::ajaxResponse( );
    }

    public function skipAction()
    {
        self::setAjaxMode();     
        $result = $this->game->skipAction();
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


  /*
  
  Example:
    
  public function myAction()
  {
      self::setAjaxMode();     

      // Retrieve arguments
      // Note: these arguments correspond to what has been sent through the javascript "ajaxcall" method
      $arg1 = self::getArg( "myArgument1", AT_posint, true );
      $arg2 = self::getArg( "myArgument2", AT_posint, true );

      // Then, call the appropriate method in your game logic, like "playCard" or "myAction"
      $this->game->myAction( $arg1, $arg2 );

      self::ajaxResponse( );
  }
  
  */

}


