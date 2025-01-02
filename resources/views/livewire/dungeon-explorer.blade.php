<div class="dungeon-explorer" wire:ignore class="p-2">
    <button class="bg-gray-500 inline-block mr-3 p-2 text-white font-semibold" id="zoomIn">Zoom In + </button>
    <button class="bg-gray-500 inline-block mr-3 p-2 text-white font-semibold" id="zoomOut">Zoom Out -</button>
    <button class="bg-gray-500 inline-block mr-3 p-2 text-white font-semibold ml-8" id="revealDungeon">Reveal Dungeon</button>
    <button class="bg-gray-500 inline-block mr-3 p-2 text-white font-semibold ml-8" id="resetDungeon">Reset Dungeon</button>
    <div class="flex flex-row">
        <div id="dungeonHolder" class="w-3/4 overflow-auto " style="height: calc(100vh - 10rem)" >
            <div id="dungeonArea" class=" bg-gray-700">
                <div id="dungeon" class="bg-black relative " style="width: {{$dungeon->width}}rem; height: {{$dungeon->height}}rem;" >


                    <!-- Rooms -->
                    @foreach($rooms as $room)
                        @for($i = 0; $i < $room->height; $i++)
                            @for($j = 0; $j < $room->width; $j++)
                                <div data-room-id="{{ $room->id }}"
                                     data-x="{{ $room->x + $j }}" data-y="{{ $room->y  + $i }}"
                                     data-room-width="{{ $room->width }}" data-room-height="{{ $room->height }}"
                                     data-is-explored="{{ $room->is_explored }}"
                                     class="room tile absolute
                             @if($room->id === $startRoomId)
                                bg-green-500 border-green-500
                                @endif

                             @if($room->id === $bossRoomId)
                                bg-red-500
                                @endif
                                "
                                     style="top: {{ $room->y + $i }}rem;
                                    left: {{ $room->x + $j }}rem;
                                    width: 1rem;
                                    height: 1rem;
                                    @if($room->is_explored === 0)
                                    display: none;
                                    @endif
                             ">
                                </div>
                            @endfor
                        @endfor
                    @endforeach

                    <!-- Corridors -->
                    @foreach($corridors as $corridor)
                        @foreach(json_decode($corridor->cells) as $cell)
                            <div class="corridor tile absolute "
                                 data-corridor-id="{{ $corridor->id }}"
                                 data-is-explored="{{ $corridor->is_explored }}"
                                 @if($corridor->is_trapped === 1)
                                     data-trap-triggered="{{ $corridor->trap_triggered }}"
                                 @endif
                                 data-x="{{ $cell->x }}" data-y="{{ $cell->y }}"
                                 style="top: {{ $cell->y }}rem; left: {{ $cell->x }}rem; width: 1rem; height: 1rem;
                          @if($corridor->is_explored === 0)
                         display: none;
                         @endif
                         ">
                            </div>
                        @endforeach
                    @endforeach

                    <!-- Doors -->
                    @foreach($doors as $door)
                        <div data-door-id="{{ $door->id }}" data-x="{{ $door->x }}" data-y="{{ $door->y }}"
                             class="door tile absolute @if($door->is_open) door-open @endif"
                             data-is-explored="{{ $door->is_explored }}"
                             style="top: {{ $door->y }}rem; left: {{ $door->x }}rem; width: 1rem; height: 1rem;
                      @if($door->is_explored === 0)
                     display: none;
                     @endif
                     ">
                        </div>
                    @endforeach

                </div>
            </div>
        </div>
        <div class="w-1/4 h-full bg-green-300 p-4 m-2 rounded-xl">
            @foreach($rooms as $room)
                @php
                $roomData = json_decode($room->description);
                @endphp
               <div style="display: none" class="object-description" data-object-type="room" data-object-id="{{ $room->id }}">
                   <div>
                       <button class="show-data">Show Data</button>
                       <pre style="display: none"> {{$room->description}}</pre>
                   </div>

                   <div class="flex flex-row justify-between">
                       <div class="font-bold">{{ $roomData->room_name }}</div>
                       <button   class="zoomToObjectButton px-2 py-1 bg-green-600 text-white hover hover:bg-green-500 rounded-lg">Zoom to Room</button>
                   </div>

                   <div class="mt-1">{{ $roomData->room_description }}</div>

                   @if($room->type === 'puzzle')

                       <div class="mt-2">
                           <span class="font-bold">Puzzle: </span>
                           {{ $roomData->puzzle->description }}
                           <button class="showPuzzleSolution px-2 py-1 bg-green-600 text-white hover hover:bg-green-500 rounded-lg">Solution</button>
                           <div class="puzzleSolution mt-2" style="display: none">
                               <div class="mb-2">
                                   <span class="font-bold">Solution:</span>
                                   <div class="mb-2">{{ $roomData->puzzle->solution }}</div>
                                   <button class="showPuzzleReward px-2 py-1 bg-green-600 text-white hover hover:bg-green-500 rounded-lg">Reward</button>
                               </div>
                               <div class="puzzleReward mb-2" style="display: none">
                                   <span class="font-bold">Reward:</span>
                                   {{ $roomData->puzzle->reward }}
                               </div>
                           </div>
                       </div>

                   @endif

                   @if($room->type === 'boss' || $room->type === 'monster')
                    <div class="bold">Monsters:</div>
                       @if(!empty($roomData->boss_monster))
                           <div class="inline-block font-semibold"><span class="inline-block px-1">BOSS</span> {{ $roomData->boss_monster->name }}, </div>
                       @endif
                       @if(isset($roomData->monsters))
                           @if(is_array($roomData->monsters))
                               @foreach($roomData->monsters as $monster)
                                   {{ $monster->amount }} x {{ $monster->name }},
                               @endforeach
                           @else
                               {{ $roomData->monsters->amount }} x {{ $roomData->monsters->name }}
                           @endif
                       @endif
                   @endif
                   @if($room->type === 'boss')
                       <x-monster-stats :monster="$roomData->boss_monster" :type="'boss'"/>
                   @endif
                   @if(isset($roomData->monster))
                       <x-monster-stats :monster="$roomData->monster" :type="'monster'"/>
                   @endif
                   @if(isset($roomData->monsters))
                       @if(is_array($roomData->monsters))
                           @foreach($roomData->monsters as $monster)
                               <x-monster-stats :monster="$monster" :type="'monster'"/>
                           @endforeach
                       @else
                           <x-monster-stats :monster="$roomData->monsters" :type="'monster'"/>
                       @endif
                   @endif
               </div>
            @endforeach
            @foreach($corridors as $corridor)

                    @php
                        $corridorData = json_decode($corridor->description);
                    @endphp
                <div style="display: none"  class="object-description" data-object-type="corridor" data-object-id="{{ $corridor->id }}">
                    <div>
                        <button class="show-data">Show Data</button>
                        <pre style="display: none"> {{$corridor}}</pre>
                    </div>


                    <div class="flex flex-row justify-between">
                        <div class="font-bold">Corridor</div>
                        <button class="zoomToObjectButton px-2 py-1 bg-green-600 text-white hover hover:bg-green-500 rounded-lg">Zoom to Corridor</button>
                    </div>

                    <div class="mt-1">{{ $corridor->description }}</div>

                    <div style="display: none" class="trap-description mt-2 p-4 bg-green-700 text-white rounded-xl">
                        <div class="font-bold mb-1">It's a trap!</div>
                        @if($corridor->is_trapped === 1)
                            @php
                                $trapData = json_decode($corridor->trap_description);
                            @endphp
                        {{ $trapData->description }}
                            <div><span class="font-bold">Effects: </span> {{ $trapData->effects }}</div>
                            <div class="italic">
                               The trap has been triggered. You may open the door.
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <style>
        .room {
            width: 1rem;  /* Width of each tile */
            height: 1rem; /* Height of each tile */
            background-image: url('{{ asset("storage/assets/floors/dungeon2.png") }}');  /* Path to your sprite image */
            background-size: 4rem 4rem;  /* Total size of the sprite image */
        }
        .corridor {
            width: 1rem;  /* Width of each tile */
            height: 1rem; /* Height of each tile */
            background-image: url('{{ asset("storage/assets/floors/dungeon2.png") }}');  /* Path to your sprite image */
            background-size: 4rem 4rem;  /* Total size of the sprite image */
        }
        .door {
            width: 1rem;  /* Width of each tile */
            height: 1rem; /* Height of each tile */
            background-image: url('{{ asset("storage/assets/doors/door1.png") }}');  /* Path to your sprite image */
            background-size: 1rem 1rem;  /* Total size of the sprite image */
        }
        .door-open {
            background-image: url('{{ asset("storage/assets/doors/door1_open.png") }}');  /* Path to your sprite image */
        }

        .selected-overlay {
            position: absolute;
        }

        .selected-overlay::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.3); /* Semi-transparent red */
            pointer-events: none; /* Allows clicks to pass through */
            z-index: 10; /* Ensure it’s above other content */
        }

    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {


            $(".show-data").click(function(){
                $(this).siblings("pre").slideToggle();
            });

            $(".showPuzzleSolution").click(function(){
                $(this).siblings(".puzzleSolution").slideToggle();
            });
            $(".showPuzzleReward").click(function(){
                $(this).parent().parent().find(".puzzleReward").slideToggle();
            });
            $("#revealDungeon").click(function(){
                $(".room, .corridor, .door").css("display", "block").attr('data-is-explored', 1);
                Livewire.dispatch('exploreDungeon');
            });
            $("#resetDungeon").click(function () {
                $(".room, .corridor, .door").css("display", "none").attr('data-is-explored', 0);
                $(".corridor").removeAttr('data-trap-triggered');
                $(".door").removeClass("door-open");
                setTimeout(function () {
                    let startRoomId = {{ $startRoomId }};  // Pass the starting room ID
                    showRoom(startRoomId);
                    showAdjacentDoorsToRoom(startRoomId);
                }, 0);
                Livewire.dispatch('resetDungeon');
            });


            $(".zoomToObjectButton").click(function(){
                let objectType = $(this).parent().parent().data('object-type');
                let objectId = $(this).parent().parent().data('object-id');
                zoomToObject(objectType, objectId);
            });
            function setRandomTile() {
                // Select all div elements with the class .sprite
                $('.room, .corridor').each(function() {
                    // Generate a random row (0-3) and column (0-3) for the tile
                    let randomRow = Math.floor(Math.random() * 4);  // Random row (0 to 3)
                    let randomCol = Math.floor(Math.random() * 4);  // Random column (0 to 3)

                    // Calculate the background position based on the row and column
                    let xPos = randomCol * 1;  // 1rem per tile horizontally
                    let yPos = randomRow * 1;  // 1rem per tile vertically

                    // Apply the background position to the div
                    $(this).css('background-position', `-${xPos}rem -${yPos}rem`);
                });
            }

            // Call the function to assign random tiles when the document is ready
            setRandomTile();


            function showItemDescription(objectType, objectId) {
                $(".object-description").hide();
                let objectDescription = $('.object-description[data-object-type="'+objectType+'"][data-object-id="'+objectId+'"]');
                objectDescription.fadeIn();
            }



            function zoomToObject(objectType, objectId) {

                    let Xs = [];
                    let Ys = [];
                    let minX = 0;
                    let minY = 0;
                    let maxX = 0;
                    let maxY = 0;

                    let objectTopEdge = 0;
                    let objectLeftEdge = 0;
                    let objectBottomEdge = 0;
                    let objectRightEdge = 0;
                    let objectWidth = 0;
                    let objectHeight = 0;

                    // Get the container's dimensions (dungeon area and visible holder)
                    let container = $("#dungeonArea");
                    let containerHolder = $("#dungeonHolder");

                    // Get the current zoom value (if applied) or assume 1 if not
                    let currentZoom = container.css('zoom') !== 'normal' ? parseFloat(container.css('zoom')) : 1;
                    container.css('zoom', currentZoom);

                    let holderWidth = containerHolder.width();
                    let holderHeight = containerHolder.height();

                    // Collect all the cells that belong to the room
                    console.log("."+objectType+"[data-"+objectType+"-id='" + objectId + "']");
                    $("."+objectType+"[data-"+objectType+"-id='" + objectId + "']").each(function() {
                        Xs.push($(this).data('x'));
                        Ys.push($(this).data('y'));
                    }).promise().done(function() {
                        // Calculate min/max X and Y values to determine room boundaries
                        minX = Math.min.apply(null, Xs);
                        minY = Math.min.apply(null, Ys);
                        maxX = Math.max.apply(null, Xs);
                        maxY = Math.max.apply(null, Ys);

                        // Get the position and size of the room based on minX and minY
                        objectTopEdge = $("."+objectType+"[data-x='" + minX + "'][data-y='" + minY + "']").position().top;
                        objectLeftEdge = $("."+objectType+"[data-x='" + minX + "'][data-y='" + minY + "']").position().left;
                        objectBottomEdge = objectTopEdge + $("."+objectType+"[data-x='" + minX + "'][data-y='" + minY + "']").outerHeight();
                        objectRightEdge = objectLeftEdge + $("."+objectType+"[data-x='" + minX + "'][data-y='" + minY + "']").outerWidth();

                        // Calculate the full width and height of the room (assuming each cell has the same width and height)
                        objectWidth = (maxX - minX + 1) * $("."+objectType).outerWidth();
                        objectHeight = (maxY - minY + 1) * $("."+objectType).outerHeight();

                        // Calculate the scale based on the visible holder's size
                        let scaleX = holderWidth / (objectWidth + (objectWidth * 30 / 100));
                        let scaleY = holderHeight / (objectHeight + (objectHeight * 30 / 100));
                        let scale = Math.min(scaleX, scaleY); // Use the smaller scale to ensure the room fits

                        scale = Math.min(scale, 7)
                        // Calculate the new zoom based on the existing zoom and the new scale
                        let newZoom = scale;

                        // Apply the new zoom (scale) to the #dungeonArea (scrollable parent)
                        container.css('zoom', newZoom);

                        setTimeout(function(){
                            objectTopEdge = $("."+objectType+"[data-x='" + minX + "'][data-y='" + minY + "']").position().top;
                            objectLeftEdge = $("."+objectType+"[data-x='" + minX + "'][data-y='" + minY + "']").position().left;
                            // Calculate the scaled dimensions of the room after zooming
                            let scaledObjectWidth = objectWidth * newZoom;
                            let scaledObjectHeight = objectHeight * newZoom;

                            // Calculate the scroll position to center the room within the zoomed container
                            let scrollTop = objectTopEdge  - ((holderHeight - scaledObjectHeight) / 2);
                            let scrollLeft = objectLeftEdge  - ((holderWidth - scaledObjectWidth) / 2);

                            console.log("scrollTop: " + scrollTop);
                            console.log("scrollLeft: " + scrollLeft);

                            // Animate the scroll to center the room smoothly
                            containerHolder.animate({
                                scrollTop: scrollTop,
                                scrollLeft: scrollLeft
                            }, 1); // 500ms for smooth scrolling

                        }, 10);


                    });

                }

            function selectObject(objectType, objectId) {
                // Remove existing overlays
                $(".room, .corridor").removeClass('selected-overlay');

                // Add overlay to the selected object
                $("." + objectType + "[data-" + objectType + "-id='" + objectId + "']").addClass('selected-overlay');
            }


            $(".room").on('click', function () {
                showItemDescription('room', $(this).data('room-id'));
                selectObject('room', $(this).data('room-id'));
            });

            $(".room").on('dblclick', function (e) {
                e.preventDefault(); // Prevent default double-click behavior
                zoomToObject('room', $(this).data('room-id'));
            });

            $(".corridor").on('click', function () {
                showItemDescription('corridor', $(this).data('corridor-id'));
                selectObject('corridor', $(this).data('corridor-id'));
            });

            $(".corridor").on('dblclick', function (e) {
                e.preventDefault();
                zoomToObject('corridor', $(this).data('corridor-id'));
            });


            // Initial zoom level (no zoom)
            const zoomFactor = 0.5; // Zoom step
            const minZoom = 0.5; // Minimum zoom level
            const maxZoom = 4.5; // Maximum zoom level
            const container = $('#dungeonArea');


            $('#zoomIn').click(function() {

                let currentZoom = container.css('zoom') !== 'normal' ? parseFloat(container.css('zoom')) : 1;
                if (currentZoom < maxZoom) { // Ensure zoom level doesn't exceed max
                    currentZoom += zoomFactor; // Increase zoom level
                    updateZoom(currentZoom);
                }
            });

            $('#zoomOut').click(function() {
                let currentZoom = container.css('zoom') !== 'normal' ? parseFloat(container.css('zoom')) : 1;
                if (currentZoom > minZoom) { // Ensure zoom level doesn't go below min
                    currentZoom -= zoomFactor; // Decrease zoom level
                    updateZoom(currentZoom);
                }
            });

            function updateZoom(currentZoom) {
                let scrollTop = container.scrollTop();
                let scrollLeft = container.scrollLeft();

                // Apply zoom to the container
                container.css('zoom', currentZoom);

                // Calculate the new scroll position to keep the same center
                let newScrollTop = (scrollTop + container.height() / 2) * currentZoom - container.height() / 2;
                let newScrollLeft = (scrollLeft + container.width() / 2) * currentZoom - container.width() / 2;

                // Apply the new scroll position
                container.scrollTop(newScrollTop);
                container.scrollLeft(newScrollLeft);
            }





            var startRoomId = {{ $startRoomId }};  // Pass the starting room ID

            showRoom(startRoomId);
            showAdjacentDoorsToRoom(startRoomId);

            function showRoom(roomId) {
                $('[data-room-id="' + roomId + '"]').fadeIn();  // Reveal the initial room
                $('[data-room-id="' + roomId + '"]').attr('data-is-explored', 1);
                Livewire.dispatch('revealRoom', { roomId: roomId });
            }
            function showCorridor(corridorId){
                $('.corridor[data-corridor-id="' + corridorId + '"]').fadeIn();
                $('.corridor[data-corridor-id="' + corridorId + '"]').attr('data-is-explored', 1);
                Livewire.dispatch('revealCorridor', { corridorId: corridorId });
            }

            function showAdjacentDoorsToRoom(roomId) {
                var roomElement = $('[data-room-id="' + roomId + '"]');
                var roomX = roomElement.data('x');
                var roomY = roomElement.data('y');
                var roomWidth = roomElement.data('room-width');  // Make sure you set data-room-width in your HTML
                var roomHeight = roomElement.data('room-height');  // Make sure you set data-room-height in your HTML

                // Loop over the edges of the room and check for adjacent doors
                for (let i = roomX; i < roomX + roomWidth; i++) {
                    for (let j = roomY; j < roomY + roomHeight; j++) {
                        // Check the tiles adjacent to the room
                        let adjacentTiles = [
                            [i - 1, j],  // left
                            [i + 1, j],  // right
                            [i, j - 1],  // top
                            [i, j + 1]   // bottom
                        ];

                        $(adjacentTiles).each(function (index, value) {
                            let checkIfDiscoveredDoor = $(".door[data-x='" + value[0] + "'][data-y='" + value[1] + "']");
                            if (checkIfDiscoveredDoor.length) {
                                let discoveredDoorId = checkIfDiscoveredDoor.data('door-id'); // Correctly get door id
                                console.log("Discovered door ID: " + discoveredDoorId);

                                $(".door[data-door-id='" + discoveredDoorId + "']").fadeIn(); // Show the adjacent door
                                $(".door[data-door-id='" + discoveredDoorId + "']").attr('data-is-explored', 1);
                                Livewire.dispatch('revealDoor', { doorId: discoveredDoorId });
                            }
                        });
                    }
                }
            }

            function showAdjacentDoorsToCorridor(corridorId) {
                $('.corridor[data-corridor-id="' + corridorId + '"]').each(function(){
                    let corridorX = $(this).data('x');
                    let corridorY = $(this).data('y');
                    let adjacentTiles = [
                        [corridorX - 1, corridorY],  // left
                        [corridorX + 1, corridorY],  // right
                        [corridorX, corridorY - 1],  // top
                        [corridorX, corridorY + 1]   // bottom
                    ];
                    $(adjacentTiles).each(function (index, value) {
                        let checkIfDiscoveredDoor = $(".door[data-x='" + value[0] + "'][data-y='" + value[1] + "']");
                        if (checkIfDiscoveredDoor.length) {
                            let discoveredDoorId = checkIfDiscoveredDoor.data('door-id'); // Correctly get door id
                            console.log("Discovered door ID: " + discoveredDoorId);
                            $(".door[data-door-id='" + discoveredDoorId + "']").fadeIn(); // Show the adjacent door
                        }
                    });
                });

            }

            function triggerTrapIfAvailable(doorId) {

                let doorElement = $(`.door[data-door-id='${doorId}']`);
                let corridorId = null;

                let doorX = doorElement.data('x');
                let doorY = doorElement.data('y');

                let adjacentTiles = [
                    { x: doorX - 1, y: doorY }, // left
                    { x: doorX + 1, y: doorY }, // right
                    { x: doorX, y: doorY - 1 }, // top
                    { x: doorX, y: doorY + 1 }  // bottom
                ];

                let pairs = [
                    [adjacentTiles[0], adjacentTiles[1]],
                    [adjacentTiles[2], adjacentTiles[3]]
                ];

                // Check pairs for a valid corridor-room match
                pairs.forEach(pair => {
                    let firstTile = $(`.tile[data-x='${pair[0].x}'][data-y='${pair[0].y}']`);
                    let secondTile = $(`.tile[data-x='${pair[1].x}'][data-y='${pair[1].y}']`);

                    if (
                        firstTile.hasClass('corridor') &&
                        firstTile.data('is-explored') === 1 &&
                        firstTile.data('trap-triggered') !== 1 &&
                        secondTile.hasClass('room') &&
                        secondTile.data('is-explored') === 0
                    ) {
                        corridorId = firstTile.data('corridor-id');
                    }
                    else if (
                        firstTile.hasClass('room') &&
                        firstTile.data('is-explored') === 0 &&
                        secondTile.hasClass('corridor') &&
                        secondTile.data('is-explored') === 1 &&
                        secondTile.data('trap-triggered') !== 1
                    ) {
                        corridorId = secondTile.data('corridor-id');
                    }
                });

                // No valid corridor found
                if (!corridorId) return false;

                let $corridor = $(`.corridor[data-corridor-id='${corridorId}']`);

                if($corridor.attr('data-trap-triggered')) {
                    console.log($corridor.attr('data-trap-triggered'));
                }
                // Explicitly check `data-trap-triggered` value
                if ($corridor.attr('data-trap-triggered') === 1 || !$corridor.attr('data-trap-triggered')) {
                    return false;
                }



                // Trigger the trap for the first time
                showItemDescription("corridor", corridorId);
                selectObject('corridor', corridorId);
                $(`.object-description[data-object-type='corridor'][data-object-id='${corridorId}'] .trap-description`)
                    .fadeIn();

                // Update `data` cache and attribute
                $corridor.data('trap-triggered', 1); // Update jQuery's internal cache
                $corridor.attr('data-trap-triggered', 1); // Ensure DOM reflects the change

                Livewire.dispatch('triggerTrap', { corridorId: corridorId });

                console.log('Trap successfully triggered.');
                return true;
            }




            $('.door').on('click', function () {



                var doorId = $(this).data('door-id');

                if(triggerTrapIfAvailable(doorId)) {
                    return;
                }



                let doorX = $(this).data('x');
                let doorY = $(this).data('y');
                let adjacentTiles = [
                    [doorX - 1, doorY],  // left
                    [doorX + 1, doorY],  // right
                    [doorX, doorY - 1],  // top
                    [doorX, doorY + 1]   // bottom
                ];
                Livewire.dispatch('openDoor', { doorId: doorId });
                $(this).addClass('door-open');
                $(adjacentTiles).each(function (index, value) {
                    let checkIfDiscoveredRoom = $(".room[data-x='" + value[0] + "'][data-y='" + value[1] + "']");
                    if(checkIfDiscoveredRoom.length) {
                        let discoveredRoomId = checkIfDiscoveredRoom.data('room-id');
                        //$(".room[data-room-id='" + discoveredRoomId + "']").fadeIn();
                        showRoom(discoveredRoomId);
                        // Show the adjacent doors to the initial room
                        showAdjacentDoorsToRoom(discoveredRoomId);
                    }

                    let checkIfDiscoveredCorridor = $(".corridor[data-x='" + value[0] + "'][data-y='" + value[1] + "']");
                    if(checkIfDiscoveredCorridor.length) {
                        let discoveredCorridorId = checkIfDiscoveredCorridor.data('corridor-id');
                        showCorridor(discoveredCorridorId);
                        showAdjacentDoorsToCorridor(discoveredCorridorId);
                    }

                });
            });

            function showRevealedDoorsOnLoad() {
                let roomIds = [];
                let corridorIds = [];

                // Collect explored room IDs
                $(".room[data-is-explored='1']").each(function () {
                    let roomId = $(this).data('room-id');
                    if (!roomIds.includes(roomId)) {
                        roomIds.push(roomId);
                    }
                });

                // Collect explored corridor IDs
                $(".corridor[data-is-explored='1']").each(function () {
                    let corridorId = $(this).data('corridor-id'); // Correct attribute
                    if (!corridorIds.includes(corridorId)) {
                        corridorIds.push(corridorId);
                    }
                });

                // Process rooms
                roomIds.forEach(function (value) {
                    showAdjacentDoorsToRoom(value);
                });

                // Process corridors
                corridorIds.forEach(function (value) {
                    showAdjacentDoorsToCorridor(value);
                });
            }

            showRevealedDoorsOnLoad();

        });
    </script>


</div>
