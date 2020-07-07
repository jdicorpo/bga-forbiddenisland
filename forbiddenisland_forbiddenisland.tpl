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
<div id="thething" class="thething">
<div id="outer_wrapper" class="anchor">
        <div id="board">
            <div id="island_tile" style="visibility: hidden;">
                <div id="pawn_area_0_0" class="pawn_area" style="visibility: hidden;"></div>
            </div>
            <!-- BEGIN island_tile -->
            <div id="island_tile_{X}_{Y}" class="island_tile_location" style="left: {LEFT}px; top: {TOP}px;">
            </div>
            <!-- END island_tile --> 

            <!-- BEGIN water_level_meter -->
            <div id="water_level_meter" style="left: {LEFT}px; top: {TOP}px;">

                <!-- BEGIN water_level -->
                    <div id="water_level_{LVL}" class="water_level" style="top:{TOP_LVL}px"></div>
                <!-- END water_level --> 

            </div>
            <!-- END water_level_meter --> 

            <!-- BEGIN treasure_starting_area -->
            <div id="starting_area_{TREASURE}" class="treasure_starting_area" style="left: {LEFT}px; top: {TOP}px;"></div>
            <!-- END treasure_starting_area -->
            
        </div>

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
            <div id="player_adventurer_{PLAYER_ID}" class="player_adventurer"></div>
            <div id="player_card_area_{PLAYER_ID}" class="player_card_area"></div>
            <div id="player_figure_area_{PLAYER_ID}" class="player_figure_area"></div>
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
        <!-- <div id="water_level_meter"></div> -->
    <!-- </div> -->

</div>
</div>
    


<!-- <a href="#" id="my_button_id" class="bgabutton bgabutton_blue"><span>My blue button</span></a> -->

<script type="text/javascript">
 
// Templates

var jstpl_tile='<div class="island_tile" id="${id}" style="background-position:-${x}px -${y}px"></div>';
var jstpl_sunk_tile='<div class="island_tile_sunk" id="${id}"></div>';
var jstpl_pawn_area='<div id="pawn_area_${id}" class="pawn_area"></div>';
var jstpl_pawn='<div class="pawn" id="${id}" style="background-position:-${x}px"></div>';
var jstpl_flood='<div class="card flood_card" id="flood_card_${id}" style="background-position:-${x}px -${y}px"></div>';
var jstpl_treasure='<div class="card treasure_card" id="treasure_card_${id}" style="background-position:-${x}px 0"></div>';
var jstpl_player='<div class="card player_card" id="player_card_${id}" style="background-position:-${x}px 0"></div>';
var jstpl_slider='<div id="water_slider"></div>';
var jstpl_figure='<div id="figure_${treasure}" class="treasure_figure" style="background-position:-${x}px 0"></div>';
var jstpl_figureicon='<div id="figureicon_${treasure}" class="figure_icon" style="background-position:-${x}px 0"></div>';
var jstpl_player_board = '\<div id="p_board_${id}" class="p_board">\
    <div class="adventurer_name" style="color:${color};">${adventurer}</div>\
    <div id="location_${id}" class="location">${location}</div>\
    <div id="p_board_icon_${id}" class="p_board_icons">\
    <div id="cards_${id}" class="card_icon"><span id="cardcount_${id}"  class="cardcount">0</span></div>\
    </div>\
</div>';

</script>  

{OVERALL_GAME_FOOTER}
