<div class="bg-white p-6 rounded-lg shadow-md max-w-md mx-auto">
    <h2 class="text-2xl font-bold mb-4">Create a New Dungeon</h2>

    @if (session()->has('success'))
        <div class="mb-4 p-2 bg-green-100 text-green-700 rounded">
            {{ session('success') }}
        </div>
    @endif

    <form wire:submit.prevent="save" class="space-y-4">
        <!-- Dungeon Name -->
        <div>
            <label class="block font-medium text-gray-700">Dungeon Name</label>
            <div class="text-sm text-gray-500">
                Leave empty to generate automatically
            </div>
            <input type="text" wire:model="name"
                   class="w-full mt-1 p-2 border rounded-md"
                   placeholder="Enter dungeon name">
            @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <!-- Dungeon Setting -->
        <div>
            <label class="block font-medium text-gray-700">Setting</label>
            <select wire:model.live="dungeonSettingId"
                    class="w-full mt-1 p-2 border rounded-md">
                @foreach ($settings as $setting)
                    <option value="{{$setting->id}}">{{ $setting->name }}</option>
                @endforeach
            </select>
            @error('dungeonSettingId') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <!-- Dungeon Type (Only show if a setting is selected) -->

        <div>
            <label class="block font-medium text-gray-700">Dungeon Type</label>
            <select wire:model="selectedDungeonTypeId"
                    class="w-full mt-1 p-2 border rounded-md">
                <option>Random</option>
                @foreach ($types as $type)
                    <option value="{{$type->id}}">{{ $type->name }}</option>
                @endforeach
            </select>
            @error('selectedDungeonTypeId') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>


        <!-- Dungeon Description -->
        <div>
            <label class="block font-medium text-gray-700">Description</label>
            <div class="text-sm text-gray-500">
                Leave empty to generate automatically
            </div>
            <textarea wire:model="description"
                      class="w-full mt-1 p-2 border rounded-md"
                      placeholder="Enter dungeon description"></textarea>
            @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <!-- Dungeon Size -->
        <div>
            <label class="block font-medium text-gray-700">Size</label>
            <select wire:model="size"
                    class="w-full mt-1 p-2 border rounded-md">
                <option value="tiny">Tiny</option>
                <option value="small">Small</option>
                <option value="medium">Medium</option>
                <option value="large">Large</option>
                <option value="enormous">Enormous</option>
            </select>
            @error('size') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <!-- Submit Button -->
        <button type="submit"
                class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">
            Generate Dungeon
        </button>
    </form>
</div>
