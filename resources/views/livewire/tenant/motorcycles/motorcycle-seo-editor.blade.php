<div class="space-y-4">
    <div class="fi-section rounded-xl border border-gray-200 p-4 dark:border-white/10">
        <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Публичный сниппет (предпросмотр)</h3>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Вычисляется из контента и SEO; не является отдельным сохраняемым полем.</p>
        <div class="mt-2">
            {!! $this->seoPreview !!}
        </div>
    </div>

    <x-motorcycle.edit-block-chrome
        title="SEO"
        description="Метаданные для поиска и соцсетей (модель SeoMeta)."
        save-label="Сохранить SEO"
        :status-text="$this->statusLine"
    >
        {{ $this->form }}
    </x-motorcycle.edit-block-chrome>
</div>
