<?php

namespace Tests\Support;

use App\Models\Complex;
use App\Models\Group;
use App\Models\User;
use App\Models\UserRole;

class BuildingContext
{
    public Complex $complex;

    public Group $group;

    public User $manager;

    public UserRole $managerRole;

    public function __construct()
    {
        $this->managerRole = UserRole::factory()->manager()->create();
        $this->complex = $this->managerRole->complex;
        $this->manager = $this->managerRole->user;
        $this->group = Group::factory()->create(['complex_id' => $this->complex->id]);
    }

    public function resident(): UserRole
    {
        return UserRole::factory()->create(['complex_id' => $this->complex->id]);
    }
}
