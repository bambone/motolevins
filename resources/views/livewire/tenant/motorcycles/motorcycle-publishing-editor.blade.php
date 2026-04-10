<div>
    <x-motorcycle.edit-block-chrome
        title="Публикация и цены"
        description="Статус, сортировка, категория, цены, подпись под ценой в каталоге и видимость."
        save-label="Сохранить публикацию"
        :status-text="$this->statusLine"
    >
        {{ $this->form }}
    </x-motorcycle.edit-block-chrome>
</div>
