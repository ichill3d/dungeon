<?php

namespace Database\Seeders;

use App\Models\DungeonSetting;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DungeonSetting::insert([
            [
                'slug' => 'fantasy',
                'name' => 'Fantasy',
                'inspiration' => 'Fantasy Dungeon involve exploring dark, mysterious underground labyrinths filled with traps, treasures, and dangerous creatures. Players navigate through ancient ruins, forgotten temples, or cursed caverns, seeking valuable loot and battling formidable enemies. Dungeons are often home to powerful monsters, hidden secrets, and magical artifacts, requiring strategy, combat, and problem-solving. The atmosphere is typically eerie and tense, with adventurers facing both physical and supernatural challenges, all while uncovering the rich lore of the fantasy world.',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'slug' => 'scifi',
                'name' => 'Sci-Fi',
                'inspiration' => 'Sci-Fi Dungeon take place in futuristic, high-tech environments such as abandoned space stations, alien-infested research facilities, or derelict starships. Players explore vast, mechanical labyrinths filled with advanced technology, hostile AI, extraterrestrial creatures, and mysterious experiments. The setting blends elements of exploration, combat, and problem-solving, often with themes of human survival, artificial intelligence, and intergalactic discovery. Players face both physical and cybernetic threats, uncovering secrets of long-lost civilizations or dealing with the consequences of scientific hubris in a gritty, high-stakes sci-fi world.',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'slug' => 'postapocalyptic',
                'name' => 'Post Apocalyptic',
                'inspiration' => 'Post-Apocalyptic Dungeon are set in a world ravaged by disaster, where players explore the remnants of civilization, navigating through ruined cities, underground bunkers, and decaying shelters. The environment is harsh and unforgiving, filled with mutated creatures, scarce resources, and remnants of lost technology. Players must scavenge for supplies, face dangerous factions, and survive environmental hazards while uncovering the secrets of the world’s downfall. Themes of survival, rebuilding, and moral choices are central, as players try to make their way through a desolate, yet hopeful, post-apocalyptic landscape.',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'slug' => 'modern',
                'name' => 'Modern',
                'inspiration' => 'Modern Dungeon RPGs are set in contemporary, urban environments where players explore hidden, underground locations like abandoned buildings, secret labs, or forgotten subway tunnels. The world is grounded in reality, but with elements of mystery, supernatural occurrences, or high-tech intrigue. Players might encounter secret organizations, criminal syndicates, or paranormal forces as they uncover dark secrets lurking beneath the surface of everyday life. Themes of urban exploration, conspiracy, and survival in a modern setting are central, with a mix of investigation, combat, and problem-solving as players navigate dangerous, concealed spaces.',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}
