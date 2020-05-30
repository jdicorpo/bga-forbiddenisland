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

            this.tilewidth = 128;
            this.tileheight = 128;

            this.cardwidth = 128;
            this.cardheight = 177.75;

            this.figurewidth = 88.5;
            this.figureheight = 120;

            this.pawnwidth = 32;
            this.pawnheight = 57;
            this.pawn_area = [];

            this.flood_card_area = new ebg.zone();
            this.flood_deck = new ebg.stock();

            this.treasure_card_area = new ebg.zone();
            this.treasure_deck = new ebg.stock();

            this.figure_area = [];
            this.figure_area['earth'] = new ebg.zone();
            this.figure_area['air'] = new ebg.zone();
            this.figure_area['fire'] = new ebg.zone();
            this.figure_area['ocean'] = new ebg.zone();

            this.player_adventurer = [];
            this.player_card_area = [];
            this.player_deck = new ebg.stock();

            this.board = new ebg.stock();

            this.selectedAction = 'move';
            this.selectedCard = null;
            this.selectedPlayers = [];
            this.startingTile = null;
            this.possibleActions = [];
            this.player_treasure_cards = [];

            this.previous_pagemaintitletext = "";

            this.colocated_players = [];
            this.playerLocations = [];

            this.clientStateArgs = {};

            this.isWinCondition = false;
              
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

            // TODO: added to speed up reload  (remove for production release)
            // dojo.destroy('debug_output');
            
            // // TODO: Set up your game interface here, according to "gamedatas"
            
            this.board.create( this, $('island_tile'), this.tilewidth, this.tileheight );

            this.board.image_items_per_row = 8;

            // Setup board - place tiles on map
            for (var tile in gamedatas.tile_list) {
                var img_id = gamedatas.tile_list[tile].img_id;
                this.board.addItemType(tile, tile, g_gamethemeurl + 'img/tiles.jpg', img_id);
                this.pawn_area[tile] = new ebg.zone();
                this.pawn_area[tile].itemIdToCoords = function( i, control_width ) { return {  x:32*(i%4), y:1, w:32, h:57 } }; 
                this.pawn_area[tile].setPattern( 'custom' );
            }

            for( var i in gamedatas.unflooded )
            {
                var tile = gamedatas.unflooded[i];
                var x = Math.trunc( tile.location_arg / 10) ;
                var y = tile.location_arg % 10;
                
                this.placeTile( x, y, tile.type);
            }

            for( var i in gamedatas.flooded )
            {
                var tile = gamedatas.flooded[i];
                var x = Math.trunc( tile.location_arg / 10) ;
                var y = tile.location_arg % 10;

                this.placeTile( x, y, tile.type, flooded = true);
            }

            for( var i in gamedatas.sunk )
            {
                var tile = gamedatas.sunk[i];
                var x = Math.trunc( tile.location_arg / 10) ;
                var y = tile.location_arg % 10;

                this.placeTile( x, y, tile.type, flooded = true, sunk = true);
            }

            // Setting up player boards
            for( var player_id in gamedatas.players )
            {
                var player = gamedatas.players[player_id];
                this.placePawn( player_id, gamedatas.player_list[player.adventurer].pawn_idx, player.location);
                this.player_adventurer[player_id] = new ebg.zone();
                this.player_card_area[player_id] = new ebg.zone();
                this.player_adventurer[player_id].create( this, 'player_adventurer_' + player_id, this.cardwidth, this.cardheight);
                this.player_card_area[player_id].create( this, 'player_card_area_' + player_id, this.cardwidth - 70, this.cardheight);
                this.placePlayer(player_id, gamedatas.player_list[player.adventurer].idx);
                this.figure_area[player_id] = new ebg.zone();
                this.figure_area[player_id].create( this, 'player_figure_area_' + player_id, this.figurewidth, this.figureheight);
                for( var card_id in gamedatas.player_card_area[player_id].treasure_cards )
                {
                    var card = gamedatas.player_card_area[player_id].treasure_cards[card_id];
                    this.placeTreasure(card_id, card.type, player_id);
                }
                // TODO: Setting up players boards if needed
                var playerBoardDiv = dojo.byId('player_board_' + player_id);
                var block = dojo.place(this.format_block('jstpl_player_board', {
                    id: player_id,
                    adventurer: gamedatas.player_list[player.adventurer].name,
                    color: player.color
                }), playerBoardDiv);
                for (treasure of ['earth', 'fire', 'air', 'ocean']) {
                    if (gamedatas[treasure] == player_id) {
                        x = this.gamedatas.treasure_list[treasure].fig * 25;
                        dojo.place(this.format_block('jstpl_figureicon', {
                            x: 0
                        }), 'p_board_icon_' + player_id, 'last');
                    }
                }
            }

            // setup the flood deck area
            this.flood_card_area.create( this, 'flood_card_area', this.cardwidth - 90, this.cardheight);
            for( var card_id in gamedatas.flood_card_area )
            {
                var card = gamedatas.flood_card_area[card_id]
                this.placeFlood(card.type);
            }

            // setup the flood deck area
            this.treasure_card_area.create( this, 'treasure_card_area', this.cardwidth - 90, this.cardheight);
            for( var card_id in gamedatas.treasure_discards )
            {
                var card = gamedatas.treasure_discards[card_id]
                this.discardTreasure(card_id, card.type, place = true);
            }

            for (treasure of ['earth', 'fire', 'air', 'ocean']) {
                this.figure_area[treasure].create( this, 'starting_area_' + treasure, this.figurewidth, this.figureheight );
                this.placeFigure(treasure, this.gamedatas[treasure]);
            }
            
             this.placeWaterLevel(this.gamedatas.water_level);

            dojo.query( '.island_tile').connect( 'onclick', this, 'onTile');
            dojo.query( '.pawn_area').connect( 'onclick', this, 'onTile');
            dojo.query( '.island_tile_sunk').connect( 'onclick', this, 'onTile');

            // Setup game notifications to handle (see "setupNotifications" method below)
            this.setupNotifications();

            console.log( "Ending game setup" );
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
            
            case 'playerActions':
                this.selectedAction = 'move';
                this.possibleActions = args.args.possibleActions;
                var obj = args.args.player_treasure_cards;
                this.player_treasure_cards = Object.keys(obj).map(function(key) {
                    return obj[key];
                });
                this.updatePossibleMoves( this.possibleActions.move );
                var obj = args.args.colocated_players;
                this.colocated_players = Object.keys(obj).map(function(key) {
                    return obj[key];
                });
                this.isWinCondition = args.args.isWinCondition;
                break;

            case 'discardTreasure':
                var obj = args.args.player_treasure_cards;
                this.player_treasure_cards = Object.keys(obj).map(function(key) {
                    return obj[key];
                });
                this.updatePossibleCards( this.player_treasure_cards );
                this.selectedAction = 'discard';
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
                break;

            case 'heli_lift':
                this.selectedAction = 'heli_lift';
                this.startingTile = null;
                this.playerLocations = args.args.playerLocations;
                this.possibleActions = args.args.possibleActions;
                this.updatePossibleMoves( this.playerLocations.map( function(player) {
                    return player.location;
                }));
                break;

            case 'client_selectHeliLiftPlayers':
                this.clearLastAction();
                var target_players = this.getPlayersAtLocation(this.startingTile);
                if (target_players.length > 1) {
                    // update possible pawns to be selected
                    this.updatePossiblePawns(target_players);
                } else {
                    this.setClientState("client_selectHeliLiftDest", 
                    { descriptionmyturn : "${you} are playing special action - Helicopter Lift. Select a destination tile."});
                }
                break;

            case 'client_selectHeliLiftDest':
                this.updatePossibleMoves( this.possibleActions.heli_lift );
                break;

            case 'client_confirmWinGame':
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
            
            case 'playerActions':
                // this.disconnectAll();
                break;

            case 'discardTreasure':
                // this.disconnectAll();
                break;
          
           
            case 'dummmy':
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
                        main.innerHTML += '<span id="remaining_actions_value" style="font-weight:bold;color:#ED0023;">' 
                            + args.remaining_actions + '</span>' + _(' actions: ') + '<span style="font-weight:bold;color:#4871b6;">' 
                            + _('Move') + '</span>' + _(' or ');
                        this.addActionButton( 'shore_up_btn', _('Shore Up'), 'onShoreUp' ); 
                        this.addActionButton( 'give_treasure_btn', _('Give Card'), 'onGiveCard' ); 
                        this.addActionButton( 'capture_treasure_btn', _('Capture Treasure'), 'onCapture' ); 
                        this.addActionButton( 'skip_btn', _('Skip'), 'onSkip' ); 
                        this.addActionButton( 'player_special_btn', _('Play Special'), 'onPlaySpecial', null, false, 'red' ); 
                        break;

                    case 'discardTreasure':
                        break;

                    case 'client_selectShoreUp':
                    case 'client_selectGiveCard':
                    case 'client_selectSpecialCard':
                        this.addActionButton( 'cancel_btn', _('Cancel'), 'onCancel', null, false, 'red' );
                        break;

                    case 'sandbags':
                    case 'heli_lift':
                        this.addActionButton( 'cancel_btn', _('Cancel'), 'onCancel', null, false, 'red' );
                        break;

                    case 'client_selectHeliLiftPlayers':
                        
                        this.addActionButton( 'done_btn', _('Done'), 'onDone', null, false, 'blue' );
                        this.addActionButton( 'cancel_btn', _('Cancel'), 'onCancel', null, false, 'red' );
                        break;

                    case 'client_selectHeliLiftDest':
                        this.addActionButton( 'cancel_btn', _('Cancel'), 'onCancel', null, false, 'red' );
                        break;

                    case 'nextPlayer':
                        break;

                    case 'client_confirmWinGame':
                        this.addActionButton( 'confirm_btn', _('Confirm'), 'onConfirm', null, true, 'red' );
                        this.addActionButton( 'cancel_btn', _('Cancel'), 'onCancel', null, false, 'gray' );
                        break;

                    default:
                        break;
                }
            }
        },        

        ///////////////////////////////////////////////////
        //// Utility methods
        
        /*
        
            Here, you can defines some utility methods that you can use everywhere in your javascript
            script.
        
        */

        updatePossibleCards: function(cards) {

            this.clearLastAction();
            if (typeof cards !== 'undefined') {
                cards.forEach(
                    function (c, index) {
                        var node = $('treasure_card_' + c.id);
                        dojo.addClass(node, 'possibleCard');
                    });
                }
                this.connectClass('possibleCard', 'onclick', 'onCard');
        },

        updateSpecialCards: function(cards) {

            this.clearLastAction();
            if (typeof cards !== 'undefined') {
                cards.forEach(
                    function (c, index) {
                        var node = $('treasure_card_' + c.id);
                        if (c.type == 'sandbags' || c.type == 'heli_lift') {
                            dojo.addClass(node, 'possibleCard');
                        }
                    }, this);
                }
                this.connectClass('possibleCard', 'onclick', 'onCard');
        },

        updateColocatedPlayers: function(players) {

            this.clearLastAction();

            if (typeof players !== 'undefined') {
                players.forEach(
                    function (p, index) {
                        if (p.player_id != this.player_id) {
                            var node = $('player_card_area_' + p.player_id);
                            dojo.addClass(node, 'possiblePlayer');
                        }
                    }, this);
                }
                this.connectClass('possiblePlayer', 'onclick', 'onPlayer');

        },

        placeWaterLevel: function(level) {

            dojo.place(this.format_block('jstpl_slider', {
                level : level
            }), 'water_level_' + level, 'only');

        },

        moveWaterLevel: function(level) {

            this.slideToObject( 'water_slider', 'water_level_'+level).play();

        },

        placeTile : function(a, b, tile_id, flooded = false, sunk = false) {
            console.log( 'placeTile' );

            var board_id = a + '_' + b;
            if (flooded) {
                var img_id = this.gamedatas.tile_list[tile_id].img_id + 24;
            } else {
                var img_id = this.gamedatas.tile_list[tile_id].img_id;
            }
            if (!sunk) {
                dojo.place(this.format_block('jstpl_tile', {
                    x : this.tilewidth * ((img_id-1) % 8),
                    y : this.tileheight * Math.trunc((img_id-1) / 8),
                    id : tile_id,
                }), 'island_tile_' + board_id, 'first');

                dojo.place(this.format_block('jstpl_pawn_area', {
                    id : tile_id,
                }), 'island_tile_' + board_id);

                this.pawn_area[tile_id].create( this, 'pawn_area_' + tile_id, this.pawnwidth, this.pawnheight);
            } else {
                dojo.place(this.format_block('jstpl_sunk_tile', {
                    id : tile_id,
                }), 'island_tile_' + board_id, 'first');

                dojo.place(this.format_block('jstpl_pawn_area', {
                    id : tile_id,
                }), 'island_tile_' + board_id);
            }

        },

        floodTile : function(tile_id) {
            console.log( 'floodTile' );

            var img_id = this.gamedatas.tile_list[tile_id].img_id + 24;
            
            dojo.place(this.format_block('jstpl_tile', {
                x : this.tilewidth * ((img_id-1) % 8),
                y : this.tileheight * Math.trunc((img_id-1) / 8),
                id : tile_id,
            }), tile_id, 'replace');

        },

        unfloodTile : function(tile_id) {
            console.log( 'unfloodTile' );

            var img_id = this.gamedatas.tile_list[tile_id].img_id;
            
            dojo.place(this.format_block('jstpl_tile', {
                x : this.tilewidth * ((img_id-1) % 8),
                y : this.tileheight * Math.trunc((img_id-1) / 8),
                id : tile_id,
            }), tile_id, 'replace');

        },

        sinkTile : function(tile_id) {
            console.log( 'sinkTile' );

            var parent_id = $(tile_id).parentNode.id;

            this.fadeOutAndDestroy( tile_id, 2000, 1000 );

            dojo.place(this.format_block('jstpl_sunk_tile', {
                id : tile_id,
            }), parent_id, 'first');

        },

        placePlayer : function(player_id, idx) {
            console.log( 'placePlayer' );
            var x = this.cardwidth * (idx-1);
            
            dojo.place(this.format_block('jstpl_player', {
                id : player_id,
                x : x,
            }), 'player_adventurer_' + player_id, 'only');

            this.player_adventurer[player_id].placeInZone('player_card_' + player_id);

        },

        placeFlood : function(id) {
            console.log( 'placeFlood' );

            var idx = this.gamedatas.flood_list[id].img_id
            var tooltip = this.gamedatas.flood_list[id].name;

            var x = this.cardwidth * ((idx-1) % 5);
            var y = this.cardheight * Math.trunc((idx-1) / 5 );
            
            dojo.place(this.format_block('jstpl_flood', {
                id : id,
                x : x,
                y: y,
            }), 'flood_card_area');

            this.flood_card_area.placeInZone('flood_card_' + id);

            this.addTooltip( 'flood_card_' + id, tooltip, '' );

        },

        removeFlood : function(id) {

            console.log( 'removeFlood' );

            this.flood_card_area.removeFromZone( 'flood_card_' + id, true, 'flood_deck' );

        },
 
        placeTreasure : function(id, type, player_id) {
            console.log( 'placeTreasure' );

            var idx = this.gamedatas.treasure_list[type].idx;
            var location = 'player_card_area_' + player_id;
            var zone = this.player_card_area[player_id];
            var x = this.cardwidth * (idx-1);
            
            dojo.place(this.format_block('jstpl_treasure', {
                id : id,
                x : x,
            }), location);

            zone.placeInZone('treasure_card_' + id);

        },

        discardTreasure : function(id, type = null, place = false) {
            console.log( 'discardTreasure' );

            if (place) {
                var idx = this.gamedatas.treasure_list[type].idx;
                var x = this.cardwidth * (idx-1);

                dojo.place(this.format_block('jstpl_treasure', {
                    id : id,
                    x : x,
                }), 'treasure_card_area');
            }

            this.treasure_card_area.placeInZone('treasure_card_' + id);

        },

        moveTreasure : function(id, player_id) {
            console.log( 'moveTreasure' );

            var zone = this.player_card_area[player_id];
            zone.placeInZone('treasure_card_' + id);

        },

        placeFigure : function(treasure, player_id) {

            console.log( 'placeFigure' );
            x = this.gamedatas.treasure_list[treasure].fig * 88.5;

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
        },

        moveFigure : function(treasure, player_id) {

            console.log( 'moveFigure' );

            this.figure_area[player_id].placeInZone('figure_' + treasure);

        },

        placePawn : function(player_id, idx, tile_id) {

            console.log( 'placePawn' );

            var parent_id = $(tile_id).parentNode.id;
            var pawn_area = dojo.query('#' + parent_id + ' .pawn_area')[0];
            var x = 31.5 * (idx-1);

            dojo.place(this.format_block('jstpl_pawn', {
                id : player_id,
                x : x,
            }), pawn_area, 'last');
            this.pawn_area[tile_id].placeInZone(player_id);

        },

        movePawn : function(tile_id, player_id) {

            console.log( 'movePawn' );

            this.pawn_area[tile_id].placeInZone(player_id);

        },


        updatePossibleMoves : function( possibleMoves )
        {
            // this.clearPossibleMoves();
            this.clearLastAction();

            console.log( 'updatePossibleMoves' );

            if (possibleMoves.length > 0) {
                possibleMoves.forEach(
                    function (tile_id, index) {
                        // if (!(dojo.query('#'+tile_id).hasClass( 'possibleMove' ))) {
                            dojo.query('#'+tile_id).addClass( 'possibleMove' );
                            dojo.query('#pawn_area_'+tile_id).addClass( 'possibleMove' );
                        // }
                    }, this);

                if( this.isCurrentPlayerActive() )
                { 
                    this.addTooltipToClass( 'possibleMove', '', _('Move to this tile.') );
                } else {
                    dojo.query('.possibleMove').addClass( 'otherPlayer' );
                }
            }

        },

        updatePossiblePawns : function( players )
        {
            // this.clearPossibleMoves();
            this.clearLastAction();

            console.log( 'updatePossiblePawns' );

            if (players.length > 0) {
                players.forEach(
                    function (player_id, index) {
                            dojo.query('#'+player_id).addClass( 'possiblePawn' );
                    }, this);

                // if( this.isCurrentPlayerActive() )
                // { 
                //     this.addTooltipToClass( 'possibleMove', '', _('Move to this tile.') );
                // } else {
                //     dojo.query('.possibleMove').addClass( 'otherPlayer' );
                // }
            }

            this.connectClass('possiblePawn', 'onclick', 'onPawn');

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

        // clearPossibleMoves : function( )
        // {
        //     // Remove current possible moves
        //     dojo.query( '.possibleMove' ).removeClass( 'possibleMove' );
        //     dojo.query( '.otherPlayer' ).removeClass( 'otherPlayer' );
        // },

        // clearPossibleCards : function( )
        // {
        //     // Remove current possible moves
        //     dojo.query( '.possibleCard' ).removeClass( 'possibleCard' );
        //     dojo.query( '.selected' ).removeClass( 'selected' );
        // },

        // clearPossiblePlayers : function( )
        // {
        //     // Remove current possible moves
        //     dojo.query( '.possiblePlayer' ).removeClass( 'possiblePlayer' );
        //     dojo.query( '.selected' ).removeClass( 'selected' );
        // },

        clearLastAction : function( )
        {
            // Remove current possible moves
            dojo.query( '.possibleMove' ).removeClass( 'possibleMove' );
            dojo.query( '.otherPlayer' ).removeClass( 'otherPlayer' );
            dojo.query( '.possibleCard' ).removeClass( 'possibleCard' );
            dojo.query( '.possiblePlayer' ).removeClass( 'possiblePlayer' );
            dojo.query( '.possiblePawn' ).removeClass( 'possiblePawn' );
            dojo.query( '.selected' ).removeClass( 'selected' );
            dojo.query( '.selectedPawn' ).removeClass( 'selectedPawn' );
        },

        getTooptipHtml : function(card)
        {

        },

        updateZone : function ( zone )
        {
            var items = zone.getAllItems()
            items.forEach(
                function (x, index) {
                    zone.removeFromZone(x, false);
            }, this);
            items.forEach(
                function (x, index) {
                    zone.placeInZone(x);
            }, this);
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
                }, this, function( result ) {} );
            }
        },  

        onPlaySpecial: function()
        {
            if( this.isCurrentPlayerActive() )
            {       
                console.log( 'onPlaySpecial' );
                
                this.updateSpecialCards(this.player_treasure_cards);
                this.selectedAction = 'special_action';

                this.setClientState("client_selectSpecialCard", { descriptionmyturn : "${you} must select a special card"});
            }

        },

        onShoreUp: function()
        {
            if( this.isCurrentPlayerActive() )
            {       
                console.log( 'onShoreUp' );
                
                this.updatePossibleMoves(this.possibleActions.shore_up);
                this.selectedAction = 'shore_up';
                
                this.setClientState("client_selectShoreUp", { descriptionmyturn : "${you} must select a tile to shore up"});
                    
            }
        },  

        onGiveCard: function()
        {
            if( this.isCurrentPlayerActive() )
            {       
                console.log( 'onGiveCard' );
                
                this.updatePossibleCards(this.player_treasure_cards);
                this.selectedAction = 'give_card';

                this.setClientState("client_selectGiveCard", { descriptionmyturn : "${you} must select a card to give"});
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
                players.push(nodes[x].id);
            }
            this.selectedPlayers = players;
            this.setClientState("client_selectHeliLiftDest", 
            { descriptionmyturn : "${you} are playing special action - Helicopter Lift. Select a destination tile."});
            }
        }, 

        onConfirm: function()
        {
            console.log( 'onConfirm' );

            if ((this.selectedAction == 'heli_lift') && this.isWinCondition) {
                this.ajaxcall( "/forbiddenisland/forbiddenisland/winGame.html", {
                }, this, function( result ) {} );
            }  // TODO else??
        },  

        onCancel: function()
        {
            console.log( 'onCancel' );

            if (this.selectedAction == 'sandbags' || this.selectedAction == 'heli_lift') {
                if (! this.checkAction('cancel'))
                return;
                this.ajaxcall( "/forbiddenisland/forbiddenisland/cancelSpecial.html", {
                }, this, function( result ) {} );
            } else {
                this.restoreServerGameState();
            }
        },  

        onSkip: function()
        {
            if (! this.checkAction('skip'))
            return;

            if( this.isCurrentPlayerActive() )
            {       
                console.log( 'onSkip' );
                this.ajaxcall( "/forbiddenisland/forbiddenisland/skipAction.html", {
                }, this, function( result ) {} );
            }
        },  

        onTile: function( evt )
        {
            var tile_id = evt.currentTarget.id;
            dojo.stopEvent( evt );
            if(dojo.hasClass(tile_id, 'pawn_area')) {
                tile_id = tile_id.slice(10);
            }

            if( this.isCurrentPlayerActive() )
            {     
                console.log( 'onTile' );

                if (this.selectedAction == 'move') {
                    if( this.checkAction( 'move' ) && dojo.hasClass(tile_id, 'possibleMove'))
                    {  
                        this.ajaxcall( "/forbiddenisland/forbiddenisland/moveAction.html", {
                            tile_id:tile_id
                        }, this, function( result ) {} );
                    }
                } else if (this.selectedAction == 'shore_up') {
                    if( this.checkAction( 'shore_up' ) && dojo.hasClass(tile_id, 'possibleMove'))
                    {  
                        this.ajaxcall( "/forbiddenisland/forbiddenisland/shoreUpAction.html", {
                            tile_id:tile_id
                        }, this, function( result ) {} );
                    }
                } else if (this.selectedAction == 'heli_lift') {
                    if( this.checkAction( 'move' ) && dojo.hasClass(tile_id, 'possibleMove'))
                    {  
                        if (tile_id == 'fools_landing' && this.isWinCondition ) {
                            this.setClientState("client_confirmWinGame", 
                            { descriptionmyturn : "${you} have your team and all four treasures.  Are you ready to lift off the island for the win!?!"});
                        } else if (this.startingTile == null) {
                            this.startingTile = tile_id;
                            this.setClientState("client_selectHeliLiftPlayers", 
                            { descriptionmyturn : "${you} are playing special action - Helicopter Lift. Select players to move."});
                        } else {
                            var card_id = this.selectedCard.split('_')[2];
                            this.ajaxcall( "/forbiddenisland/forbiddenisland/moveAction.html", {
                                tile_id:tile_id,
                                heli_lift: true,
                                card_id: card_id,
                                players: this.selectedPlayers.join(';')
                            }, this, function( result ) {} );
                        }
                    }
                } else if (this.selectedAction == 'sandbags') {
                    if( this.checkAction( 'shore_up' ) && dojo.hasClass(tile_id, 'possibleMove'))
                    {  
                        var card_id = this.selectedCard.split('_')[2];
                        this.ajaxcall( "/forbiddenisland/forbiddenisland/shoreUpAction.html", {
                            tile_id:tile_id,
                            sandbags: true,
                            card_id: card_id
                        }, this, function( result ) {} );
                    }
                }
            }
        },

        onCard: function( evt )
        {
            var card_id = evt.currentTarget.id;
            dojo.stopEvent( evt );

            this.selectedCard = card_id;

            if ( this.isCurrentPlayerActive() )
            {     
                console.log( 'onCard' );

                if (this.selectedAction == 'discard') {
                    if( this.checkAction( 'discard' ) && dojo.hasClass(dojo.byId(card_id), 'possibleCard'))
                    {  
                        var node = $(card_id);
                        dojo.addClass(node, 'selected');

                        var id = card_id.split('_')[2];
                        this.ajaxcall( "/forbiddenisland/forbiddenisland/discardTreasure.html", {
                            id:id
                        }, this, function( result ) {} );
                    }
                } else if (this.selectedAction == 'give_card') {
                    if( this.checkAction( 'give_card' ) && dojo.hasClass(dojo.byId(card_id), 'possibleCard'))
                    {  
                        var node = $(card_id);
                        dojo.addClass(node, 'selected');

                        this.updateColocatedPlayers(this.colocated_players);

                        // change to use setClientState()
                        var main = $('pagemaintitletext');
                        this.previous_pagemaintitletext = main.innerHTML;
                        main.innerHTML = _("Select a player on your tile");

                    }
                } else if (this.selectedAction == 'special_action') {
                    if( this.checkAction( 'special_action' ) && dojo.hasClass(dojo.byId(card_id), 'possibleCard'))
                    {  
                        var node = $(card_id);
                        dojo.addClass(node, 'selected');

                        var id = card_id.split('_')[2];
                        this.ajaxcall( "/forbiddenisland/forbiddenisland/playSpecial.html", {
                            id:id,
                            player_id: this.player_id
                        }, this, function( result ) {} );

                    }
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
                        var target_player_id = target_player_area.split('_')[3];
                        var id = card_id.split('_')[2];
                        this.ajaxcall( "/forbiddenisland/forbiddenisland/giveTreasure.html", { 
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

                if (this.selectedAction == 'heli_lift') {
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

            dojo.subscribe( 'reshuffleTreasureDeck', this, "notif_reshuffleTreasureDeck" );
            this.notifqueue.setSynchronous( 'reshuffleTreasureDeck', 1000 );
            
        },  
        
       notif_moveAction: function( notif )
       {
            console.log( 'notif_moveAction' );
            console.log( notif );
            
            // this.clearPossibleMoves();
            this.clearLastAction();


            if (notif.args.heli_lift) {
                this.discardTreasure(notif.args.card_id);
                notif.args.players.split(',').forEach( function(x) {
                    this.movePawn( notif.args.tile_id, x );
                }, this)
            } else {
                this.movePawn( notif.args.tile_id, notif.args.player_id );
            }

       },

       notif_shoreUpAction: function( notif )
       {
            console.log( 'notif_shoreUpAction' );
            console.log( notif );
            
            // this.clearPossibleMoves();
            this.clearLastAction();
            this.unfloodTile(notif.args.tile_id);

            if (notif.args.sandbags) {
                this.discardTreasure(notif.args.card_id);
            }

       },

       notif_skipAction: function( notif )
       {
            console.log( 'notif_skipAction' );
            console.log( notif );
            
            // this.clearPossibleMoves();
            this.clearLastAction();

       },

       notif_floodTile: function( notif )
       {
            console.log( 'notif_floodTile' );
            console.log( notif );

            var tile_id = notif.args.tile_id;
            var flood_card = notif.args.flood_card_type;

            this.floodTile(tile_id);
            this.placeFlood(flood_card);
            
       },

       notif_sinkTile: function( notif )
       {
            console.log( 'notif_sinkTile' );
            console.log( notif );

            var tile_id = notif.args.tile_id;
            var flood_card = notif.args.flood_card_type;

            this.sinkTile(tile_id);
            this.removeFlood(flood_card);
            
       },

       notif_watersRise: function( notif )
       {
            console.log( 'notif_watersRise' );
            console.log( notif );

            this.discardTreasure(notif.args.card.id);

            this.flood_card_area.removeAll();

            this.moveWaterLevel(notif.args.water_level);

       },

       notif_drawTreasure: function( notif )
       {
            console.log( 'notif_drawTreasure' );
            console.log( notif );

            this.placeTreasure(notif.args.card_1.id, notif.args.card_1.type, notif.args.player_id);
            this.placeTreasure(notif.args.card_2.id, notif.args.card_2.type, notif.args.player_id);

       },

       notif_discardTreasure: function( notif )
       {
            console.log( 'notif_discardTreasure' );
            console.log( notif );

            this.clearLastAction();
            this.discardTreasure(notif.args.card.id);
            // this.updateZone(this.player_card_area[notif.args.player_id]);

       },

       notif_giveTreasure: function( notif )
       {
            console.log( 'notif_giveTreasure' );
            console.log( notif );

            // this.clearPossibleCards();
            this.clearLastAction();
            this.moveTreasure(notif.args.card.id, notif.args.target_player_id);
            // setTimeout(() => { this.updateZone(this.player_card_area[notif.args.player_id]); }, 4000);

       },

       notif_captureTreasure: function( notif )
       {
            console.log( 'notif_captureTreasure' );
            console.log( notif );

            this.clearLastAction();
            notif.args.cards.forEach(
                function (c, index) {
                    this.discardTreasure(c.id);
            }, this);

            this.moveFigure(notif.args.treasure, notif.args.player_id);
            // this.updateZone(this.player_card_area[notif.args.player_id]);

       },

       notif_reshuffleTreasureDeck: function( notif )
       {
            this.treasure_card_area.removeAll();
       },
   });             
});
