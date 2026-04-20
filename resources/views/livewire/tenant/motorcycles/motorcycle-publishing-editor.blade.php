<div>
    <x-motorcycle.edit-block-chrome
        variant="secondary"
        :compact="true"
        title="Публикация и видимость"
        description="Статус, сортировка, категория и показ в каталоге / на главной."
        save-label="Сохранить публикацию"
        :status-text="$this->statusLine"
    >
        {{ $this->form }}
    </x-motorcycle.edit-block-chrome>
</div>
