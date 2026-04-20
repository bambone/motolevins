<div>
    <x-motorcycle.edit-block-chrome
        variant="primary"
        :compact="true"
        title="Основная информация"
        description="Название, slug, бренд, модель, краткий текст и чипы каталога."
        save-label="Сохранить основное"
        :status-text="$this->statusLine"
    >
        {{ $this->form }}
    </x-motorcycle.edit-block-chrome>
</div>
