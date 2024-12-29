<x-app-layout>
    <div class="p-6 space-y-4"
    x-data="{scaleDungeon: 1}"
    >
        <h1 class="text-2xl font-bold">{{ $dungeon->name }}</h1>
        <p class="text-gray-600">{{ $dungeon->description }}</p>

        <!-- Zoom Controls -->
        <div class="flex gap-2 items-center justify-center mb-2">
            <a href="#"
               @click.prevent="scaleDungeon = Math.max(0.5, scaleDungeon - 0.5)"
               class="bg-gray-700 text-white px-4 py-2 rounded hover:bg-gray-600"
            >Zoom Out</a>
            <a href="#"
               @click.prevent="scaleDungeon = Math.min(6, scaleDungeon + 0.5)"
               class="bg-gray-700 text-white px-4 py-2 rounded hover:bg-gray-600"
            >Zoom In</a>
            <span class="text-sm text-gray-400">Zoom: <span x-text="(scaleDungeon * 100).toFixed(0)"></span>%</span>
        </div>

        <div class="flex justify-center overflow-auto border border-gray-500 rounded-lg bg-black p-2">
            <div
                class="grid gap-0"
                x-data="dungeonExplorer('{{ $startX }}', '{{ $startY }}')"
                x-bind:style="'scale: ' + scaleDungeon + '; ' + 'grid-template-columns: repeat({{ count(json_decode($dungeon->grid, true)[0]) }}, 1fr); width: fit-content;'"

            >
                @foreach (json_decode($dungeon->grid, true) as $y => $row)
                    @foreach ($row as $x => $cell)
                        @php $key = "{$x}-{$y}"; @endphp
                        <div
                            class="w-8 h-8 border border-black relative"
                            data-key="{{ $key }}"
                            data-type="{{ $cell }}"
                            x-bind:class="{
                                'bg-gray-700': '{{ $cell }}' === 'W',   /* Wall */
                                'bg-gray-300': '{{ $cell }}' === 'R',   /* Room */
                                'bg-yellow-400': '{{ $cell }}' === 'C', /* Corridor */
                                'bg-yellow-600 text-white flex items-center justify-center cursor-pointer': '{{ $cell }}' === 'D', /* Door */
                                'bg-white text-black flex items-center justify-center': '{{ $cell }}' === 'E', /* Exit */
                                'bg-red-600 text-white flex items-center justify-center': '{{ $cell }}' === 'S', /* Start */
                                'bg-purple-700 text-white flex items-center justify-center': '{{ $cell }}' === 'B' /* Boss */
                            }"
                            @click="if ('{{ $cell }}' === 'D') explore('{{ $x }}', '{{ $y }}')"
                        >
                            @if($cell === 'D') D @endif
                            @if($cell === 'E') E @endif
                            @if($cell === 'S') S @endif
                            @if($cell === 'B') B @endif

                            {{-- Masking Layer --}}
{{--                            <div--}}
{{--                                class="absolute inset-0 bg-black"--}}
{{--                                x-show="!explored.includes('{{ $key }}')"--}}
{{--                                style="opacity: 1;"--}}
{{--                            ></div>--}}
                        </div>
                    @endforeach
                @endforeach
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('dungeonExplorer', (startX, startY) => ({
                explored: [],

                init() {
                    this.revealStart(parseInt(startX), parseInt(startY));
                },

                // Reveal the starting room/corridor
                revealStart(startX, startY) {
                    const queue = [[startX, startY]];
                    const visited = new Set();

                    while (queue.length > 0) {
                        const [currentX, currentY] = queue.shift();
                        const key = `${currentX}-${currentY}`;

                        if (visited.has(key) || this.explored.includes(key)) {
                            continue;
                        }

                        visited.add(key);
                        this.explored.push(key);

                        const directions = [
                            [0, -1], // Up
                            [0, 1],  // Down
                            [-1, 0], // Left
                            [1, 0]   // Right
                        ];

                        directions.forEach(([dx, dy]) => {
                            const newX = currentX + dx;
                            const newY = currentY + dy;
                            const neighborKey = `${newX}-${newY}`;
                            const tileElement = document.querySelector(`[data-key="${neighborKey}"]`);
                            const tileType = tileElement?.getAttribute('data-type');

                            if (!visited.has(neighborKey) && !this.explored.includes(neighborKey)) {
                                if (['R', 'C', 'S'].includes(tileType)) {
                                    queue.push([newX, newY]);
                                } else if (tileType === 'D') {
                                    this.explored.push(neighborKey);
                                }
                            }
                        });
                    }
                },

                // Explore connected rooms/corridors from a door
                explore(x, y) {
                    const queue = [[parseInt(x), parseInt(y)]];
                    const visited = new Set();

                    while (queue.length > 0) {
                        const [currentX, currentY] = queue.shift();
                        const key = `${currentX}-${currentY}`;

                        if (visited.has(key)) {
                            continue;
                        }

                        visited.add(key);
                        this.explored.push(key);

                        const directions = [
                            [0, -1], // Up
                            [0, 1],  // Down
                            [-1, 0], // Left
                            [1, 0]   // Right
                        ];

                        directions.forEach(([dx, dy]) => {
                            const newX = currentX + dx;
                            const newY = currentY + dy;
                            const neighborKey = `${newX}-${newY}`;
                            const tileElement = document.querySelector(`[data-key="${neighborKey}"]`);
                            const tileType = tileElement?.getAttribute('data-type');

                            if (!visited.has(neighborKey) && !this.explored.includes(neighborKey)) {
                                if (['R', 'C', 'B'].includes(tileType)) {
                                    queue.push([newX, newY]);
                                } else if (tileType === 'D') {
                                    this.explored.push(neighborKey); // Reveal adjacent doors
                                }
                            }
                        });
                    }

                    // Ensure Alpine detects changes
                    this.$nextTick(() => {
                        console.log('Explored:', this.explored);
                    });
                }
            }));
        });
    </script>
</x-app-layout>
