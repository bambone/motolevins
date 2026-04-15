<?php

namespace Tests\Feature\PageBuilder;

use App\Livewire\Tenant\PageSectionsBuilder;
use App\Models\Page;
use App\Models\Tenant;
use App\Models\TenantDomain;
use App\Tenant\CurrentTenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\TestCase;

/**
 * Редактор секции editorial_gallery: дефолты repeater, валидация по media_kind, подсказки embed.
 */
class EditorialGallerySectionLivewireTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;

    private function bindTenantContext(Tenant $tenant): void
    {
        $host = $this->tenancyHostForSlug((string) $tenant->slug);
        $domain = TenantDomain::query()->where('tenant_id', $tenant->id)->firstOrFail();
        $this->app->instance(CurrentTenant::class, new CurrentTenant($tenant, $domain, false, $host));
    }

    private function homePage(Tenant $tenant): Page
    {
        return Page::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Главная',
            'slug' => 'home',
            'template' => 'default',
            'status' => 'published',
            'published_at' => now(),
            'show_in_main_menu' => false,
            'main_menu_sort_order' => 0,
        ]);
    }

    public function test_repeater_add_seeds_media_kind_image_and_source_new_tab_true(): void
    {
        $tenant = $this->createTenantWithActiveDomain('pb-eg-add', ['theme_key' => 'advocate_editorial']);
        $this->bindTenantContext($tenant);
        $page = $this->homePage($tenant);

        $lw = Livewire::test(PageSectionsBuilder::class, ['record' => $page])
            ->call('startAdd', 'editorial_gallery');

        $this->assertSame([], $lw->get('sectionFormData.data_json.items') ?? []);

        $lw->call('mountAction', 'add', [], ['schemaComponent' => 'sectionEditor.data_json.items']);

        $items = $lw->get('sectionFormData.data_json.items') ?? [];
        $this->assertCount(1, $items);
        $row = $items[array_key_first($items)] ?? [];
        $this->assertSame('image', $row['media_kind'] ?? null);
        $this->assertTrue((bool) ($row['source_new_tab'] ?? false));
    }

    public function test_save_fails_when_image_row_has_empty_image_url(): void
    {
        $tenant = $this->createTenantWithActiveDomain('pb-eg-val-img', ['theme_key' => 'advocate_editorial']);
        $this->bindTenantContext($tenant);
        $page = $this->homePage($tenant);

        $lw = Livewire::test(PageSectionsBuilder::class, ['record' => $page])
            ->call('startAdd', 'editorial_gallery')
            ->call('mountAction', 'add', [], ['schemaComponent' => 'sectionEditor.data_json.items']);

        $items = $lw->get('sectionFormData.data_json.items') ?? [];
        $key = (string) array_key_first($items);
        $lw->set('sectionFormData.data_json.items.'.$key.'.media_kind', 'image');
        $lw->set('sectionFormData.data_json.items.'.$key.'.image_url', '');

        $lw->call('save')
            ->assertHasErrors();
    }

    public function test_save_fails_when_video_row_has_empty_video_url(): void
    {
        $tenant = $this->createTenantWithActiveDomain('pb-eg-val-vid', ['theme_key' => 'advocate_editorial']);
        $this->bindTenantContext($tenant);
        $page = $this->homePage($tenant);

        $lw = Livewire::test(PageSectionsBuilder::class, ['record' => $page])
            ->call('startAdd', 'editorial_gallery')
            ->call('mountAction', 'add', [], ['schemaComponent' => 'sectionEditor.data_json.items']);

        $items = $lw->get('sectionFormData.data_json.items') ?? [];
        $key = (string) array_key_first($items);
        $lw->set('sectionFormData.data_json.items.'.$key.'.media_kind', 'video');
        $lw->set('sectionFormData.data_json.items.'.$key.'.video_url', '');

        $lw->call('save')
            ->assertHasErrors();
    }

    public function test_save_fails_when_video_embed_without_provider(): void
    {
        $tenant = $this->createTenantWithActiveDomain('pb-eg-val-emb', ['theme_key' => 'advocate_editorial']);
        $this->bindTenantContext($tenant);
        $page = $this->homePage($tenant);

        $lw = Livewire::test(PageSectionsBuilder::class, ['record' => $page])
            ->call('startAdd', 'editorial_gallery')
            ->call('mountAction', 'add', [], ['schemaComponent' => 'sectionEditor.data_json.items']);

        $items = $lw->get('sectionFormData.data_json.items') ?? [];
        $key = (string) array_key_first($items);
        $lw->set('sectionFormData.data_json.items.'.$key.'.media_kind', 'video_embed');
        $lw->set('sectionFormData.data_json.items.'.$key.'.embed_provider', null);
        $lw->set('sectionFormData.data_json.items.'.$key.'.embed_share_url', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ');

        $lw->call('save')
            ->assertHasErrors();
    }

    public function test_save_fails_when_video_embed_share_url_invalid_for_provider(): void
    {
        $tenant = $this->createTenantWithActiveDomain('pb-eg-val-url', ['theme_key' => 'advocate_editorial']);
        $this->bindTenantContext($tenant);
        $page = $this->homePage($tenant);

        $lw = Livewire::test(PageSectionsBuilder::class, ['record' => $page])
            ->call('startAdd', 'editorial_gallery')
            ->call('mountAction', 'add', [], ['schemaComponent' => 'sectionEditor.data_json.items']);

        $items = $lw->get('sectionFormData.data_json.items') ?? [];
        $key = (string) array_key_first($items);
        $lw->set('sectionFormData.data_json.items.'.$key.'.media_kind', 'video_embed');
        $lw->set('sectionFormData.data_json.items.'.$key.'.embed_provider', 'youtube');
        $lw->set('sectionFormData.data_json.items.'.$key.'.embed_share_url', 'https://example.com/not-youtube');

        $lw->call('save')
            ->assertHasErrors();
    }

    public function test_editor_html_hides_embed_fields_for_image_kind(): void
    {
        $tenant = $this->createTenantWithActiveDomain('pb-eg-vis-img', ['theme_key' => 'advocate_editorial']);
        $this->bindTenantContext($tenant);
        $page = $this->homePage($tenant);

        $lw = Livewire::test(PageSectionsBuilder::class, ['record' => $page])
            ->call('startAdd', 'editorial_gallery')
            ->call('mountAction', 'add', [], ['schemaComponent' => 'sectionEditor.data_json.items']);

        $html = $lw->html();
        $this->assertStringContainsString('Изображение (путь или URL)', $html);
        $this->assertStringNotContainsString('Площадка встраивания', $html);
        $this->assertStringNotContainsString('Видеофайл (путь или URL)', $html);
    }

    public function test_editor_html_hides_image_and_embed_for_video_file_kind(): void
    {
        $tenant = $this->createTenantWithActiveDomain('pb-eg-vis-vid', ['theme_key' => 'advocate_editorial']);
        $this->bindTenantContext($tenant);
        $page = $this->homePage($tenant);

        $lw = Livewire::test(PageSectionsBuilder::class, ['record' => $page])
            ->call('startAdd', 'editorial_gallery')
            ->call('mountAction', 'add', [], ['schemaComponent' => 'sectionEditor.data_json.items']);

        $items = $lw->get('sectionFormData.data_json.items') ?? [];
        $key = (string) array_key_first($items);
        $lw->set('sectionFormData.data_json.items.'.$key.'.media_kind', 'video');

        $html = $lw->html();
        $this->assertStringContainsString('Видеофайл (путь или URL)', $html);
        $this->assertStringContainsString('Постер видео', $html);
        $this->assertStringNotContainsString('Изображение (путь или URL)', $html);
        $this->assertStringNotContainsString('Площадка встраивания', $html);
    }

    public function test_editor_html_shows_embed_fields_for_video_embed_kind(): void
    {
        $tenant = $this->createTenantWithActiveDomain('pb-eg-vis-emb', ['theme_key' => 'advocate_editorial']);
        $this->bindTenantContext($tenant);
        $page = $this->homePage($tenant);

        $lw = Livewire::test(PageSectionsBuilder::class, ['record' => $page])
            ->call('startAdd', 'editorial_gallery')
            ->call('mountAction', 'add', [], ['schemaComponent' => 'sectionEditor.data_json.items']);

        $items = $lw->get('sectionFormData.data_json.items') ?? [];
        $key = (string) array_key_first($items);
        $lw->set('sectionFormData.data_json.items.'.$key.'.media_kind', 'video_embed');
        $lw->set('sectionFormData.data_json.items.'.$key.'.embed_provider', 'vk');

        $html = $lw->html();
        $this->assertStringContainsString('Площадка встраивания', $html);
        $this->assertStringContainsString('Ссылка на ролик', $html);
        $this->assertStringContainsString('video_ext.php', $html);
        $this->assertStringNotContainsString('Изображение (путь или URL)', $html);
        $this->assertStringNotContainsString('Видеофайл (путь или URL)', $html);
    }

    public function test_embed_share_helper_switches_with_youtube_provider(): void
    {
        $tenant = $this->createTenantWithActiveDomain('pb-eg-hlp-yt', ['theme_key' => 'advocate_editorial']);
        $this->bindTenantContext($tenant);
        $page = $this->homePage($tenant);

        $lw = Livewire::test(PageSectionsBuilder::class, ['record' => $page])
            ->call('startAdd', 'editorial_gallery')
            ->call('mountAction', 'add', [], ['schemaComponent' => 'sectionEditor.data_json.items']);

        $items = $lw->get('sectionFormData.data_json.items') ?? [];
        $key = (string) array_key_first($items);
        $lw->set('sectionFormData.data_json.items.'.$key.'.media_kind', 'video_embed');
        $lw->set('sectionFormData.data_json.items.'.$key.'.embed_provider', 'youtube');

        $html = $lw->html();
        $this->assertStringContainsString('youtube.com/watch?v', $html);
        $this->assertStringNotContainsString('video_ext.php', $html);
    }
}
