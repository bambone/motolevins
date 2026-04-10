<div>
    @if (! \App\Filament\Tenant\Forms\LinkedBookableSchedulingForm::schedulingUiVisible())
        <div class="fi-section rounded-xl border border-gray-200 p-4 text-sm text-gray-600 dark:border-white/10 dark:text-gray-400">
            Онлайн-запись недоступна для текущей роли или настроек клиента.
        </div>
    @else
        <x-motorcycle.edit-block-chrome
            title="Онлайн-запись"
            description="Linked-услуга и параметры слотов для записи на эту модель."
            save-label="Сохранить онлайн-запись"
            :status-text="$this->statusLine"
        >
            {{ $this->form }}
        </x-motorcycle.edit-block-chrome>
    @endif
</div>
