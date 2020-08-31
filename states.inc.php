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
    define("STATE_CONTINUE", 13);
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
        "description" => clienttranslate('${actplayer} '),
        "descriptionmyturn" => clienttranslate('${you} '),
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
            "discard" => STATE_DISCARD_TREASURE_CARDS
        )
    ),

    STATE_DRAW_TREASURE_CARDS => array(
        "name" => "drawTreasure",
        "description" => clienttranslate('Drawing treasure cards'),
        "descriptionmyturn" => clienttranslate('Drawing treasure cards'),
        "type" => "game",
        "action" => "stDrawTreasureCards",
        "transitions" => array( 
            "continue" => STATE_CONTINUE,
            "discard" => STATE_DISCARD_TREASURE_CARDS,
            "final" => STATE_FINAL 
        )
    ),

    STATE_DISCARD_TREASURE_CARDS => array(
        "name" => "discardTreasure",
        "description" => clienttranslate('${actplayer} must select cards to discard (down to 5)'),
        "descriptionmyturn" => clienttranslate('${you} must select cards to discard (down to 5)'),
        "type" => "multipleactiveplayer",
        "action" => "stDiscardTreasure",
        "args" => "argDiscardTreasure",
        "possibleactions" => array( "discard", "special_action"),
        "transitions" => array( 
            "action" => STATE_PLAYER_ACTIONS, 
            "discard" => STATE_DISCARD_TREASURE_CARDS, 
            "continue" => STATE_CONTINUE,
            "sandbags" => STATE_SPECIAL_SANDBAGS,
            "heli_lift" => STATE_SPECIAL_HELI_LIFT,
            "draw_treasure" => STATE_DRAW_TREASURE_CARDS
        )
    ),

    STATE_CONTINUE => array(
        "name" => "continue",
        "description" => clienttranslate('Waiting for ${actplayer} to draw flood cards'),
        "descriptionmyturn" => clienttranslate('${you} must play Special Cards or '),
        "type" => "activeplayer",
        "args" => "argPlayerActions",
        "possibleactions" => array( "continue", "special_action" ),
        "transitions" => array( 
            "set_flood" => STATE_SET_FLOOD_CARDS, 
            "sandbags" => STATE_SPECIAL_SANDBAGS,
            "heli_lift" => STATE_SPECIAL_HELI_LIFT
        )
    ),

    STATE_SET_FLOOD_CARDS => array(
        "name" => "setFlood",
        "description" => clienttranslate('Drawing flood cards'),
        "descriptionmyturn" => clienttranslate('Drawing flood cards'),
        "type" => "game",
        "action" => "stSetFloodCards",
        "transitions" => array( 
            "draw_flood" => STATE_DRAW_FLOOD_CARDS
        )
    ),

    STATE_DRAW_FLOOD_CARDS => array(
        "name" => "drawFlood",
        "description" => clienttranslate('Drawing flood cards'),
        "descriptionmyturn" => clienttranslate('Drawing flood cards'),
        "type" => "game",
        "action" => "stDrawFloodCards",
        "args" => "argDrawFloodCards",
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
        "type" => "multipleactiveplayer",
        "action" => "stRescuePawn",
        "args" => "argMultiPlayerActions",
        "possibleactions" => array( "move" ),
        "transitions" => array( 
            "draw_flood" => STATE_DRAW_FLOOD_CARDS,
            "rescue_pawn" => STATE_RESCUE_PAWN,
            "final" => STATE_FINAL
        )
    ),

    STATE_BONUS_SHOREUP => array(
        "name" => "bonusShoreup",
        "description" => clienttranslate('${actplayer} is taking bonus shore up action'),
        "descriptionmyturn" => clienttranslate('${you} take bonus shore up action'),
        "type" => "activeplayer",
        "args" => "argPlayerActions",
        "possibleactions" => array( "shore_up", "skip" ),
        "transitions" => array( 
            "action" => STATE_PLAYER_ACTIONS, 
            "draw_treasure" => STATE_DRAW_TREASURE_CARDS
        )
    ),

    STATE_SPECIAL_SANDBAGS => array(
        "name" => "sandbags",
        "description" => clienttranslate('${actplayer} is playing Sandbags. '),
        "descriptionmyturn" => clienttranslate('${you} are playing Sandbags. Select tile to shore up. '),
        "type" => "multipleactiveplayer",
        "action" => "stSpecialAction",
        "args" => "argPlayerActions",
        "possibleactions" => array( "shore_up", "cancel" ),
        "transitions" => array( 
            "action" => STATE_PLAYER_ACTIONS, 
            "cancel" => STATE_PLAYER_ACTIONS, 
            "draw_treasure" => STATE_DRAW_TREASURE_CARDS,
            "discard" => STATE_DISCARD_TREASURE_CARDS,
            "continue" => STATE_CONTINUE
        )
    ),

    STATE_SPECIAL_HELI_LIFT => array(
        "name" => "heli_lift",
        "description" => clienttranslate('${actplayer} is playing Helicopter Lift.'),
        "descriptionmyturn" => clienttranslate('${you} are playing Helicopter Lift.  Select starting tile.'),
        "type" => "multipleactiveplayer",
        "action" => "stSpecialAction",
        "args" => "argPlayerActions",
        "possibleactions" => array( "move", "cancel", "win" ),
        "transitions" => array( 
            "action" => STATE_PLAYER_ACTIONS, 
            "cancel" => STATE_PLAYER_ACTIONS, 
            "draw_treasure" => STATE_DRAW_TREASURE_CARDS,
            "discard" => STATE_DISCARD_TREASURE_CARDS,
            "continue" => STATE_CONTINUE,
            "final" => STATE_FINAL
        )
    ),

    STATE_NEXT_PLAYER => array(
        "name" => "nextPlayer",
        "type" => "game",
        "action" => "stNextPlayer",
        "updateGameProgression" => true,
        "transitions" => array( 
            "next_turn" => STATE_PLAYER_ACTIONS,
            "final" => STATE_FINAL 
        )
    ),
  
    // Final state.

    STATE_FINAL => array(
        "name" => "final",
        "type" => "game",
        "action" => "stFinal",
        "transitions" => array( 
            "end" => STATE_END_GAME,
            "debug" => STATE_PLAYER_ACTIONS
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



