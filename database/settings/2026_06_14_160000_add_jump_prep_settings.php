<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('jump_prep.arrival_info', 'Please arrive 30 minutes before your slot time so we can complete your paperwork and brief without rushing. Allow a few hours on site — jumps can be delayed by weather and we go in slot order.');
        $this->migrator->add('jump_prep.what_to_bring', 'Wear comfortable clothes and trainers (no sandals). Bring a jumper for the colder air at altitude, any required medical paperwork, and a form of ID. Leave valuables at home or with a friend.');
        $this->migrator->add('jump_prep.what_to_expect', "You'll meet your instructor, get a full safety brief, then gear up. After the climb to altitude it's a 30-45 second freefall and a 5-minute canopy ride back down. Photos and video can be added on the day.");
    }
};
