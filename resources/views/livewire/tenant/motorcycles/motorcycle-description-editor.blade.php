<div>
    <x-motorcycle.edit-block-chrome
        title="Подробное описание"
        description="Только текст карточки; остальные поля сохраняются в своих блоках."
        save-label="Сохранить описание"
        :status-text="$this->statusLine"
    >
        {{ $this->form }}
    </x-motorcycle.edit-block-chrome>
</div>
