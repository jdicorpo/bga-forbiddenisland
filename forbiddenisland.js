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
            this.selectedCard = '';
            this.possibleActions = [];
            this.player_treasure_cards = [];

            this.previous_pagemaintitletext = "";

            this.colocated_players = [];
            this.players = [];
              
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
                this.placePawn( player.adventurer, gamedatas.player_list[player.adventurer].pawn_idx, player.location);
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
            
            /* Example:
            
            case 'myGameState':
            
                // Show some HTML block at this game state
                dojo.style( 'my_html_block_id', 'display', 'block' );
                
                break;
           */

            case 'playerActions':
                this.selectedAction = 'move';
                this.possibleActions = args.args.possibleActions;
                var obj = args.args.player_treasure_cards;
                this.player_treasure_cards = Object.keys(obj).map(function(key) {
                    return obj[key];
                });
                this.updatePossibleMoves( this.possibleActions.move );
                var obj = args.args.colocated_players;  // don't need this ??
                this.colocated_players = Object.keys(obj).map(function(key) {
                    return obj[key];  // don't need this ??
                });
                var obj= args.args.players;
                this.players = Object.keys(obj).map(function(key) {
                    return obj[key];
                });
                // dojo.query( '.island_tile').connect( 'onclick', this, 'onTile');
                // dojo.query( '.pawn_area').connect( 'onclick', this, 'onTile');
                break;

            case 'discardTreasure':
                var obj = args.args.player_treasure_cards;
                this.player_treasure_cards = Object.keys(obj).map(function(key) {
                    return obj[key];
                });
                this.updatePossibleCards( this.player_treasure_cards );
                this.selectedAction = 'discard';
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
                        // dojo.place(this.format_block('jstpl_actions', {
                            // n : args.remaining_actions,
                        // }),'pagemaintitletext','last');
                        var main = $('pagemaintitletext');
                        main.innerHTML += '<span id="remaining_actions_value" style="font-weight:bold;color:#ED0023;">' 
                            + args.remaining_actions + '</span>' + _(' actions: ') + '<span style="font-weight:bold;color:#4871b6;">' 
                            + _('Move') + '</span>' + _(' or ');
                        this.addActionButton( 'shore_up_btn', _('Shore Up'), 'onShoreUp' ); 
                        this.addActionButton( 'give_treasure_btn', _('Give Card'), 'onGiveCard' ); 
                        this.addActionButton( 'capture_treasure_btn', _('Capture Treasure'), 'onCapture' ); 
                        this.addActionButton( 'skip_btn', _('Skip'), 'onSkip' ); 
                        break;

                    case 'discardTreasure':
                        // this.addActionButton( 'done_btn', _('Done'), 'onDone' ); 
                        // dojo.query('.possibleCard').connect('click', this, 'onCard');
                        // this.connectClass('possibleCard', 'onclick', 'onCard');
                        break;

                    case 'nextPlayer':
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

            // this.clearPossibleMoves();
            // this.clearPossibleCards();
            this.clearLastAction();
            if (typeof cards !== 'undefined') {
                cards.forEach(
                    function (c, index) {
                        var node = $('treasure_card_' + c.id);
                        dojo.addClass(node, 'possibleCard');
                        // dojo.connect(node, 'onclick', this, 'onCard');
                        // dojo.query('#treasure_card_'+c.id).addClass('possibleMove').connect('onclick', this, 'onCard');
                    });
                }
                this.connectClass('possibleCard', 'onclick', 'onCard');
        },

        updateColocatedPlayers: function(players) {

            // this.clearPossibleMoves();
            // this.clearPossibleCards();
            this.clearLastAction();
            // var player_id = this.getActivePlayerId();
            if (typeof players !== 'undefined') {
                players.forEach(
                    function (p, index) {
                        if (p.player_id != this.player_id) {
                            var node = $('player_card_area_' + p.player_id);
                            dojo.addClass(node, 'possiblePlayer');
                            // dojo.connect(node, 'onclick', this, 'onCard');
                            // dojo.query('#tresasure_card_'+c.id).addClass('possibleMove').connect('onclick', this, 'onCard');
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

            // dojo.place(this.format_block('jstpl_slider', {
            //     y : y,
            //     level : level
            // }), 'water_level_' + level, 'only');

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

            // var board_id = a + '_' + b;

            // var board_id = $(tile_id).parentNode.id;
            var img_id = this.gamedatas.tile_list[tile_id].img_id + 24;
            
            dojo.place(this.format_block('jstpl_tile', {
                x : this.tilewidth * ((img_id-1) % 8),
                y : this.tileheight * Math.trunc((img_id-1) / 8),
                id : tile_id,
            }), tile_id, 'replace');

            // dojo.place(this.format_block('jstpl_pawn_area', {
            //     id : tile_id,
            // }), 'island_tile_' + board_id);

            // this.pawn_area[tile_id].create( this, 'pawn_area_' + tile_id, this.pawnwidth, this.pawnheight);

        },

        unfloodTile : function(tile_id) {
            console.log( 'unfloodTile' );

            // var board_id = a + '_' + b;

            // var board_id = $(tile_id).parentNode.id;
            var img_id = this.gamedatas.tile_list[tile_id].img_id;
            
            dojo.place(this.format_block('jstpl_tile', {
                x : this.tilewidth * ((img_id-1) % 8),
                y : this.tileheight * Math.trunc((img_id-1) / 8),
                id : tile_id,
            }), tile_id, 'replace');

            // dojo.place(this.format_block('jstpl_pawn_area', {
            //     id : tile_id,
            // }), 'island_tile_' + board_id);

            // this.pawn_area[tile_id].create( this, 'pawn_area_' + tile_id, this.pawnwidth, this.pawnheight);

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

            // var idx = this.gamedatas.treasure_list[type].idx;
            // var location = 'player_card_area_' + player_id;
            // var zone = this.player_card_area[player_id];
            // var x = this.cardwidth * (idx-1);
            
            // zone.removeFromZone( 'treasure_card_' + id, true, 'treasure_deck' );

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
            debugger;
            this.figure_area[player_id].placeInZone('figure_' + treasure);

        },

        placePawn : function(adventurer, idx, tile_id) {

            console.log( 'placePawn' );

            var parent_id = $(tile_id).parentNode.id;
            var pawn_area = dojo.query('#' + parent_id + ' .pawn_area')[0];
            var x = 31.5 * (idx-1);

            dojo.place(this.format_block('jstpl_pawn', {
                id : adventurer,
                x : x,
            }), pawn_area, 'last');
            this.pawn_area[tile_id].placeInZone(adventurer);
        },

        movePawn : function(tile_id, player_id) {

            console.log( 'movePawn' );

            var player = this.gamedatas.players[player_id];
            // var idx = gamedatas.player_list[player.adventurer].idx;

            // var start_pid = $(player.location).parentNode.id;
            // var end_pid = $(tile_id).parentNode.id;
            // var start_pawn_area = dojo.query('#' + start_pid + ' .pawn_area')[0];
            // var end_pawn_area = dojo.query('#' + end_pid + ' .pawn_area')[0];
            
            this.pawn_area[tile_id].placeInZone(player.adventurer);
        },


        updatePossibleMoves : function( possibleMoves )
        {
            // this.clearPossibleMoves();
            this.clearLastAction();

            console.log( 'updatePossibleMoves' );

            possibleMoves.forEach(
                function (tile_id, index) {
                    dojo.query('#'+tile_id).addClass( 'possibleMove' );
                    dojo.query('#pawn_area_'+tile_id).addClass( 'possibleMove' );
                });

            if( this.isCurrentPlayerActive() )
            { 
                this.addTooltipToClass( 'possibleMove', '', _('Move to this tile.') );
            } else {
                dojo.query('.possibleMove').addClass( 'otherPlayer' );
            }
        },

        clearPossibleMoves : function( )
        {
            // Remove current possible moves
            dojo.query( '.possibleMove' ).removeClass( 'possibleMove' );
            dojo.query( '.otherPlayer' ).removeClass( 'otherPlayer' );
        },

        clearPossibleCards : function( )
        {
            // Remove current possible moves
            dojo.query( '.possibleCard' ).removeClass( 'possibleCard' );
            dojo.query( '.selected' ).removeClass( 'selected' );
        },

        clearPossiblePlayers : function( )
        {
            // Remove current possible moves
            dojo.query( '.possiblePlayer' ).removeClass( 'possiblePlayer' );
            dojo.query( '.selected' ).removeClass( 'selected' );
        },

        clearLastAction : function( )
        {
            // Remove current possible moves
            dojo.query( '.possibleMove' ).removeClass( 'possibleMove' );
            dojo.query( '.otherPlayer' ).removeClass( 'otherPlayer' );
            dojo.query( '.possibleCard' ).removeClass( 'possibleCard' );
            dojo.query( '.possiblePlayer' ).removeClass( 'possiblePlayer' );
            dojo.query( '.selected' ).removeClass( 'selected' );
        },

        getTooptipHtml : function(card)
        {

        },

        updateZone : function ( zone )
        {
            zone.getAllItems().forEach(
                function (x, index) {
                    zone.removeFromZone(x, false);
                    zone.placeInZone(x);
            }, this);
        },

        // addDiscOnBoard: function( x, y, player )
        // {
        //     var color = this.gamedatas.players[ player ].color;
            
        //     dojo.place( this.format_block( 'jstpl_disc', {
        //         xy: x+''+y,
        //         color: color
        //     } ) , 'discs' );
            
        //     this.placeOnObject( 'disc_'+x+''+y, 'overall_player_board_'+player );
        //     this.slideToObject( 'disc_'+x+''+y, 'square_'+x+'_'+y ).play();
        // }, 

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

        onShoreUp: function()
        {
            if( this.isCurrentPlayerActive() )
            {       
                console.log( 'onShoreUp' );
                
                var main = $('pagemaintitletext');
                this.previous_pagemaintitletext = main.innerHTML;
                main.innerHTML = _("Choose a tile to Shore Up");
                
                this.updatePossibleMoves(this.possibleActions.shore_up);
                
                this.selectedAction = 'shore_up';
                
                // dojo.place(this.format_block('jstpl_actions', {
                    //     n : args.remaining_actions,
                    // }),'pagemaintitletext','last');
                    
                this.addActionButton( 'cancel_btn', _('Cancel'), 'onCancel' );
            }
        },  

        onGiveCard: function()
        {
            if( this.isCurrentPlayerActive() )
            {       
                console.log( 'onGiveCard' );
                
                var main = $('pagemaintitletext');
                this.previous_pagemaintitletext = main.innerHTML;
                main.innerHTML = _("Choose a card to give");
                
                this.updatePossibleCards(this.player_treasure_cards);
                
                this.selectedAction = 'give_card';
                
                // dojo.place(this.format_block('jstpl_actions', {
                    //     n : args.remaining_actions,
                    // }),'pagemaintitletext','last');
                    
                this.addActionButton( 'cancel_btn', _('Cancel'), 'onCancel' );
            }
        },

        onCancel: function()
        {
            if( this.isCurrentPlayerActive() )
            {       
                console.log( 'onCancel' );
                
                this.updatePossibleMoves(this.possibleActions.move);
                
                this.selectedAction = 'move';
                
                var main = $('pagemaintitletext');
                main.innerHTML = this.previous_pagemaintitletext;

            }
            // dojo.place(this.format_block('jstpl_actions', {
            //     n : args.remaining_actions,
            // }),'pagemaintitletext','last');

            // this.addActionButton( 'cancel_btn', _('Cancel'), 'onCancel' );
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

                        var main = $('pagemaintitletext');
                        this.previous_pagemaintitletext = main.innerHTML;
                        main.innerHTML = _("Select a player on your tile");

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
            this.movePawn( notif.args.tile_id, notif.args.player_id );

       },

       notif_shoreUpAction: function( notif )
       {
            console.log( 'notif_shoreUpAction' );
            console.log( notif );
            
            // this.clearPossibleMoves();
            this.clearLastAction();
            this.unfloodTile(notif.args.tile_id);

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

            // this.clearPossibleCards();
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
            // this.updateZone(this.player_card_area[notif.args.player_id]);

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
