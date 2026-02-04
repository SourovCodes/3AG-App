<?php

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Filament\Resources\Users\UserResource;
use App\Models\License;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['email' => 'sourovcodes@gmail.com']);
    $this->actingAs($this->admin);
});

describe('List Users Page', function () {
    it('can render the index page', function () {
        $this->get(UserResource::getUrl('index'))
            ->assertSuccessful();
    });

    it('can list users', function () {
        $users = User::factory()->count(3)->create();

        Livewire::test(ListUsers::class)
            ->assertCanSeeTableRecords($users);
    });

    it('can search users by name', function () {
        $userToFind = User::factory()->create(['name' => 'John Doe']);
        $otherUser = User::factory()->create(['name' => 'Jane Smith']);

        Livewire::test(ListUsers::class)
            ->searchTable('John')
            ->assertCanSeeTableRecords([$userToFind])
            ->assertCanNotSeeTableRecords([$otherUser]);
    });

    it('can search users by email', function () {
        $userToFind = User::factory()->create(['email' => 'findme@example.com']);
        $otherUser = User::factory()->create(['email' => 'other@example.com']);

        Livewire::test(ListUsers::class)
            ->searchTable('findme@example.com')
            ->assertCanSeeTableRecords([$userToFind])
            ->assertCanNotSeeTableRecords([$otherUser]);
    });

    it('can sort users by created date', function () {
        $oldUser = User::factory()->create(['created_at' => now()->subDays(10)]);
        $newUser = User::factory()->create(['created_at' => now()]);

        Livewire::test(ListUsers::class)
            ->sortTable('created_at', 'desc')
            ->assertCanSeeTableRecords([$newUser, $oldUser], inOrder: true);
    });

    it('can filter users by verified status', function () {
        $verifiedUser = User::factory()->create();
        $unverifiedUser = User::factory()->unverified()->create();

        Livewire::test(ListUsers::class)
            ->filterTable('email_verified_at', true)
            ->assertCanSeeTableRecords([$verifiedUser])
            ->assertCanNotSeeTableRecords([$unverifiedUser]);
    });
});

describe('Create User Page', function () {
    it('can render the create page', function () {
        $this->get(UserResource::getUrl('create'))
            ->assertSuccessful();
    });

    it('can create a user', function () {
        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'New User',
                'email' => 'newuser@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(User::class, [
            'name' => 'New User',
            'email' => 'newuser@example.com',
        ]);
    });

    it('validates required fields', function () {
        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => '',
                'email' => '',
                'password' => '',
            ])
            ->call('create')
            ->assertHasFormErrors(['name', 'email', 'password']);
    });

    it('validates email format', function () {
        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Test User',
                'email' => 'invalid-email',
                'password' => 'password123',
            ])
            ->call('create')
            ->assertHasFormErrors(['email']);
    });

    it('validates unique email', function () {
        User::factory()->create(['email' => 'existing@example.com']);

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Test User',
                'email' => 'existing@example.com',
                'password' => 'password123',
            ])
            ->call('create')
            ->assertHasFormErrors(['email']);
    });
});

describe('View User Page', function () {
    it('can render the view page', function () {
        $user = User::factory()->create();

        $this->get(UserResource::getUrl('view', ['record' => $user]))
            ->assertSuccessful();
    });

    it('displays user information correctly', function () {
        $user = User::factory()->create([
            'name' => 'View Test User',
            'email' => 'viewtest@example.com',
        ]);

        Livewire::test(ViewUser::class, ['record' => $user->getRouteKey()])
            ->assertSee('View Test User')
            ->assertSee('viewtest@example.com');
    });

    it('shows user licenses in relation manager', function () {
        $user = User::factory()->create();
        $licenses = License::factory()->count(2)->create(['user_id' => $user->id]);

        Livewire::test(ViewUser::class, ['record' => $user->getRouteKey()])
            ->assertSuccessful();
    });
});

describe('Edit User Page', function () {
    it('can render the edit page', function () {
        $user = User::factory()->create();

        $this->get(UserResource::getUrl('edit', ['record' => $user]))
            ->assertSuccessful();
    });

    it('can retrieve user data', function () {
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
        ]);

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->assertFormSet([
                'name' => 'Original Name',
                'email' => 'original@example.com',
            ]);
    });

    it('can update a user', function () {
        $user = User::factory()->create();

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->fillForm([
                'name' => 'Updated Name',
                'email' => 'updated@example.com',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();

        expect($user->name)->toBe('Updated Name')
            ->and($user->email)->toBe('updated@example.com');
    });

    it('can delete a user', function () {
        $user = User::factory()->create();

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->callAction('delete');

        $this->assertModelMissing($user);
    });
});
