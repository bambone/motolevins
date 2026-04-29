<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use Illuminate\Contracts\View\View;

class TenantPublicFaqController extends Controller
{
    public function __invoke(): View
    {
        $t = tenant();
        abort_if($t === null, 404);

        $faqs = Faq::query()
            ->where('status', 'published')
            ->forPublicHubAndFaqPage()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $theme = $t->themeKey();
        $isBlackDuck = $theme === 'black_duck';
        $isExpertPr = $theme === 'expert_pr';
        $faqPageIntroLine1 = $isBlackDuck
            ? 'Кратко о записи, сроках и порядке работ. Точный план и смета по вашему авто — после осмотра или согласованной заявки.'
            : ($isExpertPr
                ? 'Straight answers about collaboration, timelines, and what to expect — before you send a brief.'
                : 'Краткие ответы на частые вопросы по срокам, гарантии и записи.');

        return tenant_view('pages.faq', [
            'faqs' => $faqs,
            'faqPageIntroLine1' => $faqPageIntroLine1,
        ]);
    }
}
