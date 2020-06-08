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
    define("STATE_BONUS_SHOREUP", 9);
    define("STATE_SPECIAL_SANDBAGS", 10);
    define("STATE_SPECIAL_HELI_LIFT", 11);
    define("STATE_FINAL", 12);
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
        "descriptionmyturn" => clienttranslate('${you} may '),
        "type" => "activeplayer",
        "args" => "argPlayerActions",
        "possibleactions" => array( "move", "shore_up", "skip", "give_card", "capture", "special_action" ),
        "transitions" => array( 
            "action" => STATE_PLAYER_ACTIONS, 
            "skip" => STATE_PLAYER_ACTIONS, 
            "bonus_shoreup" => STATE_BONUS_SHOREUP, 
            "draw_treasure" => STATE_DRAW_TREASURE_CARDS,
            "sandbags" => STATE_SPECIAL_SANDBAGS,
            "heli_lift" => STATE_SPECIAL_HELI_LIFT,
            "discard" => STATE_DISCARD_TREASURE_CARDS,
            "final" => STATE_END_GAME
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
            "discard" => STATE_DISCARD_TREASURE_CARDS,
            "final" => STATE_END_GAME
        )
    ),

    STATE_DISCARD_TREASURE_CARDS => array(
        "name" => "discardTreasure",
        "description" => clienttranslate('${actplayer} must select cards to discard (down to 5)'),
        "descriptionmyturn" => clienttranslate('${you} must select cards to discard (down to 5)'),
        // "type" => "activeplayer",
        "type" => "multipleactiveplayer",
        "action" => "stDiscardTreasure",
        "args" => "argDiscardTreasure",
        "possibleactions" => array( "discard", "special_action" ),
        "transitions" => array( 
            "action" => STATE_PLAYER_ACTIONS, 
            "discard" => STATE_DISCARD_TREASURE_CARDS, 
            "set_flood" => STATE_SET_FLOOD_CARDS,
            "sandbags" => STATE_SPECIAL_SANDBAGS,
            "heli_lift" => STATE_SPECIAL_HELI_LIFT,
            "final" => STATE_END_GAME
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
            "final" => STATE_FINAL 
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
            "rescue_pawn" => STATE_RESCUE_PAWN, 
            "next_player" => STATE_NEXT_PLAYER,
            "final" => STATE_FINAL 
        )
    ),

    STATE_RESCUE_PAWN => array(
        "name" => "rescuePawn",
        "description" => clienttranslate('Other players are rescuing their pawns'),
        "descriptionmyturn" => clienttranslate('${you} rescue your pawn.  Select a tile to move to.'),
        // "type" => "activeplayer",
        "type" => "multipleactiveplayer",
        "action" => "stRescuePawn",
        "args" => "argMultiPlayerActions",
        "possibleactions" => array( "move" ),
        "transitions" => array( 
            "draw_flood" => STATE_DRAW_FLOOD_CARDS,
            "rescue_pawn" => STATE_RESCUE_PAWN
        )
    ),

    STATE_BONUS_SHOREUP => array(
        "name" => "bonusShoreup",
        "description" => clienttranslate('${actplayer} is taking bonus shore up action'),
        "descriptionmyturn" => clienttranslate('${you} take bonus shore up action'),
        "type" => "activeplayer",
        "args" => "argPlayerActions",
        "possibleactions" => array( "shore_up", "pass" ),
        "transitions" => array( 
            "action" => STATE_PLAYER_ACTIONS, 
            "draw_treasure" => STATE_DRAW_TREASURE_CARDS,
            "pass" => STATE_PLAYER_ACTIONS 
        )
    ),

    STATE_SPECIAL_SANDBAGS => array(
        "name" => "sandbags",
        "description" => clienttranslate('${actplayer} is playing special action - Sandbags. '),
        "descriptionmyturn" => clienttranslate('${you} are playing special action - Sandbags. Select tile to shore up. '),
        // "type" => "activeplayer",
        "type" => "multipleactiveplayer",
        "action" => "stSpecialAction",
        "args" => "argPlayerActions",
        "possibleactions" => array( "move", "shore_up", "cancel" ),
        "transitions" => array( 
            "action" => STATE_PLAYER_ACTIONS, 
            "cancel" => STATE_PLAYER_ACTIONS, 
            "draw_treasure" => STATE_DRAW_TREASURE_CARDS,
            "discard" => STATE_DISCARD_TREASURE_CARDS,
            "set_flood" => STATE_SET_FLOOD_CARDS,
            "final" => STATE_END_GAME
        )
    ),

    STATE_SPECIAL_HELI_LIFT => array(
        "name" => "heli_lift",
        "description" => clienttranslate('${actplayer} is playing special action - Helicopter Lift.'),
        "descriptionmyturn" => clienttranslate('${you} are playing special action - Helicopter Lift.  Select starting tile.'),
        // "type" => "activeplayer",
        "type" => "multipleactiveplayer",
        "action" => "stSpecialAction",
        "args" => "argPlayerActions",
        "possibleactions" => array( "move", "shore_up", "cancel" ),
        "transitions" => array( 
            "action" => STATE_PLAYER_ACTIONS, 
            "cancel" => STATE_PLAYER_ACTIONS, 
            "draw_treasure" => STATE_DRAW_TREASURE_CARDS,
            "discard" => STATE_DISCARD_TREASURE_CARDS,
            "set_flood" => STATE_SET_FLOOD_CARDS,
            "final" => STATE_END_GAME
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
            "next_turn" => STATE_PLAYER_ACTIONS,
            "final" => STATE_FINAL 
        )
    ),
  
    // Final state.

    STATE_FINAL => array(
        "name" => "final",
        "type" => "game",
        // "args" => "argPlayerActions",
        "action" => "stFinal",
        // "updateGameProgression" => true,
        // "possibleactions" => array( "move", "pass" ),
        "transitions" => array( 
            "end" => STATE_END_GAME 
        )
    ),

    // Please do not modify (and do not overload action/args methods).
    STATE_END_GAME => array(
        "name" => "endGame",
        "description" => clienttranslate("End of game"),
        "type" => "manager",
        "action" => "stGameEnd",
        "args" => "argGameEnd"
    )

);



