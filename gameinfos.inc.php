<?php

$gameinfos = array( 

'game_name' => 'Forbidden Island',

// Game designer (or game designers, separated by commas)
'designer' => 'Matt Leacock',       

// Game artist (or game artists, separated by commas)
'artist' => 'C. B. Canga',         

// Year of FIRST publication of this game. Can be negative.
'year' => 2010,                 

// Game publisher
'publisher' => 'Gamewright',                     

// Url of game publisher website
'publisher_website' => 'http://www.gamewright.com/gamewright/',   

// Board Game Geek ID of the publisher
'publisher_bgg_id' => 108,

// Board game geek if of the game
'bgg_id' => 65244,


// Players configuration that can be played (ex: 2 to 4 players)
'players' => array( 2, 3, 4 ),    

// Suggest players to play with this number of players. Must be null if there is no such advice, or if there is only one possible player configuration.
'suggest_player_number' => 4,

// Discourage players to play with this number of players. Must be null if there is no such advice.
'not_recommend_player_number' => array( ),



// Estimated game duration, in minutes (used only for the launch, afterward the real duration is computed)
'estimated_duration' => 30,           

// Time in second add to a player when "giveExtraTime" is called (speed profile = fast)
'fast_additional_time' => 7,           

// Time in second add to a player when "giveExtraTime" is called (speed profile = medium)
'medium_additional_time' => 16,           

// Time in second add to a player when "giveExtraTime" is called (speed profile = slow)
'slow_additional_time' => 23,           


// Game is "beta". A game MUST set is_beta=1 when published on BGA for the first time, and must remains like this until all bugs are fixed.
'is_beta' => 1,                     

// Is this game cooperative (all players wins together or loose together)
'is_coop' => 1, 


// Complexity of the game, from 0 (extremely simple) to 5 (extremely complex)
'complexity' => 2,    

// Luck of the game, from 0 (absolutely no luck in this game) to 5 (totally luck driven)
'luck' => 3,    

// Strategy of the game, from 0 (no strategy can be setup) to 5 (totally based on strategy)
'strategy' => 2,    

// Diplomacy of the game, from 0 (no interaction in this game) to 5 (totally based on interaction and discussion between players)
'diplomacy' => 0,    

// Colors attributed to players
'player_colors' => array( 
    "ED0023",  // red
    "006EB2",  // blue
    "FFEC35",  // yellow
    "109D4C",  // green
    "1F1D1D", // grey
    "BEC1C4", // silver
),

// Favorite colors support : if set to "true", support attribution of favorite colors based on player's preferences (see reattributeColorsBasedOnPreferences PHP method)
'favorite_colors_support' => false,

// Game interface width range (pixels)
// Note: game interface = space on the left side, without the column on the right
'game_interface_width' => array(

    // Minimum width
    //  default: 760
    //  maximum possible value: 760 (ie: your game interface should fit with a 760px width (correspond to a 1024px screen)
    //  minimum possible value: 320 (the lowest value you specify, the better the display is on mobile)
    'min' => 540,

    // Maximum width
    //  default: null (ie: no limit, the game interface is as big as the player's screen allows it).
    //  maximum possible value: unlimited
    //  minimum possible value: 760
    'max' => null
),


// Games categories
//  You can attribute any number of "tags" to your game.
//  Each tag has a specific ID (ex: 22 for the category "Prototype", 101 for the tag "Science-fiction theme game")
'tags' => array( 2, 11, 20, 103, 210 )

);
