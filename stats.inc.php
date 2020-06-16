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
 * stats.inc.php
 *
 * forbiddenisland game statistics description
 *
 */

/*
    In this file, you are describing game statistics, that will be displayed at the end of the
    game.
    
    There are 2 types of statistics:
    _ table statistics, that are not associated to a specific player (ie: 1 value for each game).
    _ player statistics, that are associated to each players (ie: 1 value for each player in the game).

    Statistics types can be "int" for integer, and "float" for floating point values.
    
    Once you defined your statistics there, you can start using "initStat", "setStat" and "incStat" method
    in your game logic, using statistics names defined below.
*/

//   !! It is not a good idea to modify this file when a game is running !!


$stats_type = array(

    // Statistics global to table
    "table" => array(

        "turns_number" => array("id"=> 10,
                    "name" => totranslate("Number of turns"),
                    "type" => "int" ),
        "players_number" => array("id"=> 11,
                    "name" => totranslate("Number of players"),
                    "type" => "int" ),
        "beginners_number" => array("id"=> 12,
                    "name" => totranslate("Number of beginner players"),
                    "type" => "int" ),
        "difficulty" => array("id"=> 13,
                    "name" => totranslate("Game difficulty"),
                    "type" => "int" ),
        "island_map" => array("id"=> 14,
                    "name" => totranslate("Island map played"),
                    "type" => "int" ),
        "tiles_flooded" => array("id"=> 15,
                    "name" => totranslate("Number of tiles flooded"),
                    "type" => "int" ),
        "tiles_sunk" => array("id"=> 16,
                    "name" => totranslate("Number of tiles sunk"),
                    "type" => "int" ),
        "tiles_shored_up" => array("id"=> 17,
                    "name" => totranslate("Number of tiles shored up"),
                    "type" => "int" ),
        "treasures_captured" => array("id"=> 18,
                    "name" => totranslate("Treasures captured"),
                    "type" => "int" ),
        "water_level" => array("id"=> 19,
                    "name" => totranslate("Water level"),
                    "type" => "int" ),
        "players_won" => array("id"=> 20,
                    "name" => totranslate("Players won"),
                    "type" => "bool" ),
    ),
    
    // Statistics existing for each player
    "player" => array(

        "turns_number" => array("id"=> 100,
                    "name" => totranslate("Number of turns"),
                    "type" => "int" ),
        "adventurer" => array("id"=> 101,
                    "name" => totranslate("Adventurer played"),
                    "type" => "int" ),
        "move" => array("id"=> 102,
                    "name" => totranslate("Move actions"),
                    "type" => "int" ),
        "shore_up" => array("id"=> 103,
                    "name" => totranslate("Shore up actions"),
                    "type" => "int" ),
        "give_card" => array("id"=> 104,
                    "name" => totranslate("Give treasure card actions"),
                    "type" => "int" ),
        "capture" => array("id"=> 105,
                    "name" => totranslate("Treasures captured"),
                    "type" => "int" ),
        "skip" => array("id"=> 106,
                    "name" => totranslate("Actions skipped"),
                    "type" => "int" ),
        "discard" => array("id"=> 107,
                    "name" => totranslate("Treasure cards discarded"),
                    "type" => "int" ),
        "sandbags" => array("id"=> 108,
                    "name" => totranslate("Sandbag cards played"),
                    "type" => "int" ),
        "heli_lift" => array("id"=> 109,
                    "name" => totranslate("Helicopter Lift cards played"),
                    "type" => "int" ),
        "pilot" => array("id"=> 110,
                    "name" => totranslate("Pilot actions"),
                    "type" => "int" ),
        "navigator" => array("id"=> 111,
                    "name" => totranslate("Navigate actions"),
                    "type" => "int" ),
        "bonus_shoreup" => array("id"=> 112,
                    "name" => totranslate("Engineer bonus shore ups"),
                    "type" => "int" ),
    
    ),

    "value_labels" => array(
        13 => array(
            1 => totranslate("Novice"),
            2 => totranslate("Normal"),
            3 => totranslate("Elite"),
            4 => totranslate("Legendary"),
        ),
        14 => array (
            1 => totranslate('Forbidden Island'),
            2 => totranslate('Treasure Island'),
            3 => totranslate('Island of Shadows'),
            4 => totranslate('Volcano Island'),
            5 => totranslate('Island of Death'),
            6 => totranslate('Bay of Gulls'),
            7 => totranslate('Bone Island'),
            8 => totranslate('Coral Reef'),
            9 => totranslate('Skull Island'),
            10 => totranslate('Bridge of Horrors'),
            11 => totranslate('Atoll of Decisions'),
            12 => totranslate('Arch of Fate'),
        ),
        101 => array (
            1 => totranslate('Engineer'),
            2 => totranslate('Pilot'),
            3 => totranslate('Navigator'),
            4 => totranslate('Explorer'),
            5 => totranslate('Diver'),
            6 => totranslate('Messenger'),
        ),
    )

);
