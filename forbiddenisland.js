/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * forbiddenisland implementation : © Jeff DiCorpo <jdicorpo@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * forbiddenisland.js
 *
 * forbiddenisland user interface script
 * 
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */
 
define([
    "dojo","dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter",
    "ebg/stock",
    "ebg/zone"
],
function (dojo, declare) {
    return declare("bgagame.forbiddenisland", ebg.core.gamegui, {
        constructor: function(){

            console.log('forbiddenisland constructor');

            // this.tilewidth = 128;
            // this.tileheight = 128;
            this.tilewidth = 147;
            this.tileheight = 147;

            this.tilewidth_lg = 220;
            this.tileheight_lg = 220;

            this.cardwidth = 147;
            this.cardheight = 204.13;

            this.figurewidth = 88.5;
            this.figureheight = 120;

            this.pawnwidth = 36.75;
            this.pawnheight = 65.4;
            this.pawn_area = [];

            this.flood_card_area = new ebg.stock();

            this.treasure_card_area = new ebg.stock();

            this.figure_area = [];
            this.figure_area['earth'] = new ebg.zone();
            this.figure_area['air'] = new ebg.zone();
            this.figure_area['fire'] = new ebg.zone();
            this.figure_area['ocean'] = new ebg.zone();

            this.player_adventurer = [];
            this.player_card_area = [];

            this.board = new ebg.stock();

            this.selectedAction = 'move';
            this.selectedCard = null;
            this.selectedPlayers = [];
            this.startingTile = null;
            this.possibleActions = [];
            this.player_treasure_cards = [];
            this.all_player_treasure_cards = [];

            this.previous_pagemaintitletext = "";

            this.colocated_players = [];
            this.playerLocations = [];

            this.clientStateArgs = {};

            this.isWinCondition = false;
            this.adventurer = '';

            this.handles = [];

            this.large_screen = true;

        },
        
        /*
            setup:
            
            This method must set up the game user interface according to current game situation specified
            in parameters.
            
            The method is called each time the game interface is displayed to a player, ie:
            _ when the game starts
            _ when a player refreshes the game page (F5)
            
            "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
        */
        
        setup: function( gamedatas )
        {
            console.log( "Starting game setup" );

            this.interface_max_width = gamedatas.interface_max_width;
            this.interface_max_height = gamedatas.interface_max_height;
            dojo.style('board','width', this.interface_max_width + 'px');
            dojo.style('board','height', this.interface_max_height + 'px');
            dojo.connect(window, "onresize", this, dojo.hitch(this, "adaptViewportSize"));

            this.board.create( this, $('island_tile'), this.tilewidth, this.tileheight );

            this.board.image_items_per_row = 8;

            this.flood_discards = Object.keys(gamedatas.flood_card_area).map( function(key, index) {
                return gamedatas.flood_card_area[key].type;
            });

            // Setup board - place tiles on map
            for (var tile in gamedatas.tile_list) {
                var img_id = gamedatas.tile_list[tile].img_id;
                this.board.addItemType(tile, tile, g_gamethemeurl + 'img/tiles.jpg', img_id);
                this.pawn_area[tile] = new ebg.zone();
                this.pawn_area[tile].setPattern( 'custom' );
                this.pawn_area[tile].itemIdToCoords = function( i, control_width ) {
                    if( i%4==0 )
                    {   return {  x:10, y:-30, w:45, h:80 }; }
                    else if( i%4==1 )
                    {   return {  x:40, y:0, w:45, h:80 }; }
                    else if( i%4==2 )
                    {   return {  x:70, y:-30, w:45, h:80 }; }
                    else if( i%4==3 )
                    {   return {  x:100, y:0, w:45, h:80 }; }
                };
            }

            for( var i in gamedatas.unflooded )
            {
                var tile = gamedatas.unflooded[i];
                var x = Math.trunc( tile.location_arg / 10) ;
                var y = tile.location_arg % 10;
                
                this.placeTile( x, y, tile.type, flooded = false, sunk = false);
            }

            for( var i in gamedatas.flooded )
            {
                var tile = gamedatas.flooded[i];
                var x = Math.trunc( tile.location_arg / 10) ;
                var y = tile.location_arg % 10;

                this.placeTile( x, y, tile.type, flooded = true, sunk = false);
            }

            for( var i in gamedatas.sunk )
            {
                var tile = gamedatas.sunk[i];
                var x = Math.trunc( tile.location_arg / 10) ;
                var y = tile.location_arg % 10;

                this.placeTile( x, y, tile.type, flooded = true, sunk = true);
            }

            // Setting up player boards
            var treasures = ['earth', 'fire', 'air', 'ocean'];
            for( var player_id in gamedatas.players )
            {
                var player = gamedatas.players[player_id];
                this.placePawn( player_id, gamedatas.player_list[player.adventurer].pawn_idx, player.location);
                this.player_adventurer[player_id] = new ebg.zone();
                this.player_card_area[player_id] = new ebg.stock();
                this.player_adventurer[player_id].create( this, 'player_adventurer_' + player_id, this.cardwidth, this.cardheight);
                this.player_card_area[player_id].create( this, $('player_card_area_' + player_id), this.cardwidth, this.cardheight);
                this.player_card_area[player_id].image_items_per_row = 8;
                this.player_card_area[player_id].setOverlap(40,0);
                for (var card in gamedatas.treasure_list) {
                    var idx = gamedatas.treasure_list[card].idx - 1;
                    this.player_card_area[player_id].addItemType(card, idx, g_gamethemeurl + 'img/treasure.jpg', idx);
                }
                this.placePlayer(player_id, gamedatas.player_list[player.adventurer].idx);
                this.figure_area[player_id] = new ebg.zone();
                this.figure_area[player_id].create( this, 'player_figure_area_' + player_id, this.figurewidth * 0.8, this.figureheight * 0.8);
                this.figure_area[player_id].tiem_margin = 5;
                this.placeAllTreasureCards(player_id, gamedatas.player_card_area[player_id].treasure_cards);
                var playerBoardDiv = dojo.byId('player_board_' + player_id);
                var x = 80 * (gamedatas.player_list[player.adventurer].idx-1);
                dojo.place(this.format_block('jstpl_player_board', {
                    id: player_id,
                    adventurer: gamedatas.player_list[player.adventurer].name,
                    location: gamedatas.tile_list[player.location].name,
                    color: player.color,
                    x: x
                }), playerBoardDiv);
                $('cardcount_' + player_id).innerHTML = Object.keys(gamedatas.player_card_area[player_id].treasure_cards).length;
                treasures.forEach( function(treasure, index) {
                    if (gamedatas[treasure] == player_id) {
                        var x = this.gamedatas.treasure_list[treasure].fig * 25;
                        dojo.place(this.format_block('jstpl_figureicon', {
                            treasure: treasure,
                            x: x
                        }), 'p_board_icon_' + player_id, 'last');
                    }
                }, this);
            }
            // setup the flood deck area
            this.flood_card_area.create( this, $('flood_card_area'), this.cardwidth, this.cardheight);
            this.flood_card_area.image_items_per_row = 5;
            this.flood_card_area.setOverlap(40,0);
            for (var card in gamedatas.flood_list) {
                var idx = gamedatas.flood_list[card].img_id - 1;
                this.flood_card_area.addItemType(card, 1, g_gamethemeurl + 'img/flood.jpg', idx);
            }
            for( var card_id in gamedatas.flood_card_area )
            {
                var card = gamedatas.flood_card_area[card_id]
                this.placeFloodCard(card.type);
            }

            // setup the flood deck area
            this.treasure_card_area.create( this, $('treasure_card_area'), this.cardwidth, this.cardheight);
            this.treasure_card_area.image_items_per_row = 8;
            this.treasure_card_area.setOverlap(40,0);
            for (var card in gamedatas.treasure_list) {
                var idx = gamedatas.treasure_list[card].idx - 1;
                this.treasure_card_area.addItemType(card, 1, g_gamethemeurl + 'img/treasure.jpg', idx);
            }
            for( var card_id in gamedatas.treasure_discards )
            {
                var card = gamedatas.treasure_discards[card_id]
                this.discardTreasure(card_id, 0, card.type, true);
            }

            treasures.forEach( function(treasure, index) {
                this.figure_area[treasure].create( this, 'starting_area_' + treasure, this.figurewidth, this.figureheight );
                this.figure_area[treasure].setFluidWidth();
                this.placeFigure(treasure, this.gamedatas[treasure]);
            }, this);

            this.placeWaterLevel(this.gamedatas.water_level);
            // $('island_name').innerHTML = gamedatas['island_name'];
            // $('difficulty_level').innerHTML = '[ ' + gamedatas['difficulty'] + ' ]';

            // Setup game notifications to handle (see "setupNotifications" method below)
            this.setupNotifications();

            $('cardcount_flood_deck').innerHTML = gamedatas['flood_deck_count'];
            $('cardcount_treasure_deck').innerHTML = gamedatas['treasure_deck_count'];
            this.addTooltip( 'cardcount_flood_deck', _('remaining cards in deck'), '' );
            this.addTooltip( 'cardcount_treasure_deck', _('remaining cards in deck'), '' );

            this.adjustScreenLayout();

            console.log( "Ending game setup" );
        },

        board_width : function(isLarge) {
            if ( this.large_screen || isLarge ) {
                // return width + 147 + 13;
                return this.interface_max_width + 147 + 13 + 240 + 200;
            } else {
                return this.interface_max_width + 147 + 13 + 150;
            }
        },

        adjustScreenLayout : function() {
            var pageid = "page-content";
            var bodycoords = dojo.marginBox(pageid);
            var contentWidth = bodycoords.w;

            // if (contentWidth >= 1600) {
            if (contentWidth >= this.board_width(true)) {
                dojo.addClass('flood_deck_area','flood_deck_area_lg_screen');
                dojo.place('flood_deck_area','board', 'before');
                dojo.style('board', 'margin-left','300px');
                dojo.style('cardcount_flood_deck', 'transform','rotate(-90deg)');
                // dojo.place('flood_deck_area','board', 'after');
                // this.interface_max_width = this.gamedatas.interface_max_width;
                // this.interface_max_width = this.gamedatas.interface_max_width + 300;
                this.large_screen = true;
                // this.flood_card_area.updateDisplay();
                // console.log("large_screen = ", this.large_screen);
            // }
                dojo.place('water_level_meter','board_wrapper', 'last');
                dojo.removeClass('water_level_meter', 'water_level_meter_sm_screen');
                dojo.style('water_level_meter', 'left','50px');
            } else if (this.large_screen) {
                
                dojo.removeClass('flood_deck_area','flood_deck_area_lg_screen');
                dojo.place('flood_deck_area','treasure_deck_area', 'before');
                // dojo.style('board', 'margin','auto');
                dojo.style('board', 'margin-left','0px');
                dojo.style('cardcount_flood_deck', 'transform','rotate(0deg)');
                // this.interface_max_width = this.gamedatas.interface_max_width;
                this.large_screen = false;
                // console.log("large_screen = ", this.large_screen);

                dojo.place('water_level_meter','board', 'last');
                dojo.addClass('water_level_meter', 'water_level_meter_sm_screen');
                dojo.style('water_level_meter', 'left',this.interface_max_width + 30 + 'px');
            }

            // console.log(this.large_screen + ':' + this.board_width() + '<->' + contentWidth);
            
        },
       
        adaptViewportSize : function() {
            // var pageid = "game_play_area";
            var pageid = "page-content";
            var nodeid = "thething";
    
            var bodycoords = dojo.marginBox(pageid);
            var contentWidth = bodycoords.w;
            // var board_width = this.board_width(this.interface_max_width);
    
            var browserZoomLevel = window.devicePixelRatio; 
            // console.log("zoom",browserZoomLevel);
            // console.log("contentWidth", contentWidth);

            this.adjustScreenLayout();

            // if (contentWidth >= this.interface_max_width || browserZoomLevel >1  || this.control3dmode3d) {
            if (contentWidth >= this.board_width() || this.control3dmode3d) {
            // if (this.large_screen || this.control3dmode3d) {
                dojo.style(nodeid,'transform','');
                dojo.style(nodeid,'-webkit-transform','');
                // console.log("contentWidth", contentWidth, '>', board_width);
                return;
            }
    
            var scale_percent = contentWidth / this.board_width();
            // console.log("scale = ", scale_percent);

            dojo.style(nodeid, "transform", "scale(" + scale_percent + ")");
            dojo.style(nodeid, "-webkit-transform", "scale(" + scale_percent + ")");

        },

        ///////////////////////////////////////////////////
        //// Game & client states
        
        // onEnteringState: this method is called each time we are entering into a new game state.
        //                  You can use this method to perform some user interface changes at this moment.
        //
        onEnteringState: function( stateName, args )
        {
            console.log( 'Entering state: '+stateName );
            
            switch( stateName )
            {
            
            case 'continue':
            case 'playerActions':
                this.selectedAction = 'move';
                this.possibleActions = args.args.possibleActions;
                this.all_player_treasure_cards = args.args.player_treasure_cards;
                if (!this.isSpectator) {
                    var obj = args.args.player_treasure_cards[this.player_id];
                    this.player_treasure_cards = Object.keys(obj).map(function(key) {
                        return obj[key];
                    });
                }
                // $('cardcount_' + this.player_id).innerHTML = this.player_treasure_cards.length;
                if (args.args.remaining_actions > 0) {
                    this.updatePossibleMoves( this.possibleActions.move );
                }
                var obj = args.args.colocated_players;
                this.colocated_players = Object.keys(obj).map(function(key) {
                    return obj[key];
                });
                this.isWinCondition = args.args.isWinCondition;
                this.adventurer = args.args.adventurer;
                this.pilot_action = args.args.pilot_action;
                this.special_action = false;
                // this.selectedCard = 'treasure_card_' + args.args.special_card_id;
                this.selectedCard = 'player_card_area_' + this.player_id + '_item_' + args.args.special_card_id;
                this.playerLocations = args.args.playerLocations;
                this.remaining_actions = args.args.remaining_actions;
                var cards = args.args.flood_discards;
                this.flood_discards = Object.keys(cards).map(function(key) {
                    return cards[key];
                });

                break;

            case 'bonusShoreup':
                this.selectedCard = null;
                this.selectedAction = 'bonus_shoreup';
                this.possibleActions = args.args.possibleActions;
                this.adventurer = args.args.adventurer;
                this.updatePossibleMoves(this.possibleActions.shore_up);
                var cards = args.args.flood_discards;
                this.flood_discards = Object.keys(cards).map(function(key) {
                    return cards[key];
                });
                break;

            case 'discardTreasure':
                this.all_player_treasure_cards = args.args.player_treasure_cards;
            case 'drawFlood':
                var cards = args.args.flood_discards;
                this.flood_discards = Object.keys(cards).map(function(key) {
                    return cards[key];
                });
                // for multipleactiveplayer states, onUpdateActionButtons id called before onEnteringState
                break;

            case 'client_selectShoreUp':
            case 'client_selectGiveCard':
            case 'client_selectSpecialCard':
                this.selectedCard = null;
                break;

            case 'sandbags':
                this.selectedAction = 'sandbags';
                this.possibleActions = args.args.possibleActions;
                this.updatePossibleMoves( this.possibleActions.sandbags );
                this.adventurer = args.args.adventurer;
                // this.selectedCard = 'treasure_card_' + args.args.special_card_id;
                this.selectedCard = 'player_card_area_' + this.player_id + '_item_' + args.args.special_card_id;
                break;

            case 'heli_lift':
                this.selectedAction = 'heli_lift';
                this.startingTile = null;
                this.playerLocations = args.args.playerLocations;
                this.possibleActions = args.args.possibleActions;
                this.updatePossibleMoves( this.playerLocations.map( function(player) {
                    return player.location;
                }));
                this.adventurer = args.args.adventurer;
                // this.selectedCard = 'treasure_card_' + args.args.special_card_id;
                this.selectedCard = 'player_card_area_' + this.player_id + '_item_' + args.args.special_card_id;
                break;

            case 'navigator':
                this.selectedAction = 'navigator';
                this.possibleActions = args.args.possibleActions;
                this.updatePossibleMoves( this.possibleActions.move );
                break;

            case 'client_selectHeliLiftPlayers':
                this.clearLastAction();
                var target_players = this.getPlayersAtLocation(this.startingTile);
                if (target_players.length > 1) {
                    // update possible pawns to be selected
                    this.updatePossiblePawns(target_players);
                } else {
                    this.selectedPlayers = target_players;
                    this.setClientState("client_selectHeliLiftDest", 
                    { descriptionmyturn : "${you} are playing Helicopter Lift. Select a destination tile."});
                }
                break;

            case 'client_selectHeliLiftDest':
            case 'client_selectPilotDest':
                // this.updatePossibleMoves( this.possibleActions.heli_lift );
                this.updatePossibleMoves( this.possibleActions.heli_lift.map( function(tile) {
                    if (tile != this.startingTile) return tile;
                }, this));
                break;
        
            case 'client_selectNavigatorPlayer':
                this.clearLastAction();
                var players = Object.keys(this.gamedatas.players);
                var target_players = [];
                for (x in players) {
                    if (players[x] != this.player_id) {
                        target_players.push(players[x]);
                    }
                }
                // if (target_players.length > 1) {  // TODO: Change back for production
                if (target_players.length > 0) {
                    // update possible pawns to be selected
                    this.updatePossiblePawns(target_players);
                } else {
                    this.setClientState("client_selectNavigatorDest", 
                        { descriptionmyturn : "Navigator: ${you} must select a destination."});
                }
                break;

            case 'client_selectNavigatorDest':
                this.updatePossibleMoves( this.possibleActions.navigator[this.selectedPlayers[0]] );
                break;

            case 'client_confirmWinGame':
                break;

            case 'rescuePawn':
                // this.clearLastAction();
                break;

            case 'dummmy':
                break;
            }
        },

        // onLeavingState: this method is called each time we are leaving a game state.
        //                 You can use this method to perform some user interface changes at this moment.
        //
        onLeavingState: function( stateName )
        {
            console.log( 'Leaving state: '+stateName );
            
            switch( stateName )
            {
            
            case 'dummmy':
            default:
                break;

            }               
        }, 

        // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
        //                        action status bar (ie: the HTML links in the status bar).
        //        
        onUpdateActionButtons: function( stateName, args )
        {
            console.log( 'onUpdateActionButtons: '+stateName );

            if( this.isCurrentPlayerActive() )
            {   
                switch( stateName )
                {
                    case 'playerActions':
                        var main = $('pagemaintitletext');
                        if (args.remaining_actions > 0) {
                            main.innerHTML += _('you may take ') + '<span id="remaining_actions_value" style="font-weight:bold;color:#ED0023;">' 
                                + args.remaining_actions + '</span>' + _(' action(s): ') + '<span style="font-weight:bold;color:#4871b6;">' 
                                + _('Move') + '</span>' + _(' or ');
                            if ((args.adventurer == 'pilot') && (args.pilot_action == 1)) {
                                this.addActionButton( 'pilot_btn', _('Pilot'), 'onPilot' ); 
                            } else if (args.adventurer == 'navigator') {
                                this.addActionButton( 'navigator_btn', _('Navigator'), 'onNavigator' ); 
                            }
                            this.addActionButton( 'shore_up_btn', _('Shore Up'), 'onShoreUp' ); 
                            this.addActionButton( 'give_treasure_btn', _('Give Card'), 'onGiveCard' ); 
                            this.addActionButton( 'capture_treasure_btn', _('Capture Treasure'), 'onCapture' ); 
                            this.addActionButton( 'skip_btn', _('End Turn'), 'onSkip', null, false, 'gray' ); 
                        } else {
                            main.innerHTML += _(' have ') + '<span id="remaining_actions_value" style="font-weight:bold;color:#ED0023;">' 
                            + args.remaining_actions + '</span>' + _(' remaining actions: ');

                            this.addActionButton( 'skip_btn', _('End Turn'), 'onSkip', null, false, 'blue' ); 
                        }
                        if (args.undo) {
                            this.addActionButton( 'undo_btn', _('Undo'), 'onUndo', null, false, 'gray' ); 
                        }
                        break;

                    case 'bonusShoreup':
                        this.addActionButton( 'skip_btn', _('Skip'), 'onSkip', null, false, 'gray' ); 
                        break;
                        
                    case 'discardTreasure':
                        // for multipleactiveplayer state, onUpdateActionButtons id called before onEnteringState
                        var obj = args.player_treasure_cards[this.player_id];
                        this.player_treasure_cards = Object.keys(obj).map(function(key) {
                            return obj[key];
                        });
                        if (args.discard_treasure_player == this.player_id) {
                            this.selectedAction = 'discard';
                            this.updatePossibleCards( this.player_treasure_cards, give=false );
                        }
                        break;

                    case 'rescuePawn':
                        this.selectedAction = 'rescue';
                        this.possibleActions = args.possibleActions[this.player_id];
                        var obj = args.player_treasure_cards[this.player_id];
                        this.player_treasure_cards = Object.keys(obj).map(function(key) {
                            return obj[key];
                        });
                        this.adventurer = args.adventurer[this.player_id];
                        // $('cardcount_' + this.player_id).innerHTML = this.player_treasure_cards.length;
                        this.special_action = false;
                        if (this.adventurer == 'pilot') {
                            this.updatePossibleMoves( this.possibleActions.heli_lift );
                        } else {
                            this.updatePossibleMoves( this.possibleActions.move );
                        }
                        // var obj = args.colocated_players;
                        // this.colocated_players = Object.keys(obj).map(function(key) {
                        //     return obj[key];
                        // });
                        this.isWinCondition = args.isWinCondition;
                        this.pilot_action = args.pilot_action;
                        break;

                    case 'client_selectShoreUp':
                    case 'client_selectGiveCard':
                    case 'client_selectGiveCardPlayers':
                    case 'client_selectPilotDest':
                        this.addActionButton( 'cancel_btn', _('Cancel'), 'onCancel', null, false, 'red' );
                        break;

                    case 'sandbags':
                    case 'heli_lift':
                        this.addActionButton( 'cancel_btn', _('Cancel'), 'onCancel', null, false, 'red' );
                        break;

                    case 'continue':
                        this.addActionButton( 'continue_btn', _('Continue'), 'onContinue', null, false, 'blue' );
                        break;

                    case 'client_selectHeliLiftPlayers':
                        
                        this.addActionButton( 'done_btn', _('Done'), 'onDone', null, false, 'blue' );
                        this.addActionButton( 'cancel_btn', _('Cancel'), 'onCancel', null, false, 'red' );
                        break;

                    case 'client_selectNavigatorPlayer':
                    case 'client_selectNavigatorDest':
                    
                        this.addActionButton( 'cancel_btn', _('Cancel'), 'onCancel', null, false, 'red' );
                        break;

                    case 'client_selectHeliLiftDest':
                        this.addActionButton( 'cancel_btn', _('Cancel'), 'onCancel', null, false, 'red' );
                        break;

                    case 'client_selectChooseDiscardSpecial':
                        this.addActionButton( 'play_btn', _('Play'), 'onPlayDiscard', null, false, 'blue' );
                        this.addActionButton( 'discard_btn', _('Discard'), 'onDiscard', null, false, 'red' );
                        this.addActionButton( 'cancel_btn', _('Cancel'), 'onCancel', null, false, 'gray' );
                        break;
                        
                    case 'client_confirmDiscard':
                        this.addActionButton( 'discard_btn', _('Discard'), 'onDiscard', null, false, 'red' );
                        this.addActionButton( 'cancel_btn', _('Cancel'), 'onCancel', null, false, 'gray' );
                        break;
                        
                    case 'client_endTurn':
                        this.addActionButton( 'confirm_btn', _('Confirm'), 'onConfirm', null, false, 'red' );
                        this.addActionButton( 'cancel_btn', _('Cancel'), 'onCancel', null, false, 'gray' );
                        break;

                    case 'client_confirmWinGame':
                        this.addActionButton( 'confirm_btn', _('Confirm'), 'onConfirm', null, true, 'red' );
                        this.addActionButton( 'cancel_btn', _('Cancel'), 'onCancel', null, false, 'gray' );
                        break;

                    default:
                        break;
                }
            }

            switch( stateName )
            {
                // these states can all occur out of turn
                case 'playerActions':
                    if (!this.isCurrentPlayerActive() ) {
                        var main = $('pagemaintitletext');
                        if (args.remaining_actions > 0) {
                            main.innerHTML += _(' is taking ') + '<span id="remaining_actions_value" style="font-weight:bold;color:#ED0023;">' 
                            + args.remaining_actions + '</span>' + _(' remaining action(s).');
                        } else {
                            main.innerHTML += _(' has ') + '<span id="remaining_actions_value" style="font-weight:bold;color:#ED0023;">' 
                            + args.remaining_actions + '</span>' + _(' remaining actions.');
                        }
                    }
                case 'continue':
                    if (!this.isSpectator && this.hasSpecialCard(args.player_treasure_cards[this.player_id])) {
                        this.addActionButton( 'player_special_btn', _('Play Special'), 'onPlaySpecial', null, false, 'red' ); 
                    }
                    break;

                case 'client_selectSpecialCard':
                    var main = $('pagemaintitletext');
                    main.innerHTML = "You must select a special card";
                    this.addActionButton( 'cancel_btn', _('Cancel'), 'onCancel', null, false, 'red' );
                    break;

                default:
                    break;
            }
        },        

        ///////////////////////////////////////////////////
        //// Utility methods
        
        /*
        
            Here, you can defines some utility methods that you can use everywhere in your javascript
            script.
        
        */

        hasSpecialCard: function(cards) {
            if (typeof cards !== 'undefined') {
                for (var id in cards) {
                    if ((cards[id].type == 'sandbags' || cards[id].type == 'heli_lift')) {
                        return true;
                    }
                };
            }
            return false;
        },

        updatePossibleCards: function(cards, give) {

            this.clearLastAction();
            if (typeof cards !== 'undefined') {
                cards.forEach(
                    function (c, index) {
                        var node = $('player_card_area_' + this.player_id + '_item_' + c.id);
                        if (!(give && (c.type == 'sandbags' || c.type == 'heli_lift'))) {
                            dojo.addClass(node, 'possibleCard');
                            this.handles.push(dojo.connect(node,'onclick', this, 'onCard'));
                        }
                    }, this);
            }
        },

        updateSpecialCards: function(cards) {

            this.clearLastAction();
            if (typeof cards !== 'undefined') {
                cards.forEach(
                    function (c, index) {
                        var node = $('player_card_area_' + this.player_id + '_item_' + c.id);
                        if (c.type == 'sandbags' || c.type == 'heli_lift') {
                            dojo.addClass(node, 'possibleCard');
                            this.handles.push(dojo.connect(node,'onclick', this, 'onCard'));
                        }
                    }, this);
                }
        },

        updateColocatedPlayers: function(players) {

            this.clearLastAction();

            if (typeof players !== 'undefined') {
                players.forEach(
                    function (p, index) {
                        if (p.player_id != this.player_id) {
                            var node = $('player_card_area_' + p.player_id);
                            dojo.addClass(node, 'possiblePlayer');
                            this.handles.push(dojo.connect(node,'onclick', this, 'onPlayer'));
                            var node = $('player_card_' + p.player_id);
                            dojo.addClass(node, 'possiblePlayer');
                            this.handles.push(dojo.connect(node,'onclick', this, 'onPlayer'));
                        }
                    }, this);
                }
        },

        updateAllPlayers: function() {

            this.clearLastAction();

            var players = this.gamedatas.players;
            for (var player_id in players) {
                if (player_id != this.player_id) {
                    var node = $('player_card_area_' + player_id);
                    dojo.addClass(node, 'possiblePlayer');
                    this.handles.push(dojo.connect(node,'onclick', this, 'onPlayer'));
                    var node = $('player_card_' + player_id);
                    dojo.addClass(node, 'possiblePlayer');
                    this.handles.push(dojo.connect(node,'onclick', this, 'onPlayer'));
                }
            }
        },

        setBackground: function(level) {
            var bg = "";

            if (level > 7) {
                bg  = "bg_5";
            } else if (level > 5) {
                bg  = "bg_4";
            } else if (level > 2) {
                bg  = "bg_3";
            } else {
                bg  = "bg_2";
            }
            dojo.query('.dj_contentbox').addClass(bg);
            // dojo.query('#game_play_area').addClass(bg);

        },

        placeWaterLevel: function(level) {

            dojo.place(this.format_block('jstpl_slider', {
                level : level
            }), 'water_level_' + level, 'only');

            this.setBackground(level);

        },

        moveWaterLevel: function(level) {

            this.slideToObject( 'water_slider', 'water_level_'+level).play();

            this.setBackground(level);

        },

        isTileInDiscard : function(tile_id) {
            if (this.flood_discards.includes(tile_id)) {
                return true;
            } else {
                return false;
            }
        },

        placeTile : function(a, b, tile_id, flooded, sunk) {
            console.log( 'placeTile' );

            var board_id = a + '_' + b;
            var warning = (this.isTileInDiscard(tile_id) ? 'inline' : 'none');
            var img_id = this.gamedatas.tile_list[tile_id].img_id;

            if (sunk) {
                img_id += 24;
                dojo.place(this.format_block('jstpl_sunk_tile', {
                    id : tile_id,
                }), 'island_tile_' + board_id, 'first');
            } else if (flooded) {
                img_id += 24;
                dojo.place(this.format_block('jstpl_flooded_tile', {
                    x : this.tilewidth * ((img_id-1) % 8),
                    y : this.tileheight * Math.trunc((img_id-1) / 8),
                    id : tile_id,
                    warning: warning
                }), 'island_tile_' + board_id, 'first');
            } else {
                dojo.place(this.format_block('jstpl_tile', {
                    x : this.tilewidth * ((img_id-1) % 8),
                    y : this.tileheight * Math.trunc((img_id-1) / 8),
                    id : tile_id,
                    warning: warning
                }), 'island_tile_' + board_id, 'first');
            }
            
            dojo.place(this.format_block('jstpl_pawn_area', {
                id : tile_id,
            }), 'island_tile_' + board_id);
            
            this.pawn_area[tile_id].create( this, 'pawn_area_' + tile_id, this.pawnwidth - 7, this.pawnheight);

            this.addTileTooltip(tile_id, flooded, sunk);

        },

        floodTile : function(tile_id) {
            console.log( 'floodTile' );

            var img_id = this.gamedatas.tile_list[tile_id].img_id + 24;
            var warning = (this.isTileInDiscard(tile_id) ? 'inline' : 'none');
            
            this.fadeOutAndDestroy( tile_id + '_bg', 2000, 0);

            setTimeout(() => { 
                dojo.place(this.format_block('jstpl_flooded_tile', {
                    x : this.tilewidth * ((img_id-1) % 8),
                    y : this.tileheight * Math.trunc((img_id-1) / 8),
                    id : tile_id,
                    warning: warning
                }), tile_id, 'replace');
            }, 2000);

            dojo.query('#'+tile_id + ' .tile_mark', 'display', 'inline');

            this.addTileTooltip(tile_id, true, false);

        },

        unfloodTile : function(tile_id) {
            console.log( 'unfloodTile' );

            var img_id = this.gamedatas.tile_list[tile_id].img_id;
            var warning = (this.isTileInDiscard(tile_id) ? 'inline' : 'none');
            
            dojo.place(this.format_block('jstpl_tile', {
                x : this.tilewidth * ((img_id-1) % 8),
                y : this.tileheight * Math.trunc((img_id-1) / 8),
                id : tile_id,
                warning: warning
            }), tile_id, 'replace');

            // dojo.style( tile_id + '_bg', 'display', 'inline' );

            this.addTileTooltip(tile_id, false, false);

        },

        sinkTile : function(tile_id) {
            console.log( 'sinkTile' );

            var parent_id = $(tile_id).parentNode.id;
            var img_id = this.gamedatas.tile_list[tile_id].img_id + 24;

            this.fadeOutAndDestroy( tile_id, 2000, 1000);

            dojo.place(this.format_block('jstpl_sunk_tile', {
                id : tile_id,
            }), parent_id, 'first');

            this.addTileTooltip(tile_id, false, true);

        },

        addTileTooltip : function(tile_id, flooded, sunk) {

            var img_id = this.gamedatas.tile_list[tile_id].img_id;
            var text_style = "";
            if (sunk) {
                var flooded_text = _('SUNK');
                img_id += 24;
                text_style = "color: black";
            } else if (flooded) {
                var flooded_text = _('FLOODED');
                img_id += 24;
                text_style = "color: blue";
            } else {
                var flooded_text = _('UNFLOODED');
                text_style = "color: green";
            }

            var parent_id = $(tile_id).parentNode.id;
            var tile_title = this.gamedatas.tile_list[tile_id].name;

            this.removeTooltip(parent_id);
            this.addTooltipHtml(parent_id, this.format_block('jstpl_tile_tooltip', {
                x : this.tilewidth_lg * ((img_id-1) % 8),
                y : this.tileheight_lg * Math.trunc((img_id-1) / 8),
                flooded_text : flooded_text,
                tile_title: tile_title,
                text_style: text_style
            }), );

            var pawn_area_id = dojo.query('#'+parent_id + ' .pawn_area').id;
            this.removeTooltip(pawn_area_id);
            this.addTooltipHtml(pawn_area_id, this.format_block('jstpl_tile_tooltip', {
                x : this.tilewidth_lg * ((img_id-1) % 8),
                y : this.tileheight_lg * Math.trunc((img_id-1) / 8),
                flooded_text : flooded_text,
                tile_title: tile_title,
                text_style: text_style
            }), );

            // this.addTooltipToClass( 'tile_mark', _('this flood card is in discard pile'), '');
            this.addTooltip( tile_id + '_mark', _('this flood card is in discard pile'), '');

        },

        placePlayer : function(player_id, idx) {
            console.log( 'placePlayer' );
            var x = this.cardwidth * (idx-1);
            var player = this.gamedatas.players[player_id];
            var tooltip = this.gamedatas.player_list[player.adventurer].tooltip;
            
            dojo.place(this.format_block('jstpl_player', {
                id : player_id,
                x : x,
            }), 'player_adventurer_' + player_id, 'only');

            this.player_adventurer[player_id].placeInZone('player_card_' + player_id);

            this.addTooltip( 'player_card_' + player_id, tooltip, '' );


        },

        placeFloodCard : function(id) {
            console.log( 'placeFloodCard' );

            var idx = this.gamedatas.flood_list[id].img_id
            var tooltip = this.gamedatas.flood_list[id].name;

            if (this.flood_card_area.count() >= 10) {
                this.flood_card_area.setOverlap(20,0);
            } else {
                this.flood_card_area.setOverlap(40,0);
            }

            this.flood_card_area.addToStockWithId(id, id, 'flood_deck');

            this.addTooltip( this.flood_card_area.getItemDivId(id), tooltip, '' );

        },

        removeFlood : function(id) {

            console.log( 'removeFlood' );

            this.flood_card_area.removeFromStockById(id, 'flood_deck');

        },

        removeAllFlood : function() {

            console.log( 'removeAllFlood' );

            var cards = this.flood_card_area.getAllItems();
            for (var c in cards) {
                this.flood_card_area.removeFromStockById(cards[c].id, 'flood_deck', true);
            }
            this.flood_card_area.updateDisplay();

            dojo.query('.tile_mark').style('display', 'none');
            this.flood_discards = [];

        },
 
        placeTreasureCard : function(id, type, player_id) {
            console.log( 'placeTreasureCard' );

            var idx = this.gamedatas.treasure_list[type].idx;
            var location = 'player_card_area_' + player_id;
            var zone = this.player_card_area[player_id];
            var x = this.cardwidth * (idx-1);

            this.player_card_area[player_id].addToStockWithId(type, id, 'treasure_deck');
            
            if ('tooltip' in this.gamedatas.treasure_list[type]) {
                var tooltip = this.gamedatas.treasure_list[type].tooltip;
                this.addTooltip( this.player_card_area[player_id].getItemDivId(id), tooltip, '' );
            }


        },

        placeAllTreasureCards : function(player_id, cards) {
            
            for( var card_id in cards )
            {
                this.placeTreasureCard(card_id, cards[card_id].type, player_id);
            }
        
        },

        discardTreasure : function(id, player_id, type, place) {
            console.log( 'discardTreasure' );

            if (place) {

                this.treasure_card_area.addToStockWithId(type, id);

            } else {

                this.treasure_card_area.addToStockWithId(type, id, this.player_card_area[player_id].getItemDivId(id));

                this.player_card_area[player_id].removeFromStockById(id);

            }

            if (type != null) {
                if ('tooltip' in this.gamedatas.treasure_list[type]) {
                    var tooltip = this.gamedatas.treasure_list[type].tooltip;
                    this.addTooltip( this.treasure_card_area.getItemDivId(id), tooltip, '' );
                }
            }

        },

        moveTreasure : function(id, player_id, target_player_id) {
            console.log( 'moveTreasure' );

            var card = this.player_card_area[player_id].getItemById(id)
            this.player_card_area[target_player_id].addToStockWithId(card.type, id, this.player_card_area[player_id].getItemDivId(id));
            this.player_card_area[player_id].removeFromStockById(id);

            if ('tooltip' in this.gamedatas.treasure_list[card.type]) {
                var tooltip = this.gamedatas.treasure_list[card.type].tooltip;
                this.addTooltip( this.player_card_area[target_player_id].getItemDivId(id), tooltip, '' );
            }

        },

        removeAllTreasure : function() {

            console.log( 'removeAllTreasure' );

            var cards = this.treasure_card_area.getAllItems();
            for (var c in cards) {
                this.treasure_card_area.removeFromStockById(cards[c].id, 'treasure_deck', true);
            }
            this.treasure_card_area.updateDisplay();

        },

        placeFigure : function(treasure, player_id) {

            console.log( 'placeFigure' );
            x = this.gamedatas.treasure_list[treasure].fig * 88.5;
            var tooltip = this.gamedatas.treasure_list[treasure].name;


            if (player_id == 0) {
                dojo.place(this.format_block('jstpl_figure', {
                    treasure : treasure,
                    x : x
                }), 'starting_area_' + treasure);
                this.figure_area[treasure].placeInZone('figure_' + treasure);
            } else {
                dojo.place(this.format_block('jstpl_figure', {
                    treasure : treasure,
                    x : x
                }), 'player_figure_area_' + player_id);
                this.figure_area[player_id].placeInZone('figure_' + treasure);
            }

            this.addTooltip( 'figure_' + treasure, tooltip, '' );

        },

        moveFigure : function(treasure, player_id) {

            console.log( 'moveFigure' );

            this.figure_area[player_id].placeInZone('figure_' + treasure);

        },

        placePawn : function(player_id, idx, tile_id) {

            console.log( 'placePawn' );

            var parent_id = $(tile_id).parentNode.id;
            var pawn_area = dojo.query('#' + parent_id + ' .pawn_area')[0];
            // var x = 36.18 * (idx-1);
            var x = 45.22 * (idx-1);

            dojo.place(this.format_block('jstpl_pawn', {
                id : player_id,
                x : x,
            }), pawn_area, 'last');
            this.pawn_area[tile_id].placeInZone(player_id);

            this.addTooltipHtml( player_id, this.pawnTooltip(player_id), '');

        },

        placePawnSelect : function(player_id) {

            console.log( 'placePawnSelect' );

            var main = $('pagemaintitletext');
            var player = this.gamedatas.players[player_id];
            var idx = this.gamedatas.player_list[player.adventurer].pawn_idx;
            // var parent_id = $(tile_id).parentNode.id;
            // var pawn_area = dojo.query('#' + parent_id + ' .pawn_area')[0];
            // var x = 36.18 * (idx-1);
            var x = 45.22 * (idx-1);

            dojo.place(this.format_block('jstpl_pawn', {
                id : 'pselect_' + player_id,
                x : x,
            }), main, 'last');
            // this.pawn_area[tile_id].placeInZone(player_id);

        },

        movePawn : function(tile_id, player_id) {

            console.log( 'movePawn' );

            var from_tile_id = $(player_id).parentNode.id.slice(10);
            this.pawn_area[from_tile_id].removeFromZone(player_id, false);

            this.pawn_area[tile_id].placeInZone(player_id);

            this.addTooltipHtml( player_id, this.pawnTooltip(player_id), '');

            // setTimeout(() => { $('location_' + player_id).innerHTML = this.gamedatas.tile_list[tile_id].name; }, 1000);
            $('location_' + player_id).innerHTML = this.gamedatas.tile_list[tile_id].name;
                        
        },

        pawnTooltip : function(player_id) {

            var player = this.gamedatas.players[player_id];
            var adventurer_name = this.gamedatas.player_list[player.adventurer].name;

            return '<b>' + adventurer_name + '</b> ( ' + player.name + ' )';

        },


        updatePossibleMoves : function( possibleMoves )
        {
            this.clearLastAction();

            console.log( 'updatePossibleMoves' );
            
            if ((typeof possibleMoves !== 'undefined') && (possibleMoves.length > 0)) {
                possibleMoves.forEach(
                    function (tile_id, index) {
                        var node = $( tile_id );
                        // dojo.query('#'+tile_id).addClass( 'possibleMove' );
                        if (node && !dojo.hasClass(node, 'possibleMove' )) {
                            dojo.addClass(node, 'possibleMove' );
                            this.handles.push(dojo.connect(node,'onclick', this, 'onTile'));
                        }
                        var node2 = $( 'pawn_area_' + tile_id );
                        // dojo.query('#pawn_area_'+tile_id).addClass( 'possibleMove' );
                        if (node2 && !dojo.hasClass(node2, 'possibleMove' )) {
                            dojo.addClass(node2, 'possibleMove' );
                            this.handles.push(dojo.connect(node2,'onclick', this, 'onTile'));
                        }
                    }, this);

                // if( this.isCurrentPlayerActive() )
                if (( this.special_action ) || ( !this.special_action && this.isCurrentPlayerActive() ))
                { 
                    switch (this.selectedAction) {
                        case 'shore_up':
                        case 'sandbags':
                        case 'bonus_shoreup':
                            // this.addTooltipToClass( 'possibleMove', '', _('Shore up this tile.') );
                            break;
                        default:
                            // this.addTooltipToClass( 'possibleMove', '', _('Move to this tile.') );
                            break;
                    }
                } else {
                    dojo.query('.possibleMove').addClass( 'otherPlayer' );
                }
            }

        },

        updatePossiblePawns : function( players )
        {
            this.clearLastAction();

            console.log( 'updatePossiblePawns' );

            if (players.length > 0) {
                players.forEach(
                    function (pid, index) {
                        this.placePawnSelect(pid);
                        var node = $( 'pselect_' + pid.toString() );
                        // var node = $( pid.toString() );
                        dojo.addClass(node, 'possiblePawn' );
                        this.handles.push(dojo.connect(node,'onclick', this, 'onPawn'));
                    }, this);

            }

            dojo.query('.island_tile').addClass( 'fadeTile' );

        },

        getPlayersAtLocation: function (tile_id)
        {
            var players = [];

            this.playerLocations.forEach(
                function (player, index) {
                    if (player.location == tile_id) {
                        players.push(player.id);
                    }
            }, this);

            return players;

        },

        clearLastAction : function( )
        {
            console.log( 'clearLastAction, handles = ' + this.handles.length );

            // Remove current possible moves
            dojo.query( '.possibleMove' ).removeClass( 'possibleMove' );
            dojo.query( '.otherPlayer' ).removeClass( 'otherPlayer' );
            dojo.query( '.possibleCard' ).removeClass( 'possibleCard' );
            dojo.query( '.possiblePlayer' ).removeClass( 'possiblePlayer' );
            dojo.query( '.possiblePawn' ).removeClass( 'possiblePawn' );
            dojo.query( '.selected' ).removeClass( 'selected' );
            dojo.query( '.selectedPawn' ).removeClass( 'selectedPawn' );
            dojo.query( '.fadeTile' ).removeClass( 'fadeTile' );

            dojo.forEach(this.handles, dojo.disconnect);
            this.handles = [];

        },

        getTooptipHtml : function(card)
        {

        },

        ///////////////////////////////////////////////////
        //// Player's action
        
        /*
        
            Here, you are defining methods to handle player's action (ex: results of mouse click on 
            game objects).
            
            Most of the time, these methods:
            _ check the action is possible at this game state.
            _ make a call to the game server
        
        */

        onCapture: function()
        {
            if (! this.checkAction('capture'))
            return;

            if( this.isCurrentPlayerActive() )
            {       
                console.log( 'onCapture' );
                this.ajaxcall( "/forbiddenisland/forbiddenisland/captureTreasure.html", {
                    lock: true,
                }, this, function( result ) {} );
            }
        },  

        onPlaySpecial: function()
        {
            // if( this.isCurrentPlayerActive() )
            // {       
                console.log( 'onPlaySpecial' );
                
                this.selectedAction = 'special_action';
                this.updateSpecialCards(this.player_treasure_cards);
                this.special_action = true;

                this.setClientState("client_selectSpecialCard", { descriptionmyturn : "${you} must select a special card"});
            // }

        },

        onShoreUp: function()
        {
            if( this.isCurrentPlayerActive() )
            {       
                console.log( 'onShoreUp' );
                
                this.selectedAction = 'shore_up';
                this.updatePossibleMoves(this.possibleActions.shore_up);
                
                this.setClientState("client_selectShoreUp", { descriptionmyturn : "${you} must select a tile to shore up"});
                    
            }
        },  

        onGiveCard: function()
        {
            if( this.isCurrentPlayerActive() )
            {       
                console.log( 'onGiveCard' );
            
                this.selectedAction = 'give_card';
                this.updatePossibleCards(this.player_treasure_cards, give=true);

                this.setClientState("client_selectGiveCard", { descriptionmyturn : "${you} must select a card to give"});
            }
        },

        onPilot: function()
        {
            if( this.isCurrentPlayerActive() )
            {       
                console.log( 'onPilot' );
                
                this.selectedAction = 'pilot';
                this.playerLocations.forEach( function(x) {
                    if (x.id == this.player_id) {
                        this.startingTile = x.location;
                    }
                }, this);
                this.setClientState("client_selectPilotDest", { descriptionmyturn : "Pilot: ${you} must select a destination tile."});
            }
        },

        onNavigator: function()
        {
            if( this.isCurrentPlayerActive() )
            {       
                console.log( 'onNavigator' );
                
                this.selectedAction = 'navigator';
                this.setClientState("client_selectNavigatorPlayer", { descriptionmyturn : "Navigator: ${you} must select a player >>> "});
            }
        },

        onDone: function()
        {
            console.log( 'onDone' );

            if (this.selectedAction == 'heli_lift') {
                if (! this.checkAction('move'))
                return;
                // var card_id = this.selectedCard.split('_')[2];

            var players = [];
            var nodes = dojo.query('.selectedPawn');
            for(var x = 0; x < nodes.length; x++) {
                players.push(nodes[x].id.split('_')[1]);
            }
            this.selectedPlayers = players;
            if (players.length > 0) {
                this.setClientState("client_selectHeliLiftDest", 
                { descriptionmyturn : "${you} are playing Helicopter Lift. Select a destination tile."});
                }
            }
        }, 

        onConfirm: function()
        {
            console.log( 'onConfirm' );

            if ((this.selectedAction == 'heli_lift') && this.isWinCondition) {
                if (! this.checkAction('win'))
                return;
                this.ajaxcall( "/forbiddenisland/forbiddenisland/winGame.html", {
                    lock: true,
                }, this, function( result ) {} );
            } else if (this.selectedAction == 'skip') {
                if (! this.checkAction('skip'))
                return;
                this.ajaxcall( "/forbiddenisland/forbiddenisland/skipAction.html", {
                    lock: true
                }, this, function( result ) {} );
            } // TODO else??
            
        },  

        onCancel: function()
        {
            console.log( 'onCancel' );

            this.special_action = false;

            if (this.selectedAction == 'sandbags' || this.selectedAction == 'heli_lift') {
                if (! this.checkPossibleActions('cancel'))
                return;
                this.ajaxcall( "/forbiddenisland/forbiddenisland/cancelSpecial.html", {
                    lock: true,
                }, this, function( result ) {} );
            } else {
                this.restoreServerGameState();
            }
        },  

        onContinue: function()
        {
            if (! this.checkAction('continue'))
                return;

            if( this.isCurrentPlayerActive() )
            {       
                console.log( 'onContinue' );
                this.ajaxcall( "/forbiddenisland/forbiddenisland/continue.html", {
                    lock: true
                }, this, function( result ) {} );
            }
        },

        onSkip: function()
        {
            if (! this.checkAction('skip'))
                return;

            if( this.isCurrentPlayerActive() )
            {       
                console.log( 'onSkip' );
                if ((this.selectedAction == 'bonus_shoreup') || (this.remaining_actions == 0)) {
                    this.ajaxcall( "/forbiddenisland/forbiddenisland/skipAction.html", {
                        lock: true
                    }, this, function( result ) {} );
                } else {
                    this.selectedAction = 'skip';
                    this.setClientState("client_endTurn", { descriptionmyturn : "Confirm to your end turn "});
                }


            }
        }, 

        onUndo: function()
        {
            console.log( 'onUndo' );

            if( this.isCurrentPlayerActive() &&  this.checkAction( 'move' )) {

                this.ajaxcall( "/forbiddenisland/forbiddenisland/moveAction.html", {
                    lock: true,
                    undo: true,
                    tile_id: ''
                }, this, function( result ) {} );

            }
        },
        
        onPlayDiscard: function()
        {
            // Play special discard
            console.log( 'onPlayDiscard' );
            if (! this.checkAction( 'special_action' ))
                return;
            var card_id = this.selectedCard;
            var id = card_id.split('_')[5];
            this.ajaxcall( "/forbiddenisland/forbiddenisland/playSpecial.html", {
                lock: true,
                id:id,
                player_id:this.player_id
            }, this, function( result ) {} );
        
        },

        onDiscard: function()
        {
            // Discard Special card

            console.log( 'onDiscard' );

            if (! this.checkAction( 'discard' ))
                return;
            
            var card_id = this.selectedCard;
            var id = card_id.split('_')[5];
            this.ajaxcall( "/forbiddenisland/forbiddenisland/discardTreasure.html", {
                lock: true,
                id:id
            }, this, function( result ) {} );
        },

        onTile: function( evt )
        {
            var tile_id = evt.currentTarget.id;
            dojo.stopEvent( evt );
            if(dojo.hasClass(tile_id, 'pawn_area')) {
                tile_id = tile_id.slice(10);
            }
            if( this.isCurrentPlayerActive() ) {     
                console.log( 'onTile' );
                switch (this.selectedAction) {
                    case 'pilot':
                        if ( this.checkAction( 'move' ) && dojo.hasClass(tile_id, 'possibleMove')) {  
                            this.ajaxcall( "/forbiddenisland/forbiddenisland/moveAction.html", {
                                lock: true,
                                tile_id:tile_id,
                                pilot: true
                            }, this, function( result ) {} );
                        }
                        break;

                    case 'move':
                        if ( this.checkAction( 'move' ) && dojo.hasClass(tile_id, 'possibleMove')) {  
                            this.ajaxcall( "/forbiddenisland/forbiddenisland/moveAction.html", {
                                lock: true,
                                tile_id:tile_id
                            }, this, function( result ) {} );
                        }
                        break;

                    case 'shore_up':
                        if ( this.checkAction( 'shore_up' ) && dojo.hasClass(tile_id, 'possibleMove')) {  
                            this.ajaxcall( "/forbiddenisland/forbiddenisland/shoreUpAction.html", {
                                lock: true,
                                tile_id:tile_id
                            }, this, function( result ) {} );
                        }
                        break;

                    case 'heli_lift':
                        if ( this.checkAction( 'move' ) && dojo.hasClass(tile_id, 'possibleMove')) {  
                            if (tile_id == 'fools_landing' && this.isWinCondition ) {
                                this.setClientState("client_confirmWinGame", 
                                    { descriptionmyturn : "${you} have your team and all four treasures.  Are you ready to lift off the island for the win!?!"});
                            } else if (this.startingTile == null) {
                                this.startingTile = tile_id;
                                this.setClientState("client_selectHeliLiftPlayers", 
                                    { descriptionmyturn : "${you} are playing Helicopter Lift. Select players to move >>> "});
                            } else {
                                var card_id = this.selectedCard.split('_')[5];
                                this.ajaxcall( "/forbiddenisland/forbiddenisland/moveAction.html", {
                                    lock: true,
                                    tile_id:tile_id,
                                    heli_lift: true,
                                    card_id: card_id,
                                    players: this.selectedPlayers.join(';')
                                }, this, function( result ) {} );
                            }
                        }
                        break;

                    case 'sandbags':
                        if( this.checkAction( 'shore_up' ) && dojo.hasClass(tile_id, 'possibleMove')) {  
                            var card_id = this.selectedCard.split('_')[5];
                            this.ajaxcall( "/forbiddenisland/forbiddenisland/shoreUpAction.html", {
                                lock: true,
                                tile_id:tile_id,
                                sandbags: true,
                                card_id: card_id
                            }, this, function( result ) {} );
                        }
                        break;

                    case 'bonus_shoreup':
                        if ( this.checkAction( 'shore_up' ) && dojo.hasClass(tile_id, 'possibleMove')) {  
                            this.ajaxcall( "/forbiddenisland/forbiddenisland/shoreUpAction.html", {
                                lock: true,
                                tile_id:tile_id,
                                bonus: true
                            }, this, function( result ) {} );
                        }
                        break;

                    case 'navigator':
                        if ( this.checkAction( 'move' )) {  
                            this.ajaxcall( "/forbiddenisland/forbiddenisland/moveAction.html", { 
                                lock: true,
                                tile_id:tile_id,
                                navigator: true,
                                players: this.selectedPlayers.join(';')
                            }, this, function( result ) {} );                        
                        }
                        break;

                    case 'rescue':
                        if ( this.checkAction( 'move' )) {  
                            this.ajaxcall( "/forbiddenisland/forbiddenisland/moveAction.html", { 
                                lock: true,
                                tile_id:tile_id,
                                rescue: true,
                                pilot: (this.adventurer == 'pilot') ? true : false,
                                players: [ this.player_id ].join(';')
                            }, this, function( result ) {} );                        
                        }
                        break;
                    
                }
            }
        },

        onCard: function( evt )
        {
            var card_id = evt.currentTarget.id;
            dojo.stopEvent( evt );
            console.log( 'onCard ' + card_id );

            this.selectedCard = card_id;

            if ( this.isCurrentPlayerActive() )
            {     

                switch (this.selectedAction) {
                    case 'discard':
                        if ( this.checkAction( 'discard' ) && dojo.hasClass(dojo.byId(card_id), 'possibleCard')) {  
                            var node = $(card_id);
                            dojo.query( '.selected' ).removeClass( 'selected' );
                            dojo.addClass(node, 'selected');
                            this.selectedCard = card_id;
                            var id = card_id.split('_')[5];
                            for (var i = 0; i < this.player_treasure_cards.length; i++) {
                                if (this.player_treasure_cards[i].id == id) {
                                    var card_type = this.player_treasure_cards[i].type;
                                    break;
                                }
                            }
                            if (card_type == 'heli_lift' || card_type == 'sandbags') {
                                this.setClientState("client_selectChooseDiscardSpecial", 
                                { descriptionmyturn : "Would ${you} like to play the special action card?"});
                            } else {
                                this.setClientState("client_confirmDiscard", 
                                { descriptionmyturn : "Confirm to discard treasure card '" + this.gamedatas.treasure_list[card_type].name + "'"});
                            }
                        }
                        break;
                    case 'give_card':
                        if ( this.checkAction( 'give_card' ) && dojo.hasClass(dojo.byId(card_id), 'possibleCard')) {  
                            var node = $(card_id);
                            dojo.addClass(node, 'selected');
    
                            if (this.adventurer == 'messenger') {
                                this.updateAllPlayers();
                            } else {
                                this.updateColocatedPlayers(this.colocated_players);
                            }
    
                            this.setClientState("client_selectGiveCardPlayers", 
                                { descriptionmyturn : "${you} select players to give the card to."});
    
                        }
                        break;

                    default:
                        break;
                }
            }

            if (this.selectedAction == 'special_action') {
                // if( this.checkPossibleActions( 'special_action' ) && dojo.hasClass(dojo.byId(card_id), 'possibleCard'))
                if(dojo.hasClass(dojo.byId(card_id), 'possibleCard'))
                {  
                    var node = $(card_id);
                    dojo.addClass(node, 'selected');
                    this.selectedCard = card_id;
                    var id = card_id.split('_')[5];
                    this.ajaxcall( "/forbiddenisland/forbiddenisland/playSpecial.html", {
                        lock: true,
                        id:id,
                        player_id: this.player_id
                    }, this, function( result ) {} );

                }
            }
        },

        onPlayer: function( evt )
        {
            var target_player_area = evt.currentTarget.id;
            dojo.stopEvent( evt );

            if ( this.isCurrentPlayerActive() )
            {     
                console.log( 'onPlayer' );

                if (this.selectedAction == 'give_card') {
                    if( this.checkAction( 'give_card' ) && dojo.hasClass(dojo.byId(target_player_area), 'possiblePlayer'))
                    {  
                        var card_id = this.selectedCard;
                        if (dojo.hasClass(dojo.byId(target_player_area), 'player_card')){
                            var target_player_id = target_player_area.split('_')[2];
                        } else {
                            var target_player_id = target_player_area.split('_')[3];
                        }
                        var id = card_id.split('_')[5];
                        this.ajaxcall( "/forbiddenisland/forbiddenisland/giveTreasure.html", { 
                            lock: true,
                            id:id,
                            target_player_id:target_player_id
                        }, this, function( result ) {} );                        
                    }
                }
            }
        },

        onPawn: function( evt )
        {
            var target_pawn = evt.currentTarget.id;
            dojo.stopEvent( evt );

            if ( this.isCurrentPlayerActive() )
            {     
                console.log( 'onPawn' );

                if (this.selectedAction == 'navigator') {
                    this.selectedPlayers[0] = target_pawn.split('_')[1];
                    this.setClientState("client_selectNavigatorDest", 
                        { descriptionmyturn : "Navigator: ${you} must select a destination."});
                } else if (this.selectedAction == 'heli_lift') {
                    dojo.toggleClass(dojo.byId(target_pawn), 'selectedPawn');
                }
            }
        },

        
        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications

        /*
            setupNotifications:
            
            In this method, you associate each of your game notifications with your local method to handle it.
            
            Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                  your forbiddenisland.game.php file.
        
        */
        setupNotifications: function()
        {
            console.log( 'notifications subscriptions setup' );

            dojo.subscribe( 'moveAction', this, "notif_moveAction" );
            this.notifqueue.setSynchronous( 'moveAction', 1000 );
            dojo.subscribe( 'shoreUpAction', this, "notif_shoreUpAction" );
            this.notifqueue.setSynchronous( 'shoreUpAction', 1000 );
            dojo.subscribe( 'floodTile', this, "notif_floodTile" );
            this.notifqueue.setSynchronous( 'floodTile', 1000 );
            dojo.subscribe( 'sinkTile', this, "notif_sinkTile" );
            this.notifqueue.setSynchronous( 'sinkTile', 1000 );
            dojo.subscribe( 'watersRise', this, "notif_watersRise" );
            this.notifqueue.setSynchronous( 'watersRise', 1000 );
            dojo.subscribe( 'drawTreasure', this, "notif_drawTreasure" );
            this.notifqueue.setSynchronous( 'drawTreasure', 2000 );
            dojo.subscribe( 'discardTreasure', this, "notif_discardTreasure" );
            this.notifqueue.setSynchronous( 'discardTreasure', 1000 );
            dojo.subscribe( 'giveTreasure', this, "notif_giveTreasure" );
            this.notifqueue.setSynchronous( 'giveTreasure', 1000 );
            dojo.subscribe( 'captureTreasure', this, "notif_captureTreasure" );
            this.notifqueue.setSynchronous( 'captureTreasure', 1000 );
            dojo.subscribe( 'captureAllTreasure', this, "notif_captureAllTreasure" );
            this.notifqueue.setSynchronous( 'captureTreasure', 1000 );
            dojo.subscribe( 'reshuffleTreasureDeck', this, "notif_reshuffleTreasureDeck" );
            this.notifqueue.setSynchronous( 'reshuffleTreasureDeck', 1000 );
            dojo.subscribe( 'reshuffleFloodDeck', this, "notif_reshuffleFloodDeck" );
            this.notifqueue.setSynchronous( 'reshuffleFloodDeck', 1000 );
            dojo.subscribe( 'updateCardCount', this, "notif_updateCardCount" );
            dojo.subscribe('log', this, "notif_log");
            dojo.subscribe('animate', this, "notif_animate");
            this.notifqueue.setSynchronous('animate', 1000);

        },  
        
       notif_moveAction: function( notif )
       {
            console.log( 'notif_moveAction' );
            console.log( notif );
            
            this.clearLastAction();
            if (notif.args.heli_lift) {
                this.discardTreasure(notif.args.card_id, notif.args.player_id, type = 'heli_lift', place = false);
                notif.args.players.split(',').forEach( function(x) {
                    this.movePawn( notif.args.tile_id, x );
                }, this);
            } else if (notif.args.navigator) {
                this.movePawn( notif.args.tile_id, notif.args.target_player_id );
            } else {
                this.movePawn( notif.args.tile_id, notif.args.player_id );
            }
            this.special_action = false;

       },

       notif_shoreUpAction: function( notif )
       {
            console.log( 'notif_shoreUpAction' );
            console.log( notif );
            
            this.clearLastAction();
            this.unfloodTile(notif.args.tile_id);

            if (notif.args.sandbags) {
                this.discardTreasure(notif.args.card_id, notif.args.player_id, type = 'sandbags', place = false);
            }
            this.special_action = false;

       },

       notif_skipAction: function( notif )
       {
            console.log( 'notif_skipAction' );
            console.log( notif );
            
            this.clearLastAction();

       },

       notif_floodTile: function( notif )
       {
            console.log( 'notif_floodTile' );
            console.log( notif );

            var tile_id = notif.args.tile_id;
            var flood_card = notif.args.flood_card_type;
            this.flood_discards.push(tile_id);

            this.floodTile(tile_id);
            this.placeFloodCard(flood_card);
            
       },

       notif_sinkTile: function( notif )
       {
            console.log( 'notif_sinkTile' );
            console.log( notif );

            var tile_id = notif.args.tile_id;
            var flood_card = notif.args.flood_card_type;

            this.placeFloodCard(flood_card);
            setTimeout(() => { this.sinkTile(tile_id) }, 500);
            setTimeout(() => { this.removeFlood(flood_card) }, 1000);
            
       },

       notif_watersRise: function( notif )
       {
            console.log( 'notif_watersRise' );
            console.log( notif );

            this.discardTreasure(notif.args.card.id, notif.args.player_id, type = 'waters_rise', place = false);

            this.removeAllFlood();

            this.moveWaterLevel(notif.args.water_level);

            dojo.query('.tile_mark').style('display', 'none');

       },

       notif_drawTreasure: function( notif )
       {
            console.log( 'notif_drawTreasure' );
            console.log( notif );

            this.placeTreasureCard(notif.args.card_1.id, notif.args.card_1.type, notif.args.player_id);
            this.placeTreasureCard(notif.args.card_2.id, notif.args.card_2.type, notif.args.player_id);

       },

       notif_discardTreasure: function( notif )
       {
            console.log( 'notif_discardTreasure' );
            console.log( notif );

            var player_id = notif.args.player_id;
            var card_id = notif.args.card.id;
            this.clearLastAction();
            this.discardTreasure(card_id, notif.args.player_id, type = notif.args.card.type, place = false);

            var cards = {};
            for( var c in this.all_player_treasure_cards[player_id]) {
                if (c != card_id) {
                    cards[c] = this.all_player_treasure_cards[player_id][c];
                }
            }

            this.all_player_treasure_cards[player_id] = cards;

       },

       notif_giveTreasure: function( notif )
       {
            console.log( 'notif_giveTreasure' );
            console.log( notif );

            this.clearLastAction();
            this.moveTreasure(notif.args.card.id, notif.args.player_id, notif.args.target_player_id);

       },

       notif_captureTreasure: function( notif )
       {
            console.log( 'notif_captureTreasure' );
            console.log( notif );

            var treasure = notif.args.treasure;
            var player_id = notif.args.player_id;

            this.clearLastAction();
            notif.args.cards.forEach(
                function (c, index) {
                    this.discardTreasure(c.id, player_id, type = treasure, place = false);
            }, this);

            this.moveFigure(treasure, player_id);
            x = this.gamedatas.treasure_list[treasure].fig * 25;
            dojo.place(this.format_block('jstpl_figureicon', {
                treasure: treasure,
                x: x
            }), 'p_board_icon_' + player_id, 'last');

            this.slideToObject('figureicon_' + treasure, 'p_board_icon_' + player_id).play();

       },

       notif_captureAllTreasure: function( notif )
       {
           this.showMessage( _("Your team has all four treasures!!  Return to Fools' Landing and play Helicopter Lift to escape the island and win the game!!"), 'info');
       },

       notif_reshuffleTreasureDeck: function( notif )
       {
            this.removeAllTreasure();
       },

       notif_reshuffleFloodDeck: function( notif )
       {
            this.removeAllFlood();
            // this.flood_discards = [];
       },

       notif_updateCardCount: function( notif )
       {
            for (var player_id in notif.args.ncards) {
                $('cardcount_' + player_id).innerHTML = notif.args.ncards[player_id];
            };

            $('cardcount_flood_deck').innerHTML = notif.args.flood_deck_count;
            $('cardcount_treasure_deck').innerHTML = notif.args.treasure_deck_count;
       },

       notif_animate : function(notif) {
            // do nothing, just there to play animation from previous notifications
       },

       notif_log : function(notif) {
            // this is for debugging php side
            console.log(notif.log);
            console.log(notif.args);
        },

   });             
});
