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
 * material.inc.php
 *
 * forbiddenisland game material description
 *
 * Here, you can describe the material of your game with PHP variables.
 *   
 * This file is loaded in your game logic class constructor, ie these variables
 * are available everywhere in your game logic code.
 *
 */

/*

$this->some_material = array(
    ...
);

*/

$this->not_in_map = array( 
    "1_1", "2_1", "5_1", "6_1",
    "1_2", "6_2",
    "1_5", "6_5",
    "1_6", "2_6", "5_6", "6_6"
);

$this->island_map = array(
    1 => array(
        'name' => clienttranslate("Forbidden Island"),
        'max_x' => 6,
        'max_y' => 6,
        'map' => array (
                          "3_1", "4_1",
                   "2_2", "3_2", "4_2", "5_2",
            "1_3", "2_3", "3_3", "4_3", "5_3", "6_3",
            "1_4", "2_4", "3_4", "4_4", "5_4", "6_4",
                   "2_5", "3_5", "4_5", "5_5",
                          "3_6", "4_6"
        ),
        'water_level_meter' => "7_2",
        'earth' => "1_1",
        'air' => "6_1",
        'fire' => "1_6",
        'ocean' => "6_6",
    ),
    2 => array(
        'name' => clienttranslate("Treasure Island"),
        'max_x' => 7,
        'max_y' => 6,
        'map' => array (
            "1_1", "2_1",                      "6_1", "7_1",
                   "2_2", "3_2", "4_2", "5_2", "6_2",
                          "3_3", "4_3", "5_3",
                          "3_4", "4_4", "5_4",
                   "2_5", "3_5", "4_5", "5_5", "6_5",
            "1_6", "2_6",                      "6_6", "7_6"
        ),
        'water_level_meter' => "7_3",
        'earth' => "3_1",
        'air' => "7_2",
        'fire' => "1_5",
        'ocean' => "5_6",
    ),
);

$this->player_list = array(
    'engineer' => array(
        'name' => clienttranslate("Engineer"),
        'location' => 'bronze_gate',
        'color' => 'ED0023',
        'pawn_idx' => 1,
        'idx' => 2,
        'tooltip' => clienttranslate("<b>Engineer</b> <p>Shore up 2 tiles for 1 action.</p>")
    ),
    'pilot' => array(
        'name' => clienttranslate("Pilot"),
        'location' => 'fools_landing',
        'color' => '006EB2',
        'pawn_idx' => 2,
        'idx' => 6,
        'tooltip' => clienttranslate("<b>Pilot</b> <p>Once per turn, fly to any tile on the island for 1 action.</p>")

    ),
    'navigator' => array(
        'name' => clienttranslate("Navigator"),
        'location' => 'gold_gate',
        'color' => 'FFEC35',
        'pawn_idx' => 3,
        'idx' => 5,
        'tooltip' => clienttranslate("<b>Navigator</b> <p>Move another player up to 2 adjacent tiles for 1 action.</p>")

    ),
    'explorer' => array(
        'name' => clienttranslate("Explorer"),
        'location' => 'copper_gate',
        'color' => '109D4C',
        'pawn_idx' => 4,
        'idx' => 3,
        'tooltip' => clienttranslate("<b>Explorer</b> <p>Move and / or shore up diagonally.</p>")

    ),
    'diver' => array(
        'name' => clienttranslate("Diver"),
        'location' => 'iron_gate',
        'color' => '1F1D1D',
        'pawn_idx' => 5,
        'idx' => 1,
        'tooltip' => clienttranslate("<b>Diver</b> <p>Move through 1 or more adjacent flooded and/or missing tiles for 1 action. (Must end your turn on a tile.)</p>")

    ),
    'messenger' => array(
        'name' => clienttranslate("Messenger"),
        'location' => 'silver_gate',
        'color' => 'BEC1C4',
        'pawn_idx' => 6,
        'idx' => 4,
        'tooltip' => clienttranslate("<b>Messenger</b> <p>Give Treasure cards to a player anywhere on the island for 1 action per card.</p>")

    )
);

