<div>
    <x-motorcycle.edit-block-chrome
        variant="secondary"
        :compact="true"
        title="Страница модели"
        description="Аудитория, сценарий, плюсы и примечания по аренде на публичной странице."
        save-label="Сохранить контент страницы"
        :status-text="$this->statusLine"
    >
        {{ $this->form }}
    </x-motorcycle.edit-block-chrome>
</div>
