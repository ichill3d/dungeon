<div class="dungeon-explorer" wire:ignore>
    <div class="bg-black relative" style="width: {{$dungeon->width}}rem; height: {{$dungeon->height}}rem;">

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

    <script>
        document.addEventListener('DOMContentLoaded', function () {
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
