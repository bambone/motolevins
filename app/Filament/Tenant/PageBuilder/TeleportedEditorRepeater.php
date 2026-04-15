<?php

namespace App\Filament\Tenant\PageBuilder;

use App\Contracts\ForcesFullLivewireRender;
use App\Livewire\Tenant\PageSectionsBuilder;
use Filament\Actions\Action;
use Filament\Actions\Concerns\HasLifecycleHooks;
use Filament\Forms\Components\Repeater;
use Livewire\Component;

/**
 * Repeater for {@see PageSectionsBuilder} section editor, which is rendered inside
 * {@code @teleport('body')}. Partial Livewire morphs often fail for add/delete/reorder; full render fixes that.
 *
 * Architectural note: Filament {@see HasLifecycleHooks::after} stores a single callback.
 * Do not chain arbitrary {@code ->after()} on the same Action without also calling {@see self::withFullLivewireRenderAfter},
 * or teleported editors may stop updating the DOM.
 */
class TeleportedEditorRepeater extends Repeater
{
    public static function withFullLivewireRenderAfter(Action $action): Action
    {
        return $action->after(function () use ($action): void {
            $livewire = $action->getLivewire();
            if ($livewire instanceof ForcesFullLivewireRender) {
                $livewire->forceFullLivewireRender();

                return;
            }
            if ($livewire instanceof Component) {
                $livewire->forceRender();
            }
        });
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->partiallyRenderAfterActionsCalled(false);
        $this->reorderableWithDragAndDrop(false);

        $this->addAction(fn (Action $action): Action => self::withFullLivewireRenderAfter($action));
        $this->addBetweenAction(fn (Action $action): Action => self::withFullLivewireRenderAfter($action));
        $this->deleteAction(fn (Action $action): Action => self::withFullLivewireRenderAfter($action));
        $this->moveUpAction(fn (Action $action): Action => self::withFullLivewireRenderAfter($action));
        $this->moveDownAction(fn (Action $action): Action => self::withFullLivewireRenderAfter($action));
        $this->cloneAction(fn (Action $action): Action => self::withFullLivewireRenderAfter($action));
        $this->reorderAction(fn (Action $action): Action => self::withFullLivewireRenderAfter($action));
    }
}
