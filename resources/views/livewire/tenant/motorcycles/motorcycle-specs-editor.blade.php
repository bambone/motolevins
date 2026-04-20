<div>
    <x-motorcycle.edit-block-chrome
        variant="secondary"
        :compact="true"
        title="Характеристики"
        description="Базовые поля и произвольные пары «название — значение»."
        save-label="Сохранить характеристики"
        :status-text="$this->statusLine"
    >
        {{ $this->form }}
    </x-motorcycle.edit-block-chrome>
</div>
