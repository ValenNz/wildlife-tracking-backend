<?php

namespace Tests\Feature;

use App\Models\Species;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WildlifeEncyclopediaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->seedAll();
    }

    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $this->app['auth']->forgetGuards();

        return parent::call($method, $uri, $parameters, $cookies, $files, $server, $content);
    }

    private function glb(array $json = ['asset' => ['version' => '2.0']], ?int $declaredLength = null): UploadedFile
    {
        $chunk = json_encode($json);
        $chunk .= str_repeat(' ', (4 - strlen($chunk) % 4) % 4);
        $length = 12 + 8 + strlen($chunk);
        $bytes = 'glTF'.pack('V', 2).pack('V', $declaredLength ?? $length).pack('V', strlen($chunk)).pack('V', 0x4E4F534A).$chunk;

        return UploadedFile::fake()->createWithContent('model.glb', $bytes);
    }

    public function test_public_catalog_shows_profiles_and_models_without_location_data(): void
    {
        $res = $this->getJson('/api/wildlife/species')->assertOk();
        $this->assertCount(8, $res->json('data'));

        $orangutan = collect($res->json('data'))->firstWhere('scientific_name', 'Pongo abelii');
        $this->assertTrue($orangutan['has_model']);
        $this->assertNotEmpty($orangutan['characteristics']);
        $this->assertArrayNotHasKey('model_path', $orangutan);
        $this->assertArrayNotHasKey('animals', $orangutan);

        $this->getJson("/api/wildlife/species/{$orangutan['species_id']}")->assertOk()
            ->assertJsonPath('data.taxon_family', 'Hominidae')
            ->assertJsonCount(3, 'data.model_hotspots');

        $this->get("/api/wildlife/species/{$orangutan['species_id']}/model")
            ->assertOk()->assertHeader('Content-Type', 'model/gltf-binary');

        $this->getJson('/api/wildlife/species?search=Mawas')->assertJsonCount(1, 'data');
    }

    public function test_hidden_species_are_not_public(): void
    {
        $species = Species::where('scientific_name', 'Rusa unicolor')->first();
        $species->update(['is_public' => false]);

        $this->getJson("/api/wildlife/species/{$species->species_id}")->assertNotFound();
        $this->get("/api/wildlife/species/{$species->species_id}/model")->assertNotFound();
        $this->getJson('/api/wildlife/species')->assertJsonCount(7, 'data');
    }

    public function test_researcher_can_upload_valid_glb_and_edit_hotspots(): void
    {
        $h = $this->loginAs('peneliti');
        $species = Species::where('scientific_name', 'Pongo abelii')->first();

        $this->post("/api/species/{$species->species_id}/model", ['file' => $this->glb(), 'model_source' => 'scan', 'model_credit' => 'Pindaian <b>tim</b> TNGL, CC-BY 4.0'], $h + ['Accept' => 'application/json'])
            ->assertOk()->assertJsonPath('data.model_source', 'scan')->assertJsonPath('data.model_credit', 'Pindaian tim TNGL, CC-BY 4.0')
            ->assertJsonPath('data.model_hotspots', null);

        $this->putJson("/api/species/{$species->species_id}", ['model_hotspots' => [
            ['label' => 'Wajah', 'description' => '<script>x</script>Bantalan pipi', 'position' => '0m 1.2m 0.1m', 'normal' => '0m 0m 1m'],
        ]], $h)->assertOk()->assertJsonPath('data.model_hotspots.0.description', 'xBantalan pipi');

        $this->putJson("/api/species/{$species->species_id}", ['model_hotspots' => [['label' => 'X', 'position' => 'javascript:alert(1)']]], $h)
            ->assertUnprocessable();
    }

    public function test_upload_rejects_non_glb_truncated_and_external_references(): void
    {
        $h = $this->loginAs('peneliti') + ['Accept' => 'application/json'];
        $id = Species::where('scientific_name', 'Pongo abelii')->value('species_id');
        $send = fn (UploadedFile $file) => $this->post("/api/species/{$id}/model", ['file' => $file, 'model_source' => 'scan', 'model_credit' => 'Uji'], $h);

        $send(UploadedFile::fake()->createWithContent('model.glb', 'bukan glb sama sekali, hanya teks biasa...'))->assertUnprocessable();
        $send(UploadedFile::fake()->image('foto.png'))->assertUnprocessable();
        $send($this->glb(declaredLength: 99999))->assertUnprocessable();
        $send($this->glb(['asset' => ['version' => '2.0'], 'images' => [['uri' => 'https://contoh.example/pelacak.png']]]))->assertUnprocessable();
    }

    public function test_only_master_data_roles_can_change_models(): void
    {
        $id = Species::where('scientific_name', 'Pongo abelii')->value('species_id');
        foreach (['lapangan', 'pengunjung', 'kebijakan'] as $user) {
            $this->post("/api/species/{$id}/model", ['file' => $this->glb(), 'model_source' => 'scan', 'model_credit' => 'x'], $this->loginAs($user) + ['Accept' => 'application/json'])
                ->assertForbidden();
        }
        $this->get("/api/species/{$id}/model", $this->loginAs('lapangan'))->assertOk();
    }
}
