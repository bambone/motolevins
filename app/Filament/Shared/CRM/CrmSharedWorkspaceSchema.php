<?php

namespace App\Filament\Shared\CRM;

use App\Models\CrmRequest;
use App\Models\User;
use App\Product\CRM\CrmRequestOperatorService;
use App\Product\CRM\CrmWorkspacePresentation;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Size;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;

final class CrmSharedWorkspaceSchema
{
    /**
     * @return array<int, Component|\Filament\Forms\Components\Component>
     */
    public static function schema(): array
    {
        return [
            Grid::make(1)
                ->extraAttributes(['class' => 'crm-ws-root crm-ws'])
                ->schema([
                    Placeholder::make('lead_identity')
                        ->hiddenLabel()
                        ->content(function (CrmRequest $record): HtmlString {
                            $iconAttrs = ['width' => '16', 'height' => '16', 'aria-hidden' => 'true'];
                            $emailIcon = svg('heroicon-o-envelope', '', $iconAttrs)->toHtml();
                            $phoneIcon = svg('heroicon-o-phone', '', $iconAttrs)->toHtml();
                            $name = e($record->name ?: 'Без имени');
                            $email = $record->email
                                ? '<a href="mailto:'.e($record->email).'" class="crm-ws-identity-contact"><span class="crm-ws-identity-contact-icon">'.$emailIcon.'</span><span class="crm-ws-identity-contact-text">'.e($record->email).'</span></a>'
                                : '';
                            $phone = $record->phone
                                ? '<a href="tel:'.e($record->phone).'" class="crm-ws-identity-contact"><span class="crm-ws-identity-contact-icon">'.$phoneIcon.'</span><span class="crm-ws-identity-contact-text crm-ws-identity-contact-text--tel">'.e($record->phone).'</span></a>'
                                : '';
                            $contacts = ($email || $phone)
                                ? '<div class="crm-ws-identity-contacts">'.$email.$phone.'</div>'
                                : '<p class="crm-ws-identity-empty">Контакты не указаны</p>';

                            $statusLabel = e(CrmRequest::statusLabels()[$record->status] ?? $record->status);
                            $priorityLabel = e(CrmRequest::priorityLabels()[$record->priority ?? CrmRequest::PRIORITY_NORMAL]);
                            $statusCls = e(CrmWorkspacePresentation::identityBadgeClassForStatus($record->status));
                            $priorityCls = e(CrmWorkspacePresentation::identityBadgeClassForPriority($record->priority));

                            $id = e((string) $record->id);
                            $created = e($record->created_at?->format('d.m.Y H:i') ?? '—');
                            $type = e($record->request_type ?? '—');

                            return new HtmlString(
                                '<header class="crm-ws-identity">'
                                .'<div class="crm-ws-identity-grid">'
                                .'<div class="crm-ws-identity-primary">'
                                .'<h2 class="crm-ws-identity-name">'.$name.'</h2>'
                                .$contacts
                                .'</div>'
                                .'<div class="crm-ws-identity-aside">'
                                .'<div class="crm-ws-identity-badges">'
                                .'<span class="'.$statusCls.'">'.$statusLabel.'</span>'
                                .'<span class="'.$priorityCls.'">'.$priorityLabel.'</span>'
                                .'</div>'
                                .'<div class="crm-ws-identity-meta">'
                                .'<div class="crm-ws-identity-meta-row"><span class="crm-ws-identity-meta-hash">#</span><span class="crm-ws-identity-meta-id">'.$id.'</span></div>'
                                .'<div class="crm-ws-identity-meta-row crm-ws-identity-meta-row--muted crm-ws-identity-meta-time">'.$created.'</div>'
                                .'<div class="crm-ws-identity-meta-row crm-ws-identity-meta-row--muted crm-ws-identity-meta-type" title="'.$type.'">'.$type.'</div>'
                                .'</div>'
                                .'</div>'
                                .'</div>'
                                .'</header>'
                            );
                        }),

                    Grid::make(['default' => 1, 'lg' => 3])
                        ->columnSpanFull()
                        ->gap()
                        ->schema([
                            Grid::make(1)
                                ->columnSpan(['lg' => 2])
                                ->extraAttributes(['class' => 'crm-ws-col-main'])
                                ->gap()
                                ->schema([
                                    Section::make('Сообщение клиента')
                                        ->icon('heroicon-o-chat-bubble-left-ellipsis')
                                        ->schema([
                                            Placeholder::make('message')
                                                ->hiddenLabel()
                                                ->content(function (CrmRequest $record): HtmlString {
                                                    $body = $record->message
                                                        ? '<div class="crm-ws-message-body">'.e($record->message).'</div>'
                                                        : '<p class="text-sm text-gray-400 dark:text-gray-500">Нет сообщения</p>';

                                                    return new HtmlString(
                                                        '<div class="crm-ws-message max-w-prose border-l-2 border-primary-500/45 py-1 pl-5 dark:border-primary-400/35 sm:pl-6">'.$body.'</div>'
                                                    );
                                                }),
                                        ]),

                                    Section::make('Внутренние заметки')
                                        ->icon('heroicon-o-document-text')
                                        ->extraAttributes(['class' => 'crm-ws-section-notes'])
                                        ->headerActions([
                                            Action::make('add_note')
                                                ->label('Добавить заметку')
                                                ->icon('heroicon-o-plus')
                                                ->color('primary')
                                                ->size(Size::Small)
                                                ->button()
                                                ->form([
                                                    Textarea::make('body')
                                                        ->label('Текст заметки')
                                                        ->required()
                                                        ->rows(4),
                                                ])
                                                ->action(function (array $data, CrmRequest $record): void {
                                                    $user = Auth::user();
                                                    if (! $user instanceof User) {
                                                        return;
                                                    }
                                                    app(CrmRequestOperatorService::class)->addNote(
                                                        $user,
                                                        $record,
                                                        $data['body']
                                                    );
                                                    $record->load('notes.user');
                                                })
                                                ->slideOver()
                                                ->modalWidth('2xl'),
                                        ])
                                        ->schema([
                                            ViewField::make('notes')
                                                ->hiddenLabel()
                                                ->view('filament.shared.crm.partials.notes-list'),
                                        ]),

                                    Section::make('Лента активности')
                                        ->icon('heroicon-o-clock')
                                        ->schema([
                                            ViewField::make('activities')
                                                ->hiddenLabel()
                                                ->view('filament.shared.crm.partials.activity-timeline'),
                                        ])
                                        ->collapsible()
                                        ->collapsed(false),
                                ]),

                            Grid::make(1)
                                ->columnSpan(['lg' => 1])
                                ->extraAttributes(['class' => 'crm-ws-col-side'])
                                ->gap()
                                ->schema([
                                    Section::make('Управление')
                                        ->icon('heroicon-o-adjustments-vertical')
                                        ->extraAttributes(['class' => 'crm-ws-manage'])
                                        ->schema([
                                            Select::make('status')
                                                ->label('Статус')
                                                ->helperText('Сохраняется сразу при смене значения.')
                                                ->options(CrmRequest::statusLabels())
                                                ->required()
                                                ->native()
                                                ->selectablePlaceholder(false)
                                                ->live()
                                                ->afterStateUpdated(function (mixed $state, mixed $old, Set $set, CrmRequest $record): void {
                                                    if (! is_string($state) || $state === '') {
                                                        return;
                                                    }
                                                    self::runWorkspaceAutosave(
                                                        $record,
                                                        $old,
                                                        $set,
                                                        'status',
                                                        fn (User $u, CrmRequest $r) => app(CrmRequestOperatorService::class)->changeStatus($u, $r, $state),
                                                        'Статус сохранён',
                                                    );
                                                }),

                                            Select::make('priority')
                                                ->label('Приоритет')
                                                ->helperText('Сохраняется сразу при смене значения.')
                                                ->options(CrmRequest::priorityLabels())
                                                ->required()
                                                ->native()
                                                ->selectablePlaceholder(false)
                                                ->live()
                                                ->afterStateUpdated(function (mixed $state, mixed $old, Set $set, CrmRequest $record): void {
                                                    if (! is_string($state) || $state === '') {
                                                        return;
                                                    }
                                                    self::runWorkspaceAutosave(
                                                        $record,
                                                        $old,
                                                        $set,
                                                        'priority',
                                                        fn (User $u, CrmRequest $r) => app(CrmRequestOperatorService::class)->updatePriority($u, $r, $state),
                                                        'Приоритет сохранён',
                                                    );
                                                }),

                                            DateTimePicker::make('next_follow_up_at')
                                                ->label('Follow-up')
                                                ->helperText('Сохраняется после выбора даты (короткая пауза).')
                                                ->native(false)
                                                ->nullable()
                                                ->live(debounce: 500)
                                                ->afterStateUpdated(function (mixed $state, mixed $old, Set $set, CrmRequest $record): void {
                                                    self::runWorkspaceAutosave(
                                                        $record,
                                                        $old,
                                                        $set,
                                                        'next_follow_up_at',
                                                        fn (User $u, CrmRequest $r) => app(CrmRequestOperatorService::class)->updateFollowUp(
                                                            $u,
                                                            $r,
                                                            self::parseFollowUpFormState($state),
                                                        ),
                                                        'Follow-up сохранён',
                                                    );
                                                }),

                                            Textarea::make('internal_summary')
                                                ->label('Внутреннее резюме')
                                                ->helperText('Сохраняется автоматически с небольшой задержкой после ввода.')
                                                ->rows(4)
                                                ->placeholder('Кратко для смены')
                                                ->live(debounce: 800)
                                                ->afterStateUpdated(function (mixed $state, mixed $old, Set $set, CrmRequest $record): void {
                                                    $summary = is_string($state) ? $state : (is_scalar($state) ? (string) $state : null);
                                                    self::runWorkspaceAutosave(
                                                        $record,
                                                        $old,
                                                        $set,
                                                        'internal_summary',
                                                        fn (User $u, CrmRequest $r) => app(CrmRequestOperatorService::class)->updateSummary($u, $r, $summary),
                                                        'Резюме сохранено',
                                                    );
                                                }),
                                        ]),

                                    Section::make('Атрибуция')
                                        ->icon('heroicon-o-identification')
                                        ->extraAttributes(['class' => 'crm-ws-section-utility'])
                                        ->schema([
                                            Placeholder::make('attribution')
                                                ->hiddenLabel()
                                                ->content(function (CrmRequest $record): HtmlString {
                                                    $src = e($record->source ?: '—');
                                                    $ch = e($record->channel ?: '—');
                                                    $us = e($record->utm_source ?: '—');
                                                    $um = e($record->utm_medium ?: '—');
                                                    $uc = e($record->utm_campaign ?: '—');

                                                    return new HtmlString(
                                                        '<div class="space-y-3 text-sm text-gray-600 dark:text-gray-400">'
                                                        .'<div><span class="text-gray-400 dark:text-gray-500">Источник</span> · '.$src.' / '.$ch.'</div>'
                                                        .'<div class="grid grid-cols-3 gap-x-2 gap-y-1 text-xs">'
                                                        .'<span class="text-gray-400 dark:text-gray-500">utm_source</span><span class="col-span-2 font-mono">'.$us.'</span>'
                                                        .'<span class="text-gray-400 dark:text-gray-500">utm_medium</span><span class="col-span-2 font-mono">'.$um.'</span>'
                                                        .'<span class="text-gray-400 dark:text-gray-500">utm_campaign</span><span class="col-span-2 font-mono">'.$uc.'</span>'
                                                        .'</div></div>'
                                                    );
                                                }),
                                        ])
                                        ->collapsible(),

                                    Section::make('Технические данные')
                                        ->icon('heroicon-o-command-line')
                                        ->extraAttributes(['class' => 'crm-ws-section-utility'])
                                        ->schema([
                                            Placeholder::make('technical')
                                                ->hiddenLabel()
                                                ->content(function (CrmRequest $record): HtmlString {
                                                    $json = $record->payload_json
                                                        ? json_encode($record->payload_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                                                        : '{}';

                                                    return new HtmlString(
                                                        '<div class="space-y-3 text-xs text-gray-500 dark:text-gray-400">'
                                                        .'<div class="break-all"><span class="text-gray-400">IP</span> '.e($record->ip ?: '—').'</div>'
                                                        .'<div class="break-all"><span class="text-gray-400">Referrer</span> '.e($record->referrer ?: '—').'</div>'
                                                        .'<pre class="max-h-40 overflow-auto rounded-lg bg-black/20 p-2.5 font-mono text-[11px] leading-relaxed text-gray-400 dark:bg-black/35">'
                                                        .e($json)
                                                        .'</pre></div>'
                                                    );
                                                }),
                                        ])
                                        ->collapsible()
                                        ->collapsed(true),
                                ]),
                        ]),
                ]),
        ];
    }

    /**
     * @param  callable(User, CrmRequest): void  $mutation
     */
    private static function runWorkspaceAutosave(
        CrmRequest $record,
        mixed $oldFormValue,
        Set $set,
        string $fieldName,
        callable $mutation,
        string $successTitle,
    ): void {
        $user = Auth::user();
        if (! $user instanceof User) {
            return;
        }

        try {
            $mutation($user, $record);
            $record->refresh();
            Notification::make()
                ->title($successTitle)
                ->success()
                ->duration(2500)
                ->send();
        } catch (ValidationException $e) {
            $set($fieldName, $oldFormValue);
            $msg = collect($e->errors())->flatten()->first();
            Notification::make()
                ->title('Не удалось сохранить')
                ->body(is_string($msg) ? $msg : 'Проверьте значение.')
                ->danger()
                ->send();
        } catch (\Throwable $e) {
            report($e);
            $set($fieldName, $oldFormValue);
            Notification::make()
                ->title('Ошибка сохранения')
                ->danger()
                ->send();
        }
    }

    private static function parseFollowUpFormState(mixed $state): ?\DateTimeInterface
    {
        if ($state === null || $state === '') {
            return null;
        }

        if ($state instanceof \DateTimeInterface) {
            return $state;
        }

        return new \DateTime((string) $state);
    }
}
