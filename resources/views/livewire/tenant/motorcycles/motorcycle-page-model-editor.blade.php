<div>
    <x-motorcycle.edit-block-chrome
        title="Страница модели"
        description="Тексты для публичной карточки /moto/… (аудитория, сценарий, плюсы, примечания по аренде)."
        save-label="Сохранить контент страницы"
        :status-text="$this->statusLine"
    >
        {{ $this->form }}
    </x-motorcycle.edit-block-chrome>
</div>
