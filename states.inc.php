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
 * states.inc.php
 *
 * forbiddenisland game states description
 *
 */

/*
*
*   Game state machine is a tool used to facilitate game developpement by doing common stuff that can be set up
*   in a very easy way from this configuration file.
*
*
*   States types:
*   _ manager: game manager can make the game progress to the next state.
*   _ game: this is an (unstable) game state. the game is going to progress to the next state as soon as current action has been accomplished
*   _ activeplayer: an action is expected from the activeplayer
*
*   Arguments:
*   _ possibleactions: array that specify possible player actions on this step (for state types "manager" and "activeplayer")
*       (correspond to actions names)
*   _ action: name of the method to call to process the action (for state type "game")
*   _ transitions: name of transitions and corresponding next state
*       (name of transitions correspond to "nextState" argument)
*   _ description: description is displayed on top of the main content.
*   _ descriptionmyturn (optional): alternative description displayed when it's player's turn
*
*/

// define contants for state ids
if (!defined('STATE_END_GAME')) { // ensure this block is only invoked once, since it is included multiple times
    define("STATE_PLAYER_ACTIONS", 2);
    define("STATE_DRAW_TREASURE_CARDS", 3);
    define("STATE_DISCARD_TREASURE_CARDS", 4);
    define("STATE_SET_FLOOD_CARDS", 5);
    define("STATE_DRAW_FLOOD_CARDS", 6);
    define("STATE_NEXT_PLAYER", 7);
    define("STATE_RESCUE_PAWN", 8);
    define("STATE_END_GAME", 99);
 }

$machinestates = array(

    // The initial state. Please do not modify.
    1 => array(
        "name" => "gameSetup",
        "description" => "",
        "type" => "manager",
        "action" => "stGameSetup",
        "transitions" => array( "" => STATE_DRAW_FLOOD_CARDS )
    ),
    
    // Note: ID=2 => your first state

    STATE_PLAYER_ACTIONS => array(
        "name" => "playerActions",
        "description" => clienttranslate('${actplayer} is taking actions'),
        "descriptionmyturn" => clienttranslate('${you} take '),
        "type" => "activeplayer",
        "args" => "argPlayerActions",
        "possibleactions" => array( "move", "shore_up", "skip", "give_card" ),
        "transitions" => array( 
            "action" => STATE_PLAYER_ACTIONS, 
            "skip" => STATE_PLAYER_ACTIONS, 
            "draw_treasure" => STATE_DRAW_TREASURE_CARDS 
        )
    ),

    STATE_DRAW_TREASURE_CARDS => array(
        "name" => "drawTreasure",
        "description" => clienttranslate('${actplayer} is drawing treasure cards'),
        "descriptionmyturn" => clienttranslate('${you} are drawing treasure cards'),
        "type" => "game",
        // "args" => "argPlayerActions",
        "action" => "stDrawTreasureCards",
        // "possibleactions" => array( "move", "pass" ),
        "transitions" => array( 
            "set_flood" => STATE_SET_FLOOD_CARDS,
            "discard" => STATE_DISCARD_TREASURE_CARDS
        )
    ),

    STATE_DISCARD_TREASURE_CARDS => array(
        "name" => "discardTreasure",
        "description" => clienttranslate('${actplayer} must select cards to discard (down to 5)'),
        "descriptionmyturn" => clienttranslate('${you} must select cards to discard (down to 5)'),
        "type" => "activeplayer",
        "args" => "argDiscardTreasure",
        "possibleactions" => array( "discard" ),
        "transitions" => array( 
            "action" => STATE_PLAYER_ACTIONS, 
            "discard" => STATE_DISCARD_TREASURE_CARDS, 
            "set_flood" => STATE_SET_FLOOD_CARDS
        )
    ),

    STATE_SET_FLOOD_CARDS => array(
        "name" => "setFlood",
        "description" => clienttranslate('Drawing flood cards'),
        "descriptionmyturn" => clienttranslate('Drawing flood cards'),
        "type" => "game",
        "action" => "stSetFloodCards",
        // "args" => "argDrawFloodCards",
        // "possibleactions" => array( "move", "pass" ),
        "transitions" => array( 
            "draw_flood" => STATE_DRAW_FLOOD_CARDS,
            "endGame" => STATE_END_GAME
        )
    ),

    STATE_DRAW_FLOOD_CARDS => array(
        "name" => "drawFlood",
        "description" => clienttranslate('Drawing flood cards'),
        "descriptionmyturn" => clienttranslate('Drawing flood cards'),
        "type" => "game",
        "action" => "stDrawFloodCards",
        "args" => "argDrawFloodCards",
        // "possibleactions" => array( "move", "pass" ),
        "transitions" => array( 
            "draw_flood" => STATE_DRAW_FLOOD_CARDS,
            "rescuePawn" => STATE_RESCUE_PAWN, 
            "nextPlayer" => STATE_NEXT_PLAYER,
            "endGame" => STATE_END_GAME 
        )
    ),

    STATE_RESCUE_PAWN => array(
        "name" => "rescuePawn",
        "description" => clienttranslate('${actplayer} is taking actions'),
        "descriptionmyturn" => clienttranslate('${you} take up to actions'),
        "type" => "activeplayer",
        "args" => "argPlayerActions",
        "possibleactions" => array( "move", "pass" ),
        "transitions" => array( 
            "action" => STATE_PLAYER_ACTIONS, 
            "pass" => STATE_PLAYER_ACTIONS 
        )
    ),

    STATE_NEXT_PLAYER => array(
        "name" => "nextPlayer",
        "type" => "game",
        // "args" => "argPlayerActions",
        "action" => "stNextPlayer",
        // "updateGameProgression" => true,
        // "possibleactions" => array( "move", "pass" ),
        "transitions" => array( 
            "nextTurn" => STATE_PLAYER_ACTIONS,
            "endGame" => STATE_END_GAME 
        )
    ),
   
    // Final state.
    // Please do not modify (and do not overload action/args methods).
    STATE_END_GAME => array(
        "name" => "endGame",
        "description" => clienttranslate("End of game"),
        "type" => "manager",
        "action" => "stGameEnd",
        "args" => "argGameEnd"
    )

);



