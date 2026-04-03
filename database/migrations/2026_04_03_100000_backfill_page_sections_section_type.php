<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Map legacy section_key → section_type for existing rows with empty/null type.
     *
     * @var array<string, string>
     */
    private const KEY_TO_TYPE = [
        'hero' => 'hero',
        'main' => 'rich_text',
        'route_cards' => 'features',
        'fleet_block' => 'cards_teaser',
        'why_us' => 'features',
        'how_it_works' => 'features',
        'rental_conditions' => 'features',
        'reviews_block' => 'cards_teaser',
        'faq_block' => 'faq',
        'final_cta' => 'cta',
    ];

    public function up(): void
    {
        foreach (self::KEY_TO_TYPE as $key => $type) {
            DB::table('page_sections')
                ->where('section_key', $key)
                ->where(function ($q): void {
                    $q->whereNull('section_type')->orWhere('section_type', '');
                })
                ->update(['section_type' => $type]);
        }

        DB::table('page_sections')
            ->where('section_type', 'html')
            ->update(['section_type' => 'rich_text']);
    }

    public function down(): void
    {
        // Non-destructive: cannot reliably restore previous null types.
    }
};
