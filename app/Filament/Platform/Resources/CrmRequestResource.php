<?php

namespace App\Filament\Platform\Resources;

use App\Filament\Platform\Resources\Concerns\GrantsPlatformPanelAccess;
use App\Filament\Platform\Resources\CrmRequestResource\Pages;
use App\Filament\Shared\CRM\CrmSharedFilters;
use App\Filament\Shared\CRM\CrmSharedInfolist;
use App\Filament\Shared\CRM\CrmSharedTable;
use App\Filament\Shared\CRM\CrmSharedWorkspaceSchema;
use App\Models\CrmRequest;
use App\Models\User;
use App\Product\CRM\CrmRequestOperatorService;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class CrmRequestResource extends Resource
{
    use GrantsPlatformPanelAccess;

    protected static ?string $model = CrmRequest::class;

    protected static ?string $navigationLabel = 'CRM-заявки';

    protected static ?string $modelLabel = 'CRM-заявка';

    protected static ?string $pluralModelLabel = 'CRM-заявки';

    protected static string|UnitEnum|null $navigationGroup = 'CRM';

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $panel = 'platform';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereNull('tenant_id')
            ->withCount('notes')
            ->with('assignedUser');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CrmSharedInfolist::schema($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(CrmSharedTable::columns())
            ->filters(CrmSharedFilters::tableFilters(static::getEloquentQuery()))
            ->defaultSort('id', 'desc')
            ->recordAction('open')
            ->recordUrl(null)
            ->recordClasses(CrmSharedTable::recordClasses())
            ->actions([
                EditAction::make('open')
                    ->label('Открыть')
                    ->slideOver()
                    ->modalWidth('7xl')
                    ->modalHeading('')
                    ->extraModalWindowAttributes(['class' => 'crm-ws-modal'])
                    ->stickyModalFooter()
                    ->modalFooterActionsAlignment(Alignment::End)
                    ->form(CrmSharedWorkspaceSchema::schema())
                    ->mutateRecordDataUsing(function (array $data, CrmRequest $record): array {
                        $user = Auth::user();
                        if ($user instanceof User) {
                            app(CrmRequestOperatorService::class)->markFirstViewed($user, $record);
                        }

                        return $data;
                    })
                    ->using(function (array $data, CrmRequest $record): void {
                        $user = Auth::user();
                        if (! $user instanceof User) {
                            return;
                        }

                        $service = app(CrmRequestOperatorService::class);

                        $service->changeStatus($user, $record, $data['status'] ?? CrmRequest::STATUS_NEW);
                        $service->updatePriority($user, $record, $data['priority'] ?? CrmRequest::PRIORITY_NORMAL);
                        $service->updateFollowUp($user, $record, ! empty($data['next_follow_up_at']) ? new \DateTime($data['next_follow_up_at']) : null);
                        $service->updateSummary($user, $record, $data['internal_summary'] ?? null);
                    }),
            ])
            ->paginated([25, 50, 100]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return true;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCrmRequests::route('/'),
        ];
    }
}
