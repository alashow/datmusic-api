<?php
/*
 * Copyright (c) 2022  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

use App\Models\Audio;
use Illuminate\Database\Migrations\Migration;

class MinervaPartitionDzArtistPaths extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Audio::deemix()->get()->each(function ($audio) {
            $audio->source_id = $this->partition_artist_folder_path($audio->source_id);
            $audio->save();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Audio::deemix()->get()->each(function ($audio) {
            $audio->source_id = $this->revert_partition_artist_folder_path($audio->source_id);
            $audio->save();
        });
    }

    /**
     * Given: Music/Eminem/The Eminem Show/The Eminem Show - Single.mp3
     * Return: Music/E/Eminem/The Eminem Show/The Eminem Show - Single.mp3.
     */
    private function partition_artist_folder_path($path): string
    {
        $parts = explode('/', $path);
        $root = $parts[0];
        $artist = $parts[1];
        $rest = array_slice($parts, 1);

        return $root.'/'.$artist[0].'/'.implode('/', $rest);
    }

    /**
     * Given: Music/E/Eminem/The Eminem Show/The Eminem Show - Single.mp3
     * Return: Music/Eminem/The Eminem Show/The Eminem Show - Single.mp3.
     */
    private function revert_partition_artist_folder_path($path): string
    {
        $parts = explode('/', $path);
        $root = $parts[0];
        $artist = $parts[2];
        $rest = array_slice($parts, 3);

        return $root.'/'.$artist.'/'.implode('/', $rest);
    }
}
