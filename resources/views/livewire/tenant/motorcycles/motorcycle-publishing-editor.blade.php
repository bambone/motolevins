<div>
    <x-motorcycle.edit-block-chrome
        title="Публикация и видимость"
        description="Статус, сортировка, категория и отображение в каталоге. Тарифы и цены — в отдельном блоке ниже."
        save-label="Сохранить публикацию"
        :status-text="$this->statusLine"
    >
        {{ $this->form }}
    </x-motorcycle.edit-block-chrome>
</div>
