<div>
    <x-motorcycle.edit-block-chrome
        variant="secondary"
        :compact="true"
        title="Подробное описание"
        description="Основной текст страницы модели (тезисы и SEO — в других блоках)."
        save-label="Сохранить описание"
        :status-text="$this->statusLine"
    >
        <div
            class="motorcycle-description-form mx-auto w-full max-w-3xl [&_.fi-fo-rich-editor-toolbar]:flex-wrap [&_.fi-fo-rich-editor-main]:min-h-[min(22rem,55vh)]"
        >
            {{ $this->form }}
        </div>
    </x-motorcycle.edit-block-chrome>
</div>
