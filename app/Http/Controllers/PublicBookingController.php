<?php

namespace App\Http\Controllers;

use App\ContactChannels\TenantContactChannelsStore;
use App\ContactChannels\VisitorContactPayloadBuilder;
use App\Http\Requests\StorePublicBookingCheckoutRequest;
use App\Models\Addon;
use App\Models\Booking;
use App\Models\Motorcycle;
use App\Models\RentalUnit;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use App\Services\Catalog\MotorcycleLocationCatalogService;
use App\Services\Catalog\TenantPublicCatalogLocationService;
use App\Services\PricingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;

class PublicBookingController extends Controller
{
    public function __construct(
        protected AvailabilityService $availabilityService,
        protected PricingService $pricingService,
        protected BookingService $bookingService,
        protected TenantPublicCatalogLocationService $catalogLocation,
        protected MotorcycleLocationCatalogService $motorcycleLocationCatalog,
    ) {}

    /**
     * Booking landing - redirect to catalog or show booking start.
     */
    public function index(): View
    {
        $motorcyclesQuery = Motorcycle::query()
            ->where('show_in_catalog', true)
            ->where('status', 'available');
        $selectedCatalogLocation = $this->catalogLocation->resolve();
        if ($selectedCatalogLocation !== null) {
            $this->motorcycleLocationCatalog->scopeMotorcyclesVisibleAtLocation($motorcyclesQuery, $selectedCatalogLocation);
        }
        $motorcycles = $motorcyclesQuery->orderBy('sort_order')->get();

        return tenant_view('booking.index', [
            'motorcycles' => $motorcycles,
            'catalogLocations' => $this->catalogLocation->activeLocationsForCurrentTenant(),
            'selectedCatalogLocation' => $selectedCatalogLocation,
            'catalogLocationFormAction' => route('booking.index'),
        ]);
    }

    /**
     * Show vehicle booking page with date picker and addons.
     */
    public function show(string $slug): View
    {
        $motorcycle = Motorcycle::where('slug', $slug)
            ->where('show_in_catalog', true)
            ->firstOrFail();

        $selectedCatalogLocation = $this->catalogLocation->resolve();
        $visibleAtSelectedLocation = $selectedCatalogLocation === null
            || $this->motorcycleLocationCatalog->isMotorcycleVisibleAtLocation($motorcycle, $selectedCatalogLocation);

        $rentalUnits = $this->activeRentalUnitsForPublicBooking($motorcycle);
        $addons = Addon::where('is_active', true)->orderBy('sort_order')->get();

        return view('tenant.booking.show', [
            'motorcycle' => $motorcycle,
            'rentalUnits' => $rentalUnits,
            'addons' => $addons,
            'catalogLocations' => $this->catalogLocation->activeLocationsForCurrentTenant(),
            'selectedCatalogLocation' => $selectedCatalogLocation,
            'catalogLocationFormAction' => route('booking.index'),
            'visibleAtSelectedLocation' => $visibleAtSelectedLocation,
        ]);
    }

