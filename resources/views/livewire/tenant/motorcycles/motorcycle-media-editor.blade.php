<div>
    <x-motorcycle.edit-block-chrome
        title="Медиа"
        description="Обложка и галерея. Загрузки часто уходят в хранилище сразу; кнопка ниже фиксирует текущее состояние списка."
        save-label="Сохранить медиа"
        :status-text="$this->statusLine"
    >
        {{ $this->form }}
    </x-motorcycle.edit-block-chrome>
</div>
