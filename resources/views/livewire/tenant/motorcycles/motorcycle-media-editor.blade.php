<div class="fi-motorcycle-media-editor">
    <x-motorcycle.edit-block-chrome
        variant="primary"
        :compact="true"
        title="Медиа"
        description="Обложка и галерея; «Сохранить» фиксирует порядок и удаления в форме."
        save-label="Сохранить медиа"
        :status-text="$this->statusLine"
    >
        {{ $this->form }}
    </x-motorcycle.edit-block-chrome>
</div>