$this->treasure_list = array(
    'air' => array(
        'name' => clienttranslate("Air"),
        'idx' => 2,
        'nbr' => 5,
        'fig' => 1,
        'tiles' => array(
            'howling_garden',
            'whispering_garden'
        ),
        'tooltip' => clienttranslate("Air")
    ),
    'earth' => array(
        'name' => clienttranslate("Earth"),
        'idx' => 3,
        'nbr' => 5,
        'fig' => 0,
        'tiles' => array(
            'temple_moon',
            'temple_sun'
        ),
        'tooltip' => clienttranslate("Earth")
    ),
    'fire' => array(
        'name' => clienttranslate("Fire"),
        'idx' => 4,
        'nbr' => 5,
        'fig' => 2,
        'tiles' => array(
            'cave_embers',
            'cave_shadows'
        ),
        'tooltip' => clienttranslate("Fire")
    ),
    'ocean' => array(
        'name' => clienttranslate("Ocean"),
        'idx' => 5,
        'nbr' => 5,
        'fig' => 3,
        'tiles' => array(
            'coral_palace',
            'tidal_palace'
        ),
        'tooltip' => clienttranslate("Ocean")
    ),
    'heli_lift' => array(
        'name' => clienttranslate("Helicopter Lift"),
        'idx' => 6,
        'nbr' => 3,
        'tooltip' => clienttranslate("<b>Helicopter Lift</b> <p>Move one or more pawns on the same tile to any other tile.</p> <p> ---- or ---- </p> <p>Lift your team off Fools' Landing for the win!</p> <i><ul><li>- Play at any time.</li><li>- Does not count as an action.</li><li>- Discard to Treasure discard pile after use.</li></ul></i>")
    ),
    'sandbags' => array(
        'name' => clienttranslate("Sandbags"),
        'idx' => 7,
        'nbr' => 2,
        'tooltip' => clienttranslate("<b>Sandbags</b> <p>Shore up any one tile on the island.</p> <i><ul><li>- Play at any time.</li><li>- Does not count as an action.</li><li>- Discard to Treasure discard pile after use.</li></ul></i>")
    ),
    'waters_rise' => array(
        'name' => clienttranslate("Waters Rise!"),
        'idx' => 8,
        'nbr' => 3,
        'tooltip' => clienttranslate("<b>Waters Rise!</b> <ol><li>Move the water level up one tick mark.</li> <li>Shuffle the Flood discard pile and place it on top of the Flood draw deck.</li> <li>Discard this card to the Treasure discard pile.</li></ol>")
    )
);

$this->tile_list = array(
    'fools_landing' => array(
        'name' => clienttranslate("Fool's Landing"),
        'img_id' => 10
    ),
    'bronze_gate' => array(
        'name' => clienttranslate("Bronze Gate"),
        'img_id' => 2
    ),
    'gold_gate' => array(
        'name' => clienttranslate("Gold Gate"),
        'img_id' => 11
    ),
    'silver_gate' => array(
        'name' => clienttranslate("Silver Gate"),
        'img_id' => 18
    ),
    'iron_gate' => array(
        'name' => clienttranslate("Iron Gate"),
        'img_id' => 13
    ),
    'copper_gate' => array(
        'name' => clienttranslate("Copper Gate"),
        'img_id' => 6
    ),
    'temple_moon' => array(
        'name' => clienttranslate("Temple of the Moon"),
        'img_id' => 19
    ),
    'temple_sun' => array(
        'name' => clienttranslate("Temple of the Sun"),
        'img_id' => 20
    ),
    'cave_embers' => array(
        'name' => clienttranslate("Cave of Embers"),
        'img_id' => 3
    ),
    'cave_shadows' => array(
        'name' => clienttranslate("Cave of Shadows"),
        'img_id' => 4
    ),
    'howling_garden' => array(
        'name' => clienttranslate("Howling Garden"),
        'img_id' => 12
    ),
    'whispering_garden' => array(
        'name' => clienttranslate("Whispering Garden"),
        'img_id' => 24
    ),
    'coral_palace' => array(
        'name' => clienttranslate("Coral Palace"),
        'img_id' => 7
    ),
    'tidal_palace' => array(
        'name' => clienttranslate("Tidal Palace"),
        'img_id' => 21
    ),
    'watchtower' => array(
        'name' => clienttranslate("Watchtower"),
        'img_id' => 23
    ),
    'observatory' => array(
        'name' => clienttranslate("Observatory"),
        'img_id' => 16
    ),
    'dunes_deception' => array(
        'name' => clienttranslate("Dunes of Deception"),
        'img_id' => 9
    ),
    'cliffs_abandon' => array(
        'name' => clienttranslate("Cliffs of Abandon"),
        'img_id' => 5
    ),
    'crimson_forest' => array(
        'name' => clienttranslate("Crimson Forest"),
        'img_id' => 8
    ),
    'twilight_hollow' => array(
        'name' => clienttranslate("Twilight Hollow"),
        'img_id' => 22
    ),
    'breakers_bridge' => array(
        'name' => clienttranslate("Breakers Bridge"),
        'img_id' => 1
    ),
    'misty_marsh' => array(
        'name' => clienttranslate("Misty Marsh"),
        'img_id' => 15
    ),
    'lost_lagoon' => array(
        'name' => clienttranslate("Lost Lagoon"),
        'img_id' => 14
    ),
    'phantom_rock' => array(
        'name' => clienttranslate("Phantom Rock"),
        'img_id' => 17
    ),
);

