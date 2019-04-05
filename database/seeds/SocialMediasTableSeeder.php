<?php

use Illuminate\Database\Seeder;
use App\Model\SocialMedia;
class SocialMediasTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        SocialMedia::firstOrCreate(['social' => 'facebook', 'is_active' => '1']);
        SocialMedia::firstOrCreate(['social' => 'twitter', 'is_active' => '1']);
        SocialMedia::firstOrCreate(['social' => 'linkedin', 'is_active' => '1']);
        SocialMedia::firstOrCreate(['social' => 'google-plus', 'is_active' => '1']);
        SocialMedia::firstOrCreate(['social' => 'instagram', 'is_active' => '1']);
        SocialMedia::firstOrCreate(['social' => 'steemit', 'is_active' => '1']);
        SocialMedia::firstOrCreate(['social' => 'telegram', 'is_active' => '1']);


        // DB::table('social_medias')->insert($data);
    }
}
