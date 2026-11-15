<?php

use App\Domain\Access\Enums\RoleName;
use App\Domain\Competition\Models\AgeGroupScheme;
use App\Domain\Competition\Models\CancellationRule;
use App\Domain\Competition\Models\Edition;
use App\Domain\Competition\Models\PriceTier;
use App\Domain\Competition\Models\Race;
use App\Domain\Competition\Models\RaceOption;
use App\Models\User;
use Database\Seeders\RoleSeeder;

/**
 * W-03, W-04, W-05: prices, options and cancellation rules follow the
 * permissions of their edition; age group schemes are kept by the super
 * admin only.
 */
beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->ownEdition = Edition::factory()->create(['year' => 2027, 'slug' => '2027']);
    $this->otherEdition = Edition::factory()->create(['year' => 2026, 'slug' => '2026']);

    $this->ownTier = PriceTier::factory()->for(Race::factory()->for($this->ownEdition))->create();
    $this->otherTier = PriceTier::factory()->for(Race::factory()->for($this->otherEdition))->create();

    $this->orga = User::factory()->create();
    $this->orga->assignRoleFor(RoleName::Orga, $this->ownEdition);
});

it('lets the orga team manage prices of its own edition only', function () {
    expect($this->orga->can('create', PriceTier::class))->toBeTrue()
        ->and($this->orga->can('update', $this->ownTier))->toBeTrue()
        ->and($this->orga->can('delete', $this->ownTier))->toBeTrue()
        ->and($this->orga->can('view', $this->otherTier))->toBeFalse()
        ->and($this->orga->can('update', $this->otherTier))->toBeFalse();
});

it('binds options and cancellation rules to the edition', function () {
    $ownOption = RaceOption::factory()->for($this->ownEdition)->create();
    $otherOption = RaceOption::factory()->for($this->otherEdition)->create();
    $ownRule = CancellationRule::factory()->for($this->ownEdition)->create();
    $otherRule = CancellationRule::factory()->for($this->otherEdition)->create();

    expect($this->orga->can('update', $ownOption))->toBeTrue()
        ->and($this->orga->can('update', $otherOption))->toBeFalse()
        ->and($this->orga->can('update', $ownRule))->toBeTrue()
        ->and($this->orga->can('update', $otherRule))->toBeFalse();
});

it('keeps prices of a closed edition read only', function () {
    $this->ownEdition->update(['status' => 'archived']);

    $tier = $this->ownTier->fresh();
    $rule = CancellationRule::factory()->for($this->ownEdition)->create();

    expect($this->orga->can('view', $tier))->toBeTrue()
        ->and($this->orga->can('update', $tier))->toBeFalse()
        ->and($this->orga->can('delete', $tier))->toBeFalse()
        ->and($this->orga->can('update', $rule))->toBeFalse();
});

it('does not let the editorial role touch prices', function () {
    $editor = User::factory()->create();
    $editor->assignRoleFor(RoleName::Redaktion, $this->ownEdition);

    expect($editor->can('view', $this->ownTier))->toBeFalse()
        ->and($editor->can('create', PriceTier::class))->toBeFalse()
        ->and($editor->can('create', CancellationRule::class))->toBeFalse();
});

it('lets only the super admin maintain age group schemes', function () {
    $scheme = AgeGroupScheme::factory()->create();
    $admin = User::factory()->create();
    $admin->assignRoleFor(RoleName::SuperAdmin);

    expect($this->orga->can('view', $scheme))->toBeTrue()
        ->and($this->orga->can('create', AgeGroupScheme::class))->toBeFalse()
        ->and($this->orga->can('update', $scheme))->toBeFalse()
        ->and($admin->can('create', AgeGroupScheme::class))->toBeTrue()
        ->and($admin->can('update', $scheme))->toBeTrue();
});
