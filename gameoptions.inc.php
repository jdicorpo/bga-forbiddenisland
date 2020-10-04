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
 * gameoptions.inc.php
 *
 * forbiddenisland game options description
 *
 * In this file, you can define your game options (= game variants).
 *
 * Note: If your game has no variant, you don't have to modify this file.
 *
 * Note²: All options defined in this file should have a corresponding "game state labels"
 *        with the same ID (see "initGameStateLabels" in forbiddenisland.game.php)
 *
 * !! It is not a good idea to modify this file when a game is running !!
 *
 */

$game_options = array(
    100 => array(
        'name' => totranslate('Difficulty'),
        'values' => array(
            1 => array( 'name' => totranslate('Novice'), 'nobeginner' => false ),
            2 => array( 'name' => totranslate('Normal'), 'nobeginner' =>  false ),
            3 => array( 'name' => totranslate('Elite'), 'nobeginner' => false ),
            4 => array( 'name' => totranslate('Legendary'), 'nobeginner' => false ),
        ),
        'default' => 2,
    ),
    101 => array(
        'name' => totranslate('Island Map'),
        'values' => array(
            1 => array( 'name' => totranslate('Forbidden Island'), 'nobeginner' => false ),
            2 => array( 'name' => totranslate('Treasure Island'), 'nobeginner' => false ),
            3 => array( 'name' => totranslate('Island of Shadows'), 'nobeginner' => false ),
            4 => array( 'name' => totranslate('Volcano Island'), 'nobeginner' => false ),
            5 => array( 'name' => totranslate('Island of Death'), 'nobeginner' => false ),
            6 => array( 'name' => totranslate('Bay of Gulls'), 'nobeginner' => false ),
            7 => array( 'name' => totranslate('Bone Island'), 'nobeginner' => false ),
            8 => array( 'name' => totranslate('Coral Reef'), 'nobeginner' => false ),
            9 => array( 'name' => totranslate('Skull Island'), 'nobeginner' => false ),
            10 => array( 'name' => totranslate('Bridge of Horrors'), 'nobeginner' => false ),
            11 => array( 'name' => totranslate('Atoll of Decisions'), 'nobeginner' => false ),
            12 => array( 'name' => totranslate('Arch of Fate'), 'nobeginner' => false ),
        ),
        'default' => 1,
    ),
);


