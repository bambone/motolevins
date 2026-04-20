<div>
    <x-motorcycle.edit-block-chrome
        variant="primary"
        :compact="true"
        title="Тарифы и условия"
        description="Тарифы, карточка каталога, залог и подпись цены."
        save-label="Сохранить тарифы"
        :status-text="$this->statusLine"
    >
        {{ $this->form }}
    </x-motorcycle.edit-block-chrome>
</div>
