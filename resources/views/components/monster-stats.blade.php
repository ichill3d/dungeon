<div class=" p-2 rounded-xl bg-green-200 mb-4">
    <div class="flex flex-row items-center mt-2 gap-2">
        @if ($type == 'boss')
        <div class="font-bold inline-block bg-amber-700 rounded-xl text-white px-2 py-1">BOSS</div>
        @endif

        <div class="font-semibold"> {{ $monster->name }}</div>
    </div>

{{--    /*--}}
{{--    "monster": { "amount": "4", "name": "Skeletal Warrior", "description": "Animated bones bound by dark magic, these warriors are relentless in their pursuit of intruders, wielding rusty swords.", "stats": { "Attributes": { "Agility": "4", "Smarts": "1", "Spirit": "2", "Strength": "5", "Vigor": "4" }, "Skills": { "Fighting": "5", "Shooting": "0", "Notice": "2" }, "Pace": "6", "Parry": "5", "Toughness": "6", "Gear": { "item": { "name": "Rusty Sword", "description": "An old and corroded sword, still deadly in the hands of a skeleton." } }, "SpecialAbilities": { "ability": { "name": "Undying", "description": "Skeletal Warriors can reassemble if not completely destroyed." } }*/--}}
    <div>{{ $monster->description }}</div>
    <div class="flex flex-row items-center gap-2 w-full mt-2 justify-between border-t-2 border-b-2 border-gray-500 pb-2">
        <div class="flex flex-col items-center">
            <div class="font-semibold">Agility</div>
            <div class="text-gray-500">{{ getNextDieType($monster->stats->Attributes->Agility) }}d</div>
        </div>
        <div class="flex flex-col  items-center">
            <div class="font-semibold">Smarts</div>
            <div class="text-gray-500">{{ getNextDieType($monster->stats->Attributes->Smarts) }}d</div>
        </div>
        <div class="flex flex-col items-center">
            <div class="font-semibold">Spirit</div>
            <div class="text-gray-500">{{ getNextDieType($monster->stats->Attributes->Spirit) }}d</div>
        </div>
        <div class="flex flex-col items-center">
            <div class="font-semibold">Strength</div>
            <div class="text-gray-500">{{ getNextDieType($monster->stats->Attributes->Strength) }}d</div>
        </div>
        <div class="flex flex-col items-center">
            <div class="font-semibold">Vigor</div>
            <div class="text-gray-500">{{ getNextDieType($monster->stats->Attributes->Vigor) }}d</div>
        </div>

    </div>
    <div class="flex flex-row items-center w-full justify-between mt-2 border-b-2 border-gray-500 pb-2">
        <div><span class="font-semibold">Pace:</span> {{ $monster->stats->Pace }}</div>
        <div><span class="font-semibold">Parry:</span> {{ $monster->stats->Parry }}</div>
        <div><span class="font-semibold">Toughness:</span> {{ $monster->stats->Toughness }}</div>
    </div>
    <div class="w-full text-center text-sm border-b border-gray-400 font-semibold">SKILLS</div>
    <div class="grid grid-cols-2 w-full justify-between mt-2 border-b-2 border-gray-500 pb-2">

        @foreach ($monster->stats->Skills as $skill => $value)
            <div><span class="font-semibold">{{ $skill }}:</span> {{ getNextDieType($value) }}d</div>
            @endforeach
    </div>
    <div class="w-full text-center text-sm border-b border-gray-400 font-semibold">GEAR</div>
    <div class="flex flex-col items-center w-full justify-between mt-2 border-b-2 border-gray-500 pb-2">
        @foreach ($monster->stats->Gear as $item)
            <div>
                <span class="font-semibold">{{ $item->name }}</span>
                <span>{{ $item->description_and_damage }}</span>
            </div>
            @endforeach
    </div>
    @if(!empty($monster->stats->SpecialAbilities))
    <div class="w-full text-center text-sm border-b border-gray-400 font-semibold">SPECIAL ABILITIES</div>
    <div class="flex flex-col items-center w-full justify-between mt-2 border-b-2 border-gray-500 pb-2">
        @foreach ($monster->stats->SpecialAbilities as $ability)
            <div>
                <span class="font-semibold">{{ $ability->name }}</span>
                <span>{{ $ability->description }}</span>
            </div>
            @endforeach
    </div>
   @endif
</div>
