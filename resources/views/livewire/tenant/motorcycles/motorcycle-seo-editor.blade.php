<div class="space-y-4">
    <details
        class="fi-moto-seo-preview-details fi-section rounded-xl border border-gray-200 p-4 dark:border-white/10 [&_summary]:list-none [&_summary::-webkit-details-marker]:hidden"
    >
        <summary
            class="flex cursor-pointer items-center justify-between gap-2 text-sm font-semibold text-gray-950 dark:text-white"
        >
            <span>Публичный сниппет (предпросмотр)</span>
            <span class="text-xs font-normal text-gray-500 dark:text-gray-400">развернуть</span>
        </summary>
        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
            Считается из контента и SEO; отдельно не сохраняется.
        </p>
        <div class="mt-2">
            {!! $this->seoPreview !!}
        </div>
    </details>

    <x-motorcycle.edit-block-chrome
        variant="quiet"
        :compact="true"
        title="SEO"
        description="Мета для поиска и соцсетей (SeoMeta)."
        save-label="Сохранить SEO"
        :status-text="$this->statusLine"
    >
        {{ $this->form }}
    </x-motorcycle.edit-block-chrome>
</div>
