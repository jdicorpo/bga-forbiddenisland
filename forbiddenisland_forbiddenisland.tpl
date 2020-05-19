{OVERALL_GAME_HEADER}

<!-- 
--------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- forbiddenisland implementation : © Jeff DiCorpo <jdicorpo@gmail.com>
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-------

    forbiddenisland_forbiddenisland.tpl
    
    This is the HTML template of your game.
    
    Everything you are writing in this file will be displayed in the HTML page of your game user interface,
    in the "main game zone" of the screen.
    
    You can use in this template:
    _ variables, with the format {MY_VARIABLE_ELEMENT}.
    _ HTML block, with the BEGIN/END format
    
    See your "view" PHP file to check how to set variables and control blocks
-->
<div id="outer_wrapper" class="anchor">
    <div id="board">
        <div id="island_tile" style="visibility: hidden;">
            <div id="pawn_area_0_0" class="pawn_area" style="visibility: hidden;"></div>
        </div>
        <!-- BEGIN island_tile -->
        <div id="island_tile_{X}_{Y}" class="island_tile_location" style="left: {LEFT}px; top: {TOP}px;">
        </div>
        <!-- END island_tile -->    

        <!-- <div style="float: right;">
            <div id="water_level_meter"></div>
            <div id="treasure_deck_display">
                <h3>Treasure Deck</h3>
            </div>
        </div>
        <div style="float: clear;"></div> -->
    </div>
    <!-- <div style="display: none;"></div> -->

    <div id="player_area_wrapper">
        <!-- BEGIN player -->
        <div id="player_area" class="player_area whiteblock">
            <div class="side_title_wrapper">
                <div id="player_area_{PLAYER_COLOR}" class="side_title color_{PLAYER_COLOR}">{PLAYER_NAME}</div>
            </div>
            <div id="title_sep_{PLAYER_COLOR}"></div>
            <!-- <div id="hand_icon" class="hand_icon"></div> -->
            <!-- <div id="hand_{PCOLOR}" class="hand hand_{PCOLOR}"> -->
                <!-- <div class="card"></div> -->
                <!-- </div> -->
            <div id="player_card_area_{PLAYER_ID}" class="player_card_area">
                <!-- <div class="card adventurer_card"></div> -->
                <!-- <div class="card treasure_card"></div> -->
                <!-- <div class="treasure_figure"></div> -->
            </div>
        </div>
        <!-- END player -->    
    </div>

    <!-- <div id="game_area_wrapper"> -->
        <!-- <div id="game_decks_wrapper" > -->
            <div id="flood_deck_area" class="flood_deck_area whiteblock" >
                <div id="flood_deck" class="card flood_card"></div>
                <div id="flood_card_area"></div>
            </div>
            <div id="treasure_deck_area" class="treasure_deck_area whiteblock" >
                <div id="treasure_deck" class="card treasure_card"></div>
                <div id="treasure_card_area"></div>
            </div>
        <!-- </div> -->
        <!-- <div id="water_level_meter" style="display: inline;"></div> -->
    <!-- </div> -->

</div>
    


<!-- <a href="#" id="my_button_id" class="bgabutton bgabutton_blue"><span>My blue button</span></a> -->

<script type="text/javascript">
 
// Templates

var jstpl_tile='<div class="island_tile" id="${id}" style="background-position:-${x}px -${y}px"></div>';
var jstpl_sunk_tile='<div class="island_tile_sunk" id="${id}"></div>';
var jstpl_pawn_area='<div id="pawn_area_${id}" class="pawn_area"></div>';
var jstpl_pawn='<div class="pawn" id="${id}" style="background-position:-${x}px"></div>';
// var jstpl_actions='<span class="remaining_actions" id="remaining_actions_text"><span id="remaining_actions_value" style="font-weight:bold;color:#ED0023;">${n}</span> actions: <span style="font-weight:bold;color:#4871b6;">Move</span> or </span>';
var jstpl_flood='<div class="card flood_card" id="flood_card_${id}" style="background-position:-${x}px -${y}px"></div>';
var jstpl_treasure='<div class="card treasure_card" id="treasure_card_${id}" style="background-position:-${x}px 0"></div>';
var jstpl_player='<div class="card player_card" id="player_card_${id}" style="background-position:-${x}px 0"></div>';

</script>  

{OVERALL_GAME_FOOTER}
