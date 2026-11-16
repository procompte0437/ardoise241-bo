<?php

namespace Tests\Feature;

use App\Models\Food;
use App\Models\Person;
use App\Models\Setting;
use App\Models\User;
use App\Services\LessyStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LessyApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_state_seeds_the_catalogue(): void
    {
        $this->getJson('/api/state')
            ->assertOk()
            ->assertJsonPath('locked', false)
            ->assertJsonCount(LessyStore::MAX_NAMES, 'names')
            ->assertJsonCount(LessyStore::MAX_FOODS, 'foods')
            ->assertJsonPath('names.0.name', 'Nom 1');

        $this->getJson('/api/state')->assertJsonCount(LessyStore::MAX_NAMES, 'names');
    }

    public function test_session_payment_and_erase(): void
    {
        $this->install();
        $person = Person::query()->create(['name' => 'Aïcha', 'debt' => 0, 'position' => 1]);
        $food = Food::query()->create(['name' => 'Poulet', 'price' => 2500, 'position' => 1]);
        Food::query()->create(['name' => 'Offert', 'price' => 0, 'position' => 2]);

        $this->postJson('/api/people/'.$person->id.'/sessions', ['counts' => []])
            ->assertStatus(422);

        $this->postJson('/api/people/'.$person->id.'/sessions', [
            'counts' => [$food->id => 2],
        ])->assertOk()->assertJsonPath('amount', 5000);

        $this->assertSame(5000, $person->fresh()->debt);

        $this->postJson('/api/people/'.$person->id.'/payments', ['amount' => 7000])
            ->assertStatus(403);

        $token = $this->postJson('/api/admin/login', ['code' => '0110'])
            ->assertOk()
            ->json('token');

        $this->withHeader('X-Admin-Token', $token)
            ->postJson('/api/people/'.$person->id.'/payments', ['amount' => 7000])
            ->assertOk()
            ->assertJsonPath('debt', 0);

        $this->postJson('/api/people/'.$person->id.'/erase', ['code' => '0000'])
            ->assertStatus(422);

        $this->postJson('/api/people/'.$person->id.'/sessions', [
            'counts' => [(string) $food->id => 1],
        ])->assertOk();

        $this->postJson('/api/people/'.$person->id.'/erase', ['code' => '0110'])
            ->assertOk()
            ->assertJsonPath('debt', 0)
            ->assertJsonCount(0, 'history');
    }

    public function test_names_ignore_duplicates_and_code_must_be_four_digits(): void
    {
        $this->install();
        Person::query()->create(['name' => 'Marie', 'debt' => 0, 'position' => 1]);
        $token = app(LessyStore::class)->issueAdminToken();

        $this->withHeader('X-Admin-Token', $token)
            ->postJson('/api/names', ['text' => "Marie\nJean Paul\n"])
            ->assertOk()
            ->assertJsonPath('added', 1);

        $this->withHeader('X-Admin-Token', $token)
            ->postJson('/api/settings/code', ['code' => '12'])
            ->assertStatus(422);

        $this->withHeader('X-Admin-Token', $token)
            ->postJson('/api/settings/code', ['code' => '2468'])
            ->assertOk();

        $this->assertSame('2468', Setting::getValue('admin_code'));
    }

    public function test_trial_lock_and_photo_slot(): void
    {
        $this->install();
        Setting::putValue('install_date', (string) now()->subMonths(4)->getTimestampMs());

        $this->getJson('/api/state')->assertOk()->assertJsonPath('locked', true);
        $this->postJson('/api/unlock', ['code' => '000000'])->assertStatus(422);
        $this->postJson('/api/unlock', ['code' => LessyStore::LOCK_CODE])->assertOk();
        $this->getJson('/api/state')->assertOk()->assertJsonPath('locked', false);

        Storage::fake('public');
        $food = Food::query()->create(['name' => 'Image 1', 'price' => 0, 'position' => 1]);
        $token = app(LessyStore::class)->issueAdminToken();

        $this->withHeader('X-Admin-Token', $token)
            ->post('/api/foods/photos', ['photos' => [UploadedFile::fake()->image('plat.jpg')]])
            ->assertOk()
            ->assertJsonPath('added', 1);

        $this->assertNotNull($food->fresh()->image);
        Storage::disk('public')->assertExists($food->fresh()->image);
    }

    public function test_desk_lists_names(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/noms')
            ->assertOk()
            ->assertSee('Lessy aimé Rodrigue')
            ->assertSee('Nom 1');
    }

    private function install(): void
    {
        Setting::putValue('admin_code', LessyStore::DEFAULT_CODE);
        Setting::putValue('install_date', (string) now()->getTimestampMs());
        Setting::putValue('unlocked', '0');
    }
}
