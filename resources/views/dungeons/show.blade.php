<div class="grid grid-cols-50 gap-1">
    @foreach ($dungeon->metadata['grid'] as $row)
        @foreach ($row as $cell)
            <div class="w-2 h-2 border
                @if ($cell === 'R') bg-green-500
                @elseif ($cell === 'C') bg-gray-400
                @else bg-gray-800 @endif">
            </div>
        @endforeach
    @endforeach
</div>
