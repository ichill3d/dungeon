<div class="flex w-full">
    <!-- First Column (Fixed Width: 2/5) -->
    <div class="w-1/4 flex flex-col p-2">
        <div class="flex flex-col py-2 border-b border-gray-300">
            <div class="mb-2">Setting:</div>
            <select
                wire:model.live="selectedDungeonSetting"
                name="dungeon_setting"
                id="dungeon_setting"
                class="form-select border border-gray-300 rounded p-2">
                @foreach($dungeonSettings as $setting)
                    <option
                        @if($setting->id === $dungeon->dungeon_setting_id)
                            selected
                        @endif
                        value="{{ $setting->id }}">{{ $setting->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex flex-col py-2 border-b border-gray-300">
            <div class="mb-2">Dungeon Type:</div>
            <select

                wire:model.live="selectedDungeonType"
                name="dungeon_type"
                id="dungeon_type"
                class="form-select border border-gray-300 rounded p-2">
                @foreach($dungeonTypes as $dungeonType)
                    <option
                        @if($dungeonType->id === $dungeon->dungeon_type_id)
                            selected
                        @endif
                        value="{{ $dungeonType->id }}">{{ $dungeonType->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex flex-col py-2 border-b border-gray-300">
            <div class="mb-2">User Inspiration:</div>
            <textarea
                wire:model.live.debounce="userInspiration"
                class="border border-gray-300 rounded p-2" ></textarea>
        </div>
        <div class="flex flex-col py-2 border-b border-gray-300">
            <div class="mb-2">Dungeon Description:</div>
            <textarea
                wire:model.live.debounce="dungeonDescription"
                name="dungeon_description"
                id="dungeon_description"
                class="border border-gray-300 rounded p-2"></textarea>
            <button
                wire:click="generateDungeonDescription"
                class="mt-2 bg-blue-500 text-white py-1 px-4 rounded hover:bg-blue-600"
            >
                Generate
            </button>
        </div>
        <div class="flex flex-col py-2 border-b border-gray-300">
            <div class="mb-2">Dungeon Name:</div>
            <input type="text"
                wire:model.live.debounce="dungeonName"
                class="border border-gray-300 rounded p-2"/>
            <button
                wire:click="generateDungeonName"
                class="mt-2 bg-blue-500 text-white py-1 px-4 rounded hover:bg-blue-600"
            >
                Generate
            </button>
        </div>
        <div class="flex flex-col py-2 border-b border-gray-300">
            <div class="mb-2">Rooms</div>
            <select wire:model.live.debounce="selectedRoomId">
                <option value="0">All</option>
                @foreach($dungeon->rooms as $room)
                    <option value="{{ $room->id }}">{{ $room->type }} / {{ $room->name }}</option>
                @endforeach
            </select>
            <button
                wire:click="generateDungeonRooms"
                class="mt-2 bg-blue-500 text-white py-1 px-4 rounded hover:bg-blue-600"
            >
                Generate
            </button>
        </div>

    </div>

    <!-- Second Column (Expands to Fill Remaining Space) -->
    <div class="flex-1 flex flex-col bg-gray-100 p-4 overflow-y-auto" id="testHolder">
        {!! $testHolderContent !!}
    </div>
</div>
