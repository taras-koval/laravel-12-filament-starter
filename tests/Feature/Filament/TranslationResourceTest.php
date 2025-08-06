<?php

namespace Tests\Feature\Filament;

use App\Enums\UserRoleEnum;
use App\Filament\Resources\TranslationResource;
use App\Filament\Resources\TranslationResource\Pages\EditTranslation;
use App\Filament\Resources\TranslationResource\Pages\ListTranslations;
use App\Models\Translation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class TranslationResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->admin->syncRoles([UserRoleEnum::ADMINISTRATOR]);
    }

    public function test_administrator_can_view_translations_list(): void
    {
        $records = Translation::factory()->count(3)->create();

        $this->actingAs($this->admin)->get(TranslationResource::getUrl())->assertSuccessful();

        Livewire::actingAs($this->admin)
            ->test(ListTranslations::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords($records);
    }

    public function test_administrator_can_edit_translation(): void
    {
        $translation = Translation::factory()->create([
            'group' => 'general',
            'key' => 'welcome_message',
            'values' => [
                'en' => 'Welcome',
                'pl' => 'Witamy',
                'uk' => 'Ласкаво просимо',
            ],
        ]);

        $updatedValues = $translation->values;
        $updatedValues['en'] = 'Welcome to our app';

        Livewire::actingAs($this->admin)
            ->test(EditTranslation::class, ['record' => $translation->id])
            ->fillForm([
                'values' => $updatedValues,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $translation->refresh();
        $this->assertEquals('Welcome to our app', $translation->values['en']);
        $this->assertEquals('Witamy', $translation->values['pl']);
        $this->assertEquals('Ласкаво просимо', $translation->values['uk']);
    }

    public function test_administrator_can_delete_translation(): void
    {
        $translation = Translation::factory()->create([
            'group' => 'general',
            'key' => 'obsolete_key',
        ]);

        Livewire::actingAs($this->admin)
            ->test(EditTranslation::class, ['record' => $translation->id])
            ->callAction('delete')
            ->assertSuccessful();

        $this->assertDatabaseMissing('translations', ['id' => $translation->id]);
    }

    public function test_import_command_imports_json(): void
    {
        // Arrange: set locales and fake File facade interactions for JSON only
        config(['translation-manager.available_locales' => [
            ['code' => 'en', 'name' => 'English', 'flag' => 'gb'],
        ]]);

        $jsonPath = base_path('lang/en.json');

        File::shouldReceive('exists')->with($jsonPath)->andReturn(true);
        File::shouldReceive('get')
            ->with($jsonPath)
            ->andReturn(json_encode([
                'welcome' => 'Welcome',
                'app.name' => 'Demo',
            ], JSON_THROW_ON_ERROR));

        // No PHP files directory
        File::shouldReceive('isDirectory')->with(base_path('lang/en'))->andReturn(false);

        // Act
        $this->artisan('translations:import')->assertExitCode(0);

        // Assert
        $welcome = Translation::where('group', 'general')->where('key', 'welcome')->first();
        $this->assertNotNull($welcome);
        $this->assertEquals('Welcome', $welcome->values['en'] ?? null);

        $appName = Translation::where('group', 'general')->where('key', 'app.name')->first();
        $this->assertNotNull($appName);
        $this->assertEquals('Demo', $appName->values['en'] ?? null);
    }

    public function test_publish_command_writes_expected_files_via_file_facade(): void
    {
        // Arrange: one general (JSON) and one grouped (PHP) translation
        config(['translation-manager.available_locales' => [
            ['code' => 'en', 'name' => 'English', 'flag' => 'gb'],
        ]]);

        Translation::create([
            'group' => 'general',
            'key' => 'welcome',
            'values' => ['en' => 'Welcome'],
        ]);

        Translation::create([
            'group' => 'auth',
            'key' => 'errors.required',
            'values' => ['en' => 'Required'],
        ]);

        $langDir = base_path('lang/en');
        $jsonPath = base_path('lang/en.json');
        $phpPath = base_path('lang/en/auth.php');

        $written = [];

        File::shouldReceive('isDirectory')
            ->with($langDir)
            ->andReturn(true);

        File::shouldReceive('put')
            ->andReturnUsing(function ($path, $content) use (&$written) {
                $written[$path] = $content;
                return true;
            });

        // Act
        $this->artisan('translations:publish')->assertExitCode(0);

        // Assert JSON file content
        $this->assertArrayHasKey($jsonPath, $written);
        $json = json_decode($written[$jsonPath], true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals('Welcome', $json['welcome'] ?? null);

        // Assert PHP file content structure
        $this->assertArrayHasKey($phpPath, $written);
        $this->assertStringContainsString('return [', $written[$phpPath]);
        $this->assertStringContainsString("'errors' =>", $written[$phpPath]);
        $this->assertStringContainsString("'required' => 'Required'", $written[$phpPath]);
    }

    public function test_csv_export_action_streams_response_successfully(): void
    {
        Translation::factory()->create([
            'group' => 'general',
            'key' => 'hello',
            'values' => ['en' => 'Hello', 'pl' => 'Cześć', 'uk' => 'Привіт'],
        ]);

        Livewire::actingAs($this->admin)
            ->test(ListTranslations::class)
            ->callAction('exportCsv')
            ->assertSuccessful();
    }

    public function test_csv_import_action_works_with_fake_storage(): void
    {
        Storage::fake('local');

        $csv = implode("\n", [
            'group,key,en,pl,uk',
            'general,welcome,Welcome,,',
            'auth,login.title,Login,,,',
            ',flat.key,Flat Value,,',
        ]);

        $path = 'tmp/translation-imports/test.csv';
        Storage::disk('local')->put($path, $csv);

        Livewire::actingAs($this->admin)
            ->test(ListTranslations::class)
            ->call('importCsv', [
                'file' => $path,
                'overwrite' => true,
            ])
            ->assertSuccessful();

        // The file is deleted after processing
        $this->assertTrue(Storage::disk('local')->missing($path));

        // Assert imported records
        $welcome = Translation::where('group', 'general')->where('key', 'welcome')->first();
        $this->assertEquals('Welcome', $welcome->values['en'] ?? null);

        $loginTitle = Translation::where('group', 'auth')->where('key', 'login.title')->first();
        $this->assertEquals('Login', $loginTitle->values['en'] ?? null);

        $flatKey = Translation::where('group', 'general')->where('key', 'flat.key')->first();
        $this->assertEquals('Flat Value', $flatKey->values['en'] ?? null);
    }
}
