<div>
    <x-motorcycle.edit-block-chrome
        title="Основная информация"
        description="Название, идентификатор URL, бренд, модель, краткое позиционирование и чипы каталога."
        save-label="Сохранить основное"
        :status-text="$this->statusLine"
    >
        {{ $this->form }}
    </x-motorcycle.edit-block-chrome>
</div>
