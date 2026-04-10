<div>
    <x-motorcycle.edit-block-chrome
        title="Характеристики"
        description="Базовые параметры и произвольные пары «название — значение»."
        save-label="Сохранить характеристики"
        :status-text="$this->statusLine"
    >
        {{ $this->form }}
    </x-motorcycle.edit-block-chrome>
</div>
