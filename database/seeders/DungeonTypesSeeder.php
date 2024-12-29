<?php

namespace Database\Seeders;

use App\Models\DungeonType;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DungeonTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DungeonType::insert([
            [
                'dungeon_setting_id' => 1,
                'slug' => 'ancient-ruins',
                'name' => 'Ancient Ruins',
                'description' => 'Exploration of history, uncovering forgotten lore, navigating decaying structures.',
                'inspiration' => 'Long-forgotten temples or castles, overgrown with vegetation and weathered by time. These dungeons are filled with crumbling stonework, traps, and remnants of past civilizations. Adventurers may find relics of a powerful, ancient society, guarded by lingering magical defenses or long-dormant creatures.',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'dungeon_setting_id' => 1,
                'slug' => 'caverns',
                'name' => 'Caverns',
                'description' => 'Stealth, avoiding traps, battling small, agile enemies, and uncovering hidden treasures.',
                'inspiration' => 'A network of subterranean tunnels and caves, home to mischievous goblins, kobolds, and other small and large creatures. The caverns are littered with traps, hoards of stolen treasures, and dangerous creatures lurking in the shadows. These dungeons are dark, dank, and full of peril.',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'dungeon_setting_id' => 1,
                'slug' => 'crypt',
                'name' => 'Crypt',
                'description' => 'Horror, combat with undead, solving puzzles tied to the afterlife, discovering forbidden necromantic magic.',
                'inspiration' => ' A dark and eerie burial ground, home to restless spirits and reanimated corpses. The crypt is a maze of tombs, sarcophagi, and crypts, where players may encounter ghostly apparitions, undead guardians, and cursed relics. The atmosphere is heavy with an oppressive sense of death and despair.',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
[
                'dungeon_setting_id' => 2,
                'slug' => 'space-station',
                'name' => 'Abandoned Space Station',
                'description' => 'Exploration of lost technology, survival in a hazardous environment, facing off against rogue robots and alien creatures.',
                'inspiration' => ' An enormous, derelict space station orbiting a distant planet, abandoned for decades. The station is filled with malfunctioning security systems, decaying technology, and dangerous alien lifeforms that have taken over the environment. Players must navigate through dark, narrow corridors, avoiding hazards like explosive decompression and rogue AI-controlled defenses.',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'dungeon_setting_id' => 2,
                'slug' => 'alien-world-caverns',
                'name' => 'Alien World Caverns',
                'description' => 'Exploration of a truly alien environment, encountering unknown creatures and hazardous terrain, uncovering the secrets of an ancient alien civilization, and surviving a constantly shifting underground world. ',
                'inspiration' => '  The Alien World Caverns are a vast, underground network of caves and tunnels on a distant alien planet. These caverns are home to strange and otherworldly lifeforms, many of which are completely unknown to humanity. Players will encounter bizarre, sentient plants, hostile fauna, and ancient alien ruins that hint at the civilization that once thrived in the depths. The caverns are full of hidden dangers, such as poisonous gases, cave-ins, and electromagnetic anomalies that can disrupt equipment and communication systems. Navigating the caverns requires both ingenuity and caution, as the environment itself is a threat.',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'dungeon_setting_id' => 3,
                'slug' => 'nuclear-fallout-shelter',
                'name' => 'Nuclear Fallout Shelter',
                'description' => 'Survival in harsh, toxic conditions, uncovering the truth behind the shelter’s collapse, dealing with mutated enemies, and uncovering long-hidden government or corporate secrets.',
                'inspiration' => 'A long-forgotten government or corporate fallout shelter buried deep underground, designed to protect the elite during the apocalypse. Now, its a shadow of its former self, filled with decaying supplies, malfunctioning security systems, and strange mutations caused by prolonged exposure to radiation. The shelter’s halls are lined with cryptic warning signs, and some areas are sealed off, harboring dangerous bioengineering experiments or systems that have gone completely rogue. Players must navigate through these sections, surviving harsh environmental conditions, battling mutated creatures, and unlocking secrets that could help them survive or shed light on the events that led to the downfall of civilization.',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]
        ]);
    }
}