$this->flood_list = array(
    'fools_landing' => array(
        'name' => clienttranslate("Fool's Landing"),
        'img_id' => 11
    ),
    'bronze_gate' => array(
        'name' => clienttranslate("Bronze Gate"),
        'img_id' => 3
    ),
    'gold_gate' => array(
        'name' => clienttranslate("Gold Gate"),
        'img_id' => 12
    ),
    'silver_gate' => array(
        'name' => clienttranslate("Silver Gate"),
        'img_id' => 19
    ),
    'iron_gate' => array(
        'name' => clienttranslate("Iron Gate"),
        'img_id' => 14
    ),
    'copper_gate' => array(
        'name' => clienttranslate("Copper Gate"),
        'img_id' => 7
    ),
    'temple_moon' => array(
        'name' => clienttranslate("Temple of the Moon"),
        'img_id' => 20
    ),
    'temple_sun' => array(
        'name' => clienttranslate("Temple of the Sun"),
        'img_id' => 21
    ),
    'cave_embers' => array(
        'name' => clienttranslate("Cave of Embers"),
        'img_id' => 4
    ),
    'cave_shadows' => array(
        'name' => clienttranslate("Cave of Shadows"),
        'img_id' => 5
    ),
    'howling_garden' => array(
        'name' => clienttranslate("Howling Garden"),
        'img_id' => 13
    ),
    'whispering_garden' => array(
        'name' => clienttranslate("Whispering Garden"),
        'img_id' => 25
    ),
    'coral_palace' => array(
        'name' => clienttranslate("Coral Palace"),
        'img_id' => 8
    ),
    'tidal_palace' => array(
        'name' => clienttranslate("Tidal Palace"),
        'img_id' => 22
    ),
    'watchtower' => array(
        'name' => clienttranslate("Watchtower"),
        'img_id' => 24
    ),
    'observatory' => array(
        'name' => clienttranslate("Observatory"),
        'img_id' => 17
    ),
    'dunes_deception' => array(
        'name' => clienttranslate("Dunes of Deception"),
        'img_id' => 10
    ),
    'cliffs_abandon' => array(
        'name' => clienttranslate("Cliffs of Abandon"),
        'img_id' => 6
    ),
    'crimson_forest' => array(
        'name' => clienttranslate("Crimson Forest"),
        'img_id' => 9
    ),
    'twilight_hollow' => array(
        'name' => clienttranslate("Twilight Hollow"),
        'img_id' => 23
    ),
    'breakers_bridge' => array(
        'name' => clienttranslate("Breakers Bridge"),
        'img_id' => 2
    ),
    'misty_marsh' => array(
        'name' => clienttranslate("Misty Marsh"),
        'img_id' => 16
    ),
    'lost_lagoon' => array(
        'name' => clienttranslate("Lost Lagoon"),
        'img_id' => 15
    ),
    'phantom_rock' => array(
        'name' => clienttranslate("Phantom Rock"),
        'img_id' => 18
    ),
);