    /**
     * Check availability and calculate price (AJAX).
     */
    public function calculate(Request $request)
    {
        $validated = $request->validate([
            'motorcycle_id' => ['required', 'exists:motorcycles,id'],
            'rental_unit_id' => ['nullable', 'exists:rental_units,id'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'addons' => ['nullable', 'array'],
            'addons.*' => ['integer', 'exists:addons,id'],
        ]);

        $motorcycle = Motorcycle::findOrFail($validated['motorcycle_id']);

        if ($response = $this->assertMotorcycleAllowedForPublicBooking($motorcycle)) {
            return $response;
        }

        $rentalUnits = $this->activeRentalUnitsForPublicBooking($motorcycle);
        $rentalUnit = $this->resolveRentalUnitForCalculateOrDraft($motorcycle, $rentalUnits, $validated['rental_unit_id'] ?? null);
        if ($rentalUnit instanceof JsonResponse) {
            return $rentalUnit;
        }

        $start = Carbon::parse($validated['start_date'])->startOfDay();
        $end = Carbon::parse($validated['end_date'])->endOfDay();

        $available = true;
        if ($rentalUnit) {
            $available = $this->availabilityService->isAvailable($rentalUnit, $start, $end);
        } else {
            $available = $this->bookingService->isAvailableForMotorcycle($motorcycle->id, $validated['start_date'], $validated['end_date']);
        }

        if (! $available) {
            return response()->json([
                'available' => false,
                'message' => 'Выбранные даты заняты. Попробуйте другие даты.',
            ]);
        }

        $target = $rentalUnit ?? $motorcycle;
        $addonIds = [];
        foreach ($validated['addons'] ?? [] as $addonId => $qty) {
            if (is_numeric($qty) && $qty > 0) {
                $addonIds[$addonId] = (int) $qty;
            }
        }

        $result = $this->pricingService->calculatePrice($target, $start, $end, 'daily', $addonIds);

        return response()->json([
            'available' => true,
            'price' => $result,
        ]);
    }

    /**
     * Show checkout form.
     */
    public function checkout(Request $request)
    {
        $session = Session::get('booking_draft', []);

        if (empty($session['motorcycle_id']) || empty($session['start_date']) || empty($session['end_date'])) {
            return redirect()->route('booking.index')->with('error', 'Сначала выберите транспорт и даты.');
        }

        $motorcycle = Motorcycle::find($session['motorcycle_id']);
        if (! $motorcycle) {
            Session::forget('booking_draft');

            return redirect()->route('booking.index')->with('error', 'Транспорт не найден.');
        }

        $selectedCatalogLocation = $this->catalogLocation->resolve();
        if ($selectedCatalogLocation !== null
            && ! $this->motorcycleLocationCatalog->isMotorcycleVisibleAtLocation($motorcycle, $selectedCatalogLocation)) {
            Session::forget('booking_draft');

            return redirect()->route('booking.index')->with('error', 'Модель недоступна в выбранной точке. Выберите другую локацию или весь каталог.');
        }

        $allowedUnits = $this->activeRentalUnitsForPublicBooking($motorcycle);
        if ($motorcycle->uses_fleet_units && ! empty($session['rental_unit_id'])) {
            $sessionUnit = RentalUnit::find($session['rental_unit_id']);
            if (! $sessionUnit || $sessionUnit->motorcycle_id !== $motorcycle->id || ! $allowedUnits->contains('id', (int) $session['rental_unit_id'])) {
                Session::forget('booking_draft');

                return redirect()->route('booking.index')->with('error', 'Выбранная единица парка недоступна. Оформите бронь заново.');
            }
        }

        $addons = collect();
        foreach ($session['addons'] ?? [] as $addonId => $qty) {
            $addon = Addon::find($addonId);
            if ($addon && $qty > 0) {
                $addons->push((object) ['addon' => $addon, 'quantity' => $qty]);
            }
        }

        $tenant = currentTenant();
        $preferredChannelFormOptions = $tenant !== null
            ? app(TenantContactChannelsStore::class)->publicFormPreferredOptions($tenant->id)
            : [];

        return view('tenant.booking.checkout', [
            'motorcycle' => $motorcycle,
            'draft' => $session,
            'addons' => $addons,
            'preferredChannelFormOptions' => $preferredChannelFormOptions,
        ]);
    }

    /**
     * Store checkout - create booking.
     */
    public function storeCheckout(StorePublicBookingCheckoutRequest $request, VisitorContactPayloadBuilder $contactPayloadBuilder)
    {
        $session = Session::get('booking_draft', []);

        if (empty($session['motorcycle_id']) || empty($session['start_date']) || empty($session['end_date'])) {
            return redirect()->route('booking.index')->with('error', 'Сессия истекла. Выберите даты заново.');
        }

        $validated = $request->validated();
        $tenant = currentTenant();
        abort_if($tenant === null, 404);

        $contact = $contactPayloadBuilder->build($tenant->id, [
            'phone' => $validated['phone'],
            'preferred_contact_channel' => $validated['preferred_contact_channel'],
            'preferred_contact_value' => $validated['preferred_contact_value'] ?? null,
        ]);

        $motorcycle = Motorcycle::findOrFail($session['motorcycle_id']);

        $selectedCatalogLocation = $this->catalogLocation->resolve();
        if ($selectedCatalogLocation !== null
            && ! $this->motorcycleLocationCatalog->isMotorcycleVisibleAtLocation($motorcycle, $selectedCatalogLocation)) {
            Session::forget('booking_draft');

            return redirect()->route('booking.index')->with('error', 'Модель недоступна в выбранной точке.');
        }

        $allowedUnits = $this->activeRentalUnitsForPublicBooking($motorcycle);
        $rentalUnit = null;
        if ($motorcycle->uses_fleet_units && isset($session['rental_unit_id'])) {
            $rentalUnit = RentalUnit::find($session['rental_unit_id']);
            if ($rentalUnit !== null) {
                if ($rentalUnit->motorcycle_id !== $motorcycle->id || ! $allowedUnits->contains('id', $rentalUnit->id)) {
                    Session::forget('booking_draft');

                    return redirect()->route('booking.index')->with('error', 'Единица парка недоступна в выбранной точке.');
                }
            }
        }

        $start = Carbon::parse($session['start_date'])->startOfDay();
        $end = Carbon::parse($session['end_date'])->endOfDay();

        $available = true;
        if ($rentalUnit) {
            $available = $this->availabilityService->isAvailable($rentalUnit, $start, $end);
        } else {
            $available = $this->bookingService->isAvailableForMotorcycle($motorcycle->id, $session['start_date'], $session['end_date']);
        }

        if (! $available) {
            return redirect()->route('booking.index')->with('error', 'Выбранные даты больше недоступны.');
        }

        $addonIds = [];
        foreach ($session['addons'] ?? [] as $addonId => $qty) {
            if (is_numeric($qty) && $qty > 0) {
                $addonIds[$addonId] = (int) $qty;
            }
        }

        $target = $rentalUnit ?? $motorcycle;
        $pricing = $this->pricingService->calculatePrice($target, $start, $end, 'daily', $addonIds);

        $booking = $this->bookingService->createPublicBooking([
            'tenant_id' => $tenant->id,
            'motorcycle_id' => $motorcycle->id,
            'rental_unit_id' => $rentalUnit?->id,
            'start_date' => $session['start_date'],
            'end_date' => $session['end_date'],
            'start_at' => $start,
            'end_at' => $end,
            'customer_name' => $validated['customer_name'],
            'phone' => $validated['phone'],
            'preferred_contact_channel' => $contact['preferred_contact_channel'],
            'preferred_contact_value' => $contact['preferred_contact_value'],
            'visitor_contact_channels_json' => $contact['visitor_contact_channels_json'],
            'email' => $validated['email'] ?? null,
            'customer_comment' => $validated['customer_comment'] ?? null,
            'source' => 'public_booking',
            'pricing_snapshot' => $pricing['pricing_snapshot'] ?? $pricing,
            'total_price' => $pricing['total'],
            'deposit_amount' => $pricing['deposit'] ?? 0,
            'addons' => $addonIds,
        ]);

        Session::forget('booking_draft');

        return redirect()->route('booking.thank-you', ['booking' => $booking->booking_number])
            ->with('booking', $booking);
    }

    /**
     * Thank you page after successful booking.
     */
    public function thankYou(Request $request, ?string $booking = null): View
    {
        $bookingModel = $request->session()->get('booking');
        if (! $bookingModel && $booking) {
            $bookingModel = Booking::where('booking_number', $booking)->first();
        }

        return view('tenant.booking.thank-you', ['booking' => $bookingModel]);
    }

    /**
     * Store booking draft in session (from vehicle page).
     */
    public function storeDraft(Request $request)
    {
        $validated = $request->validate([
            'motorcycle_id' => ['required', 'exists:motorcycles,id'],
            'rental_unit_id' => ['nullable', 'exists:rental_units,id'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'addons' => ['nullable', 'array'],
            'addons.*' => ['integer', 'min:0'],
        ]);

        $motorcycle = Motorcycle::findOrFail($validated['motorcycle_id']);

        if ($response = $this->assertMotorcycleAllowedForPublicBooking($motorcycle)) {
            return $response;
        }

        $rentalUnits = $this->activeRentalUnitsForPublicBooking($motorcycle);
        $resolved = $this->resolveRentalUnitForCalculateOrDraft($motorcycle, $rentalUnits, $validated['rental_unit_id'] ?? null);
        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }
        $unit = $resolved;

        $start = Carbon::parse($validated['start_date'])->startOfDay();
        $end = Carbon::parse($validated['end_date'])->endOfDay();

        $available = true;
        if ($unit) {
            $available = $this->availabilityService->isAvailable($unit, $start, $end);
        } else {
            $available = $this->bookingService->isAvailableForMotorcycle($motorcycle->id, $validated['start_date'], $validated['end_date']);
        }

        if (! $available) {
            return response()->json([
                'success' => false,
                'message' => 'Выбранные даты заняты.',
            ], 422);
        }

        Session::put('booking_draft', [
            'motorcycle_id' => $validated['motorcycle_id'],
            'rental_unit_id' => $motorcycle->uses_fleet_units ? ($validated['rental_unit_id'] ?? null) : null,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'addons' => $validated['addons'] ?? [],
        ]);

        return response()->json([
            'success' => true,
            'redirect' => route('booking.checkout'),
        ]);
    }

    /**
     * @return Collection<int, RentalUnit>
     */
    private function activeRentalUnitsForPublicBooking(Motorcycle $motorcycle): Collection
    {
        $loc = $this->catalogLocation->resolve();

        return $this->motorcycleLocationCatalog
            ->rentalUnitsQueryForPublic($motorcycle, $loc)
            ->orderBy('id')
            ->get();
    }

    private function assertMotorcycleAllowedForPublicBooking(Motorcycle $motorcycle): ?JsonResponse
    {
        $loc = $this->catalogLocation->resolve();
        if ($loc !== null && ! $this->motorcycleLocationCatalog->isMotorcycleVisibleAtLocation($motorcycle, $loc)) {
            return response()->json([
                'available' => false,
                'message' => 'Модель недоступна в выбранной точке. Смените локацию или откройте весь каталог.',
            ], 422);
        }

        return null;
    }

    /**
     * @param  Collection<int, RentalUnit>  $allowedUnits
     */
    private function resolveRentalUnitForCalculateOrDraft(Motorcycle $motorcycle, Collection $allowedUnits, mixed $rentalUnitId): RentalUnit|JsonResponse|null
    {
        if (! $motorcycle->uses_fleet_units) {
            return null;
        }

        if ($allowedUnits->isEmpty()) {
            return response()->json([
                'available' => false,
                'message' => 'Нет доступных единиц парка для выбранной точки.',
            ], 422);
        }

        if ($rentalUnitId) {
            $rentalUnit = RentalUnit::find($rentalUnitId);
            if (! $rentalUnit || $rentalUnit->motorcycle_id !== $motorcycle->id) {
                return response()->json([
                    'available' => false,
                    'message' => 'Указана некорректная единица парка.',
                ], 422);
            }
            if (! $allowedUnits->contains('id', $rentalUnit->id)) {
                return response()->json([
                    'available' => false,
                    'message' => 'Эта единица недоступна в выбранной точке.',
                ], 422);
            }

            return $rentalUnit;
        }

        return $allowedUnits->first();
    }
}
