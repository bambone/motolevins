<?php

namespace App\Filament\Platform\Widgets\Mail;

use App\Auth\AccessRoles;
use App\Filament\Platform\Resources\TenantResource;
use App\Models\Tenant;
use App\Models\TenantMailLog;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class MailTenantActivityTableWidget extends TableWidget
{
    protected static bool $isLazy = false;

    protected static bool $isDiscovered = false;

    protected static ?string $panel = 'platform';

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Активность клиентов';

    public static function canView(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->hasAnyRole(AccessRoles::platformRoles());
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Tenant::query()
                    ->withCount([
                        'mailLogs as sent_24h' => fn (Builder $q): Builder => $q
                            ->where('status', TenantMailLog::STATUS_SENT)
                            ->where('sent_at', '>=', now()->subDay()),
                        'mailLogs as failed_24h' => fn (Builder $q): Builder => $q
                            ->where('status', TenantMailLog::STATUS_FAILED)
                            ->where('failed_at', '>=', now()->subDay()),
                    ])
                    ->withSum([
                        'mailLogs as throttle_hits_24h' => fn (Builder $q): Builder => $q
                            ->where('updated_at', '>=', now()->subDay()),
                    ], 'throttled_count')
                    ->withMax([
                        'mailLogs as last_sent_at' => fn (Builder $q): Builder => $q
                            ->whereNotNull('sent_at'),
                    ], 'sent_at')
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Клиент')
                    ->searchable()
                    ->url(fn (Tenant $record): string => TenantResource::getUrl('edit', ['record' => $record])),
                TextColumn::make('mail_rate_limit_per_minute')
                    ->label('Лимит/мин')
                    ->sortable(),
                TextColumn::make('sent_24h')
                    ->label('Отпр. 24ч')
                    ->sortable()
                    ->alignEnd(),
                TextColumn::make('failed_24h')
                    ->label('Ошибок 24ч')
                    ->sortable()
                    ->alignEnd()
                    ->color(fn (int $state): string => $state > 0 ? 'danger' : 'gray'),
                TextColumn::make('throttle_hits_24h')
                    ->label('Throttled 24ч')
                    ->sortable()
                    ->alignEnd()
                    ->formatStateUsing(fn ($state): string => (string) (int) $state),
                TextColumn::make('last_sent_at')
                    ->label('Последнее письмо')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('top_mail_type')
                    ->label('Топ тип (24ч)')
                    ->getStateUsing(function (Tenant $record): string {
                        $row = TenantMailLog::query()
                            ->where('tenant_id', $record->id)
                            ->where('created_at', '>=', now()->subDay())
                            ->selectRaw('mail_type, count(*) as c')
                            ->groupBy('mail_type')
                            ->orderByDesc('c')
                            ->first();

                        return $row !== null ? (string) $row->mail_type : '—';
                    }),
                TextColumn::make('health')
                    ->label('Состояние')
                    ->badge()
                    ->getStateUsing(function (Tenant $record): string {
                        $failed = (int) ($record->failed_24h ?? 0);
                        $throttle = (int) ($record->throttle_hits_24h ?? 0);

                        if ($failed >= 5) {
                            return 'critical';
                        }
                        if ($failed > 0 || $throttle >= 20) {
                            return 'warn';
                        }

                        return 'ok';
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'critical' => 'Много ошибок',
                        'warn' => 'Внимание',
                        default => 'Норма',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'critical' => 'danger',
                        'warn' => 'warning',
                        default => 'success',
                    }),
            ])
            ->defaultSort('sent_24h', 'desc')
            ->paginated([10, 25]);
    }
}
