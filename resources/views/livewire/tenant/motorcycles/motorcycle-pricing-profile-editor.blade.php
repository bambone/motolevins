<div>
    <x-motorcycle.edit-block-chrome
        title="Тарифы и условия"
        description="Профиль ценообразования v1: тарифы, отображение на карточке, залог и подпись в каталоге. Стабильные id строк не меняются при сортировке."
        save-label="Сохранить тарифы"
        :status-text="$this->statusLine"
    >
        {{ $this->form }}
    </x-motorcycle.edit-block-chrome>
</div>
