<div class="dungeon-explorer" wire:ignore class="p-2">
    <button class="bg-gray-500 inline-block mr-3 p-2 text-white font-semibold" id="zoomIn">Zoom In + </button>
    <button class="bg-gray-500 inline-block mr-3 p-2 text-white font-semibold" id="zoomOut">Zoom Out -</button>
    <div id="dungeonHolder" class="w-full overflow-auto" style="height: 50rem;">
        <div id="dungeonArea" style="width: 300rem; height: 300rem;" class=" bg-gray-700">
            <div id="dungeon" class="bg-black relative " style="width: {{$dungeon->width}}rem; height: {{$dungeon->height}}rem;" >


            <!-- Rooms -->
            @foreach($rooms as $room)
                @for($i = 0; $i < $room->height; $i++)
                    @for($j = 0; $j < $room->width; $j++)
                        <div data-room-id="{{ $room->id }}"
                             data-x="{{ $room->x + $j }}" data-y="{{ $room->y  + $i }}"
                             data-room-width="{{ $room->width }}" data-room-height="{{ $room->height }}"
                             data-is-explored="{{ $room->is_explored }}"
                             class="room absolute border border-gray-300 bg-gray-500
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
                    <div class="corridor absolute bg-blue-600 border border-gray-300"
                         data-corridor-id="{{ $corridor->id }}"
                         data-is-explored="{{ $corridor->is_explored }}"
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
                <div data-door-id="{{ $door->id }}" data-x="{{ $door->x }}" data-y="{{ $door->y }}" class="door absolute bg-orange-500 border border-gray-300"
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
    <script>
        document.addEventListener('DOMContentLoaded', function () {



            $(".room").click(function() {

                let thisRoomId = $(this).data('room-id');

                let Xs = [];
                let Ys = [];
                let minX = 0;
                let minY = 0;
                let maxX = 0;
                let maxY = 0;
                let roomTopEdge = 0;
                let roomLeftEdge = 0;
                let roomBottomEdge = 0;
                let roomRightEdge = 0;
                let roomWidth = 0;
                let roomHeight = 0;

                // Get the container's dimensions (dungeon area and visible holder)
                let container = $("#dungeonArea");
                let containerHolder = $("#dungeonHolder");



                // Get the current zoom value (if applied) or assume 1 if not
                let currentZoom = container.css('zoom') !== 'normal' ? parseFloat(container.css('zoom')) : 1;
                container.css('zoom', currentZoom);

                let containerWidth = container.width();
                let containerHeight = container.height();
                let holderWidth = containerHolder.width();
                let holderHeight = containerHolder.height();



                // Collect all the cells that belong to the room
                $(".room[data-room-id='" + thisRoomId + "']").each(function() {
                    Xs.push($(this).data('x'));
                    Ys.push($(this).data('y'));
                }).promise().done(function() {
                    // Calculate min/max X and Y values to determine room boundaries
                    minX = Math.min.apply(null, Xs);
                    minY = Math.min.apply(null, Ys);
                    maxX = Math.max.apply(null, Xs);
                    maxY = Math.max.apply(null, Ys);

                    // Get the position and size of the room based on minX and minY
                    roomTopEdge = $(".room[data-x='" + minX + "'][data-y='" + minY + "']").position().top;
                    roomLeftEdge = $(".room[data-x='" + minX + "'][data-y='" + minY + "']").position().left;
                    roomBottomEdge = roomTopEdge + $(".room[data-x='" + minX + "'][data-y='" + minY + "']").outerHeight();
                    roomRightEdge = roomLeftEdge + $(".room[data-x='" + minX + "'][data-y='" + minY + "']").outerWidth();

                    // Calculate the full width and height of the room (assuming each cell has the same width and height)
                    roomWidth = (maxX - minX + 1) * $(".room").outerWidth();
                    roomHeight = (maxY - minY + 1) * $(".room").outerHeight();

                    console.log("roomTopEdge: " + roomTopEdge);
                    console.log("roomLeftEdge: " + roomLeftEdge);
                    console.log("roomBottomEdge: " + roomBottomEdge);
                    console.log("roomRightEdge: " + roomRightEdge);
                    console.log("roomWidth: " + roomWidth);
                    console.log("roomHeight: " + roomHeight);

                    // Calculate the scale based on the visible holder's size
                    let scaleX = holderWidth / (roomWidth + (roomWidth * 30 / 100));
                    let scaleY = holderHeight / (roomHeight + (roomHeight * 30 / 100));
                    let scale = Math.min(scaleX, scaleY); // Use the smaller scale to ensure the room fits

                    // Calculate the new zoom based on the existing zoom and the new scale
                    let newZoom = scale;


                    console.log("scale: " + scale);

                    console.log("currentZoom: " + currentZoom);
                    console.log("newZoom: " + newZoom);

                    // Apply the new zoom (scale) to the #dungeonArea (scrollable parent)
                    container.css('zoom', newZoom);

                    setTimeout(function(){
                        roomTopEdge = $(".room[data-x='" + minX + "'][data-y='" + minY + "']").position().top;
                        roomLeftEdge = $(".room[data-x='" + minX + "'][data-y='" + minY + "']").position().left;
                        // Calculate the scaled dimensions of the room after zooming
                        let scaledRoomWidth = roomWidth * newZoom;
                        let scaledRoomHeight = roomHeight * newZoom;

                        // Calculate the scroll position to center the room within the zoomed container
                        let scrollTop = roomTopEdge  - ((holderHeight - scaledRoomHeight) / 2);
                        let scrollLeft = roomLeftEdge  - ((holderWidth - scaledRoomWidth) / 2);

                        console.log("scrollTop: " + scrollTop);
                        console.log("scrollLeft: " + scrollLeft);

                        // Animate the scroll to center the room smoothly
                        containerHolder.animate({
                            scrollTop: scrollTop,
                            scrollLeft: scrollLeft
                        }, 1); // 500ms for smooth scrolling

                    }, 10);


                });
            });








            let zoomLevel = 1; // Initial zoom level (no zoom)
            const zoomFactor = 0.5; // Zoom step
            const minZoom = 0.5; // Minimum zoom level
            const maxZoom = 4.5; // Maximum zoom level

            // Apply initial smooth transition
            $('#dungeon').css('transition', 'transform 0.3s ease'); // Smooth zoom effect

            $('#zoomIn').click(function() {
                if (zoomLevel < maxZoom) { // Ensure zoom level doesn't exceed max
                    zoomLevel += zoomFactor; // Increase zoom level
                    updateZoom();
                }
            });

            $('#zoomOut').click(function() {
                if (zoomLevel > minZoom) { // Ensure zoom level doesn't go below min
                    zoomLevel -= zoomFactor; // Decrease zoom level
                    updateZoom();
                }
            });

            function updateZoom() {
                $('#dungeon').css('zoom', zoomLevel);
            }





            var startRoomId = {{ $startRoomId }};  // Pass the starting room ID

            // Show the initial room
            showRoom(startRoomId);
            // Show the adjacent doors to the initial room
            showAdjacentDoorsToRoom(startRoomId);

            // Show the room by ID
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

            // Show doors adjacent to the current room
            // Show doors adjacent to the current room
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


            $('.door').on('click', function () {
                var doorId = $(this).data('door-id');
                let doorX = $(this).data('x');
                let doorY = $(this).data('y');
                let adjacentTiles = [
                    [doorX - 1, doorY],  // left
                    [doorX + 1, doorY],  // right
                    [doorX, doorY - 1],  // top
                    [doorX, doorY + 1]   // bottom
                ];
                $(adjacentTiles).each(function (index, value) {
                    let checkIfDiscoveredRoom = $(".room[data-x='" + value[0] + "'][data-y='" + value[1] + "']");
                    if(checkIfDiscoveredRoom.length) {
                        let discoveredRoomId = checkIfDiscoveredRoom.data('room-id');
                        console.log("discoveredRoomId: " + discoveredRoomId);
                        //$(".room[data-room-id='" + discoveredRoomId + "']").fadeIn();
                        showRoom(discoveredRoomId);
                        // Show the adjacent doors to the initial room
                        showAdjacentDoorsToRoom(discoveredRoomId);
                    }

                    let checkIfDiscoveredCorridor = $(".corridor[data-x='" + value[0] + "'][data-y='" + value[1] + "']");
                    if(checkIfDiscoveredCorridor.length) {
                        let discoveredCorridorId = checkIfDiscoveredCorridor.data('corridor-id');
                        console.log("discoveredCorridorId: " + discoveredCorridorId);
                        showCorridor(discoveredCorridorId);
                        showAdjacentDoorsToCorridor(discoveredCorridorId);
                    }

                });
            });
        });
    </script>

</div>
