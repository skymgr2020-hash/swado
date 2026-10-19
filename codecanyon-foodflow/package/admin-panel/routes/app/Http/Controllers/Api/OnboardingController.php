<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\Cuisine;
use App\Models\DeliveryArea;
use App\Models\PartnerApplication;
use App\Models\Restaurant;
use App\Models\User;
use App\Rules\UniqueUserContactForRole;
use App\Services\DeliveryAreaResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class OnboardingController extends Controller
{
    public function meta(Request $request)
    {
        $user = $request->user();

        return response()->json(['success' => true, 'data' => [
            'capabilities' => [
                'restaurants' => $this->canCreateRestaurants($user),
                'drivers' => $this->canCreateDrivers($user),
            ],
            'roles' => $user->roles()->pluck('name')->values(),
            'restaurant_types' => Restaurant::validServiceTypes(),
            'cuisines' => Cuisine::query()->where('is_active', true)->orderBy('display_order')->get(['id', 'name']),
            'delivery_areas' => DeliveryArea::query()->active()->orderBy('name')->get(['id', 'name', 'area_type', 'description', 'latitude', 'longitude', 'radius_km']),
            'vehicle_types' => ['bike', 'ev_scooter'],
            'fuel_types' => ['petrol', 'electric'],
            'genders' => ['male', 'female', 'other'],
            'tax_deductee_types' => ['individual', 'company'],
            'week_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'],
            'restaurant_document_types' => ['logo_image', 'banner_image', 'menu_photo', 'fssai_license'],
            'driver_document_types' => ['profile_photo', 'vehicle_image', 'license_document', 'aadhar_card', 'pan_card', 'vehicle_rc', 'insurance_document'],
            'defaults' => [
                'currency_symbol' => AppSetting::sanitizedCurrencySymbol(),
                'country_code' => AppSetting::getValue('country_code', 'IN'),
                'mobile_country_code' => AppSetting::getValue('default_mobile_country_code', '+91'),
                'timezone' => config('app.timezone', 'Asia/Kolkata'),
            ],
        ]]);
    }

    public function dashboard(Request $request)
    {
        $today = now()->startOfDay();
        $month = now()->startOfMonth();
        $restaurantApps = PartnerApplication::where('partner_type', 'restaurant');
        $driverApps = PartnerApplication::where('partner_type', 'driver');
        $drivers = User::role('delivery_partner');

        return response()->json(['success' => true, 'data' => [
            'kpis' => [
                ['key' => 'restaurant_applications', 'label' => 'Restaurant applications', 'value' => (clone $restaurantApps)->count(), 'delta' => (clone $restaurantApps)->where('created_at', '>=', $month)->count(), 'tone' => 'mint'],
                ['key' => 'driver_applications', 'label' => 'Driver applications', 'value' => (clone $driverApps)->count(), 'delta' => (clone $driverApps)->where('created_at', '>=', $month)->count(), 'tone' => 'sky'],
                ['key' => 'pending_reviews', 'label' => 'Pending reviews', 'value' => PartnerApplication::where('status', 'pending')->count(), 'delta' => PartnerApplication::where('status', 'pending')->where('created_at', '>=', $today)->count(), 'tone' => 'amber'],
                ['key' => 'live_partners', 'label' => 'Live partners', 'value' => Restaurant::where('is_verified', true)->count() + (clone $drivers)->where('is_active', true)->count(), 'delta' => Restaurant::where('created_at', '>=', $month)->count() + User::role('delivery_partner')->where('created_at', '>=', $month)->count(), 'tone' => 'rose'],
            ],
            'status_breakdown' => [
                'restaurants' => $this->statusCounts('restaurant'),
                'drivers' => $this->statusCounts('driver'),
            ],
            'operational' => [
                'restaurants_total' => Restaurant::count(),
                'restaurants_verified' => Restaurant::where('is_verified', true)->count(),
                'restaurants_open' => Restaurant::where('is_open', true)->count(),
                'drivers_total' => User::role('delivery_partner')->count(),
                'drivers_active' => User::role('delivery_partner')->where('is_active', true)->count(),
                'submitted_today' => PartnerApplication::where('created_at', '>=', $today)->count(),
            ],
            'recent' => $this->recentApplications(8),
        ]]);
    }

    public function pipeline(Request $request)
    {
        $validated = $request->validate([
            'type' => ['nullable', 'in:restaurant,driver'],
            'status' => ['nullable', 'in:pending,approved,rejected'],
            'search' => ['nullable', 'string', 'max:120'],
        ]);

        $query = PartnerApplication::query()->with('reviewer:id,name')->latest();
        if (! empty($validated['type'])) {
            $query->where('partner_type', $validated['type']);
        }
        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }
        if (! empty($validated['search'])) {
            $search = '%' . $validated['search'] . '%';
            $query->where(function ($builder) use ($search) {
                $builder->where('application_number', 'like', $search)
                    ->orWhere('business_name', 'like', $search)
                    ->orWhere('full_name', 'like', $search)
                    ->orWhere('business_email', 'like', $search)
                    ->orWhere('email', 'like', $search)
                    ->orWhere('business_phone', 'like', $search)
                    ->orWhere('phone', 'like', $search);
            });
        }

        return response()->json(['success' => true, 'data' => $query->limit(50)->get()->map(fn ($application) => $this->applicationPayload($application))]);
    }

    public function storeRestaurant(Request $request)
    {
        abort_unless($this->canCreateRestaurants($request->user()), 403, 'Restaurant onboarding access is required.');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:restaurants,email'],
            'phone' => ['required', 'string', 'max:40'],
            'description' => ['nullable', 'string', 'max:2000'],
            'address' => ['required', 'string', 'max:1000'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'state_code' => ['nullable', 'string', 'max:2'],
            'pincode' => ['required', 'string', 'max:12'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'delivery_radius' => ['required', 'numeric', 'min:0', 'max:100'],
            'restaurant_type' => ['required', 'in:' . implode(',', Restaurant::validServiceTypes())],
            'cuisine' => ['nullable', 'array'],
            'cuisine.*' => ['string', 'max:120'],
            'is_pure_veg' => ['nullable', 'boolean'],
            'is_gst_registered' => ['nullable', 'boolean'],
            'gstin' => ['nullable', 'string', 'max:20'],
            'pan' => ['nullable', 'string', 'max:15'],
            'fssai_license_number' => ['nullable', 'string', 'max:64'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'delivery_fee' => ['nullable', 'numeric', 'min:0'],
            'commission_calculation_type' => ['nullable', 'in:global,percentage,fixed'],
            'commission_rate' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'delivery_time' => ['nullable', 'integer', 'min:1', 'max:240'],
            'order_lead_time' => ['nullable', 'integer', 'min:0', 'max:240'],
            'open_time' => ['nullable', 'date_format:H:i'],
            'close_time' => ['nullable', 'date_format:H:i'],
            'owner_name' => ['required', 'string', 'max:255'],
            'owner_email' => ['required', 'email', UniqueUserContactForRole::email('restaurant_owner')],
            'owner_phone' => ['required', 'string', 'max:40', UniqueUserContactForRole::phone('restaurant_owner')],
            'owner_password' => ['required', 'string', 'min:8', 'confirmed'],
            'account_holder_name' => ['nullable', 'string', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:64'],
            'ifsc_code' => ['nullable', 'string', 'max:32'],
            'upi_id' => ['nullable', 'string', 'max:255'],
            'background_location_enabled' => ['nullable', 'boolean'],
            'notification_permission_enabled' => ['nullable', 'boolean'],
            'document_status' => ['nullable', 'array'],
            'document_status.*' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $result = DB::transaction(function () use ($validated, $request) {
            $owner = User::create([
                'name' => $validated['owner_name'],
                'email' => $validated['owner_email'],
                'phone' => $validated['owner_phone'],
                'password' => Hash::make($validated['owner_password']),
                'is_active' => true,
                'email_verified_at' => now(),
                'account_holder_name' => $validated['account_holder_name'] ?? null,
                'bank_name' => $validated['bank_name'] ?? null,
                'account_number' => $validated['account_number'] ?? null,
                'ifsc_code' => $validated['ifsc_code'] ?? null,
                'routing_code' => $validated['ifsc_code'] ?? null,
                'upi_id' => $validated['upi_id'] ?? null,
            ]);
            $owner->assignRole(Role::findOrCreate('restaurant_owner', 'web'));

            $restaurant = Restaurant::create([
                'owner_id' => $owner->id,
                'name' => $validated['name'],
                'slug' => $this->uniqueRestaurantSlug($validated['name']),
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'description' => $validated['description'] ?? null,
                'address' => $validated['address'],
                'city' => $validated['city'],
                'state' => $validated['state'],
                'state_code' => $validated['state_code'] ?? null,
                'pincode' => $validated['pincode'],
                'latitude' => $validated['latitude'] ?? 0,
                'longitude' => $validated['longitude'] ?? 0,
                'delivery_radius' => $validated['delivery_radius'],
                'restaurant_type' => $validated['restaurant_type'],
                'cuisine' => $validated['cuisine'] ?? [],
                'is_pure_veg' => (bool) ($validated['is_pure_veg'] ?? false),
                'is_gst_registered' => (bool) ($validated['is_gst_registered'] ?? false),
                'gstin' => $validated['gstin'] ?? null,
                'pan' => $validated['pan'] ?? null,
                'fssai_license_number' => $validated['fssai_license_number'] ?? null,
                'tax_deductee_type' => (bool) ($validated['is_gst_registered'] ?? false) ? 'company' : 'individual',
                'min_order_amount' => $validated['min_order_amount'] ?? 0,
                'delivery_fee' => $validated['delivery_fee'] ?? 0,
                'commission_calculation_type' => $validated['commission_calculation_type'] ?? 'global',
                'commission_rate' => ($validated['commission_calculation_type'] ?? 'global') === 'global' ? null : ($validated['commission_rate'] ?? null),
                'delivery_time' => $validated['delivery_time'] ?? 30,
                'order_lead_time' => $validated['order_lead_time'] ?? 0,
                'open_time' => $validated['open_time'] ?? '09:00',
                'close_time' => $validated['close_time'] ?? '22:00',
                'weekly_timings' => $this->weeklyTimings($validated['open_time'] ?? '09:00', $validated['close_time'] ?? '22:00', $validated['weekly_off'] ?? []),
                'timezone' => config('app.timezone', 'Asia/Kolkata'),
                'is_open' => false,
                'is_verified' => false,
            ]);

            $application = PartnerApplication::create([
                'application_number' => $this->applicationNumber('restaurant'),
                'partner_type' => 'restaurant',
                'business_name' => $restaurant->name,
                'business_email' => $restaurant->email,
                'business_phone' => $restaurant->phone,
                'contact_name' => $owner->name,
                'contact_designation' => 'Owner',
                'contact_email' => $owner->email,
                'contact_phone' => $owner->phone,
                'city' => $restaurant->city,
                'address' => $restaurant->address,
                'pincode' => $restaurant->pincode,
                'cuisine' => $restaurant->cuisine,
                'is_pure_veg' => $restaurant->is_pure_veg,
                'gstin_number' => $restaurant->gstin,
                'pan_number' => $restaurant->pan,
                'fssai_license' => $restaurant->fssai_license_number,
                'bank_details' => $this->bankDetails($validated),
                'status' => 'approved',
                'admin_notes' => $validated['notes'] ?? 'Created from onboarding app.',
                'reviewed_at' => now(),
                'reviewed_by' => $request->user()->id,
                'onboarding_meta' => ['source' => 'onboarding_app', 'created_by' => $request->user()->id, 'restaurant_id' => $restaurant->id, 'owner_id' => $owner->id, 'weekly_off' => $validated['weekly_off'] ?? [], 'document_status' => $validated['document_status'] ?? []],
            ]);

            return compact('application', 'restaurant');
        });

        return response()->json(['success' => true, 'message' => 'Restaurant onboarded successfully.', 'data' => [
            'application' => $this->applicationPayload($result['application']->fresh('reviewer')),
            'restaurant' => $this->restaurantPayload($result['restaurant']->fresh('owner')),
        ]], 201);
    }

    public function storeDriver(Request $request)
    {
        abort_unless($this->canCreateDrivers($request->user()), 403, 'Driver onboarding access is required.');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', UniqueUserContactForRole::email('delivery_partner')],
            'phone' => ['required', 'string', 'max:40', UniqueUserContactForRole::phone('delivery_partner')],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'vehicle_type' => ['required', 'string', 'max:80'],
            'vehicle_number' => ['required', 'string', 'max:80'],
            'license_number' => ['required', 'string', 'max:120'],
            'pan' => ['nullable', 'string', 'max:15'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'in:male,female,other'],
            'landmark' => ['nullable', 'string', 'max:255'],
            'vehicle_model' => ['nullable', 'string', 'max:120'],
            'fuel_type' => ['nullable', 'in:petrol,electric'],
            'tax_deductee_type' => ['nullable', 'in:individual,company'],
            'earning_mode' => ['nullable', 'in:commission,salary'],
            'monthly_salary' => ['nullable', 'numeric', 'min:0'],
            'salary_effective_from' => ['nullable', 'date'],
            'address' => ['nullable', 'string', 'max:1000'],
            'city' => ['nullable', 'string', 'max:100'],
            'pincode' => ['nullable', 'string', 'max:12'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'area_id' => ['nullable', 'exists:delivery_areas,id'],
            'max_active_orders' => ['nullable', 'integer', 'min:1', 'max:50'],
            'account_holder_name' => ['nullable', 'string', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:64'],
            'ifsc_code' => ['nullable', 'string', 'max:32'],
            'upi_id' => ['nullable', 'string', 'max:255'],
            'background_location_enabled' => ['nullable', 'boolean'],
            'notification_permission_enabled' => ['nullable', 'boolean'],
            'document_status' => ['nullable', 'array'],
            'document_status.*' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $result = DB::transaction(function () use ($validated, $request) {
            $areaId = $validated['area_id'] ?? $this->resolveDeliveryAreaId($validated['latitude'] ?? null, $validated['longitude'] ?? null);
            $driver = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => Hash::make($validated['password']),
                'is_active' => true,
                'email_verified_at' => now(),
                'vehicle_type' => $validated['vehicle_type'],
                'vehicle_number' => $validated['vehicle_number'],
                'license_number' => $validated['license_number'],
                'pan' => $validated['pan'] ?? null,
                'tax_deductee_type' => $validated['tax_deductee_type'] ?? 'individual',
                'earning_mode' => $validated['earning_mode'] ?? 'commission',
                'monthly_salary' => $validated['monthly_salary'] ?? null,
                'salary_effective_from' => $validated['salary_effective_from'] ?? null,
                'address' => $validated['address'] ?? null,
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'delivery_area_id' => $areaId,
                'max_active_orders' => $validated['max_active_orders'] ?? null,
                'account_holder_name' => $validated['account_holder_name'] ?? null,
                'bank_name' => $validated['bank_name'] ?? null,
                'account_number' => $validated['account_number'] ?? null,
                'ifsc_code' => $validated['ifsc_code'] ?? null,
                'routing_code' => $validated['ifsc_code'] ?? null,
                'upi_id' => $validated['upi_id'] ?? null,
            ]);
            $driver->assignRole(Role::findOrCreate('delivery_partner', 'web'));

            $application = PartnerApplication::create([
                'application_number' => $this->applicationNumber('driver'),
                'partner_type' => 'driver',
                'full_name' => $driver->name,
                'email' => $driver->email,
                'phone' => $driver->phone,
                'city' => $validated['city'] ?? null,
                'address' => $driver->address,
                'area_id' => $areaId,
                'latitude' => $driver->latitude,
                'longitude' => $driver->longitude,
                'pincode' => $validated['pincode'] ?? null,
                'vehicle_type' => $driver->vehicle_type,
                'vehicle_number' => $driver->vehicle_number,
                'license_number' => $driver->license_number,
                'pan_number' => $driver->pan,
                'bank_details' => $this->bankDetails($validated),
                'status' => 'approved',
                'admin_notes' => $validated['notes'] ?? 'Created from onboarding app.',
                'reviewed_at' => now(),
                'reviewed_by' => $request->user()->id,
                'dob' => $validated['date_of_birth'] ?? null,
                'document_verification' => $validated['document_status'] ?? [],
                'onboarding_meta' => ['source' => 'onboarding_app', 'created_by' => $request->user()->id, 'driver_id' => $driver->id, 'date_of_birth' => $validated['date_of_birth'] ?? null, 'gender' => $validated['gender'] ?? null, 'landmark' => $validated['landmark'] ?? null, 'vehicle_model' => $validated['vehicle_model'] ?? null, 'fuel_type' => $validated['fuel_type'] ?? null, 'background_location_enabled' => (bool) ($validated['background_location_enabled'] ?? false), 'notification_permission_enabled' => (bool) ($validated['notification_permission_enabled'] ?? false), 'document_status' => $validated['document_status'] ?? []],
            ]);

            return compact('application', 'driver');
        });

        return response()->json(['success' => true, 'message' => 'Driver onboarded successfully.', 'data' => [
            'application' => $this->applicationPayload($result['application']->fresh('reviewer')),
            'driver' => $this->driverPayload($result['driver']->fresh('deliveryArea')),
        ]], 201);
    }

    private function statusCounts(string $type): array
    {
        return collect(['pending', 'approved', 'rejected'])->mapWithKeys(fn ($status) => [$status => PartnerApplication::where('partner_type', $type)->where('status', $status)->count()])->all();
    }

    private function recentApplications(int $limit): array
    {
        return PartnerApplication::query()->with('reviewer:id,name')->latest()->limit($limit)->get()->map(fn ($application) => $this->applicationPayload($application))->all();
    }

    private function applicationPayload(PartnerApplication $application): array
    {
        return [
            'id' => $application->id,
            'application_number' => $application->application_number,
            'type' => $application->partner_type,
            'status' => $application->status,
            'name' => $application->partner_type === 'restaurant' ? $application->business_name : $application->full_name,
            'email' => $application->partner_type === 'restaurant' ? $application->business_email : $application->email,
            'phone' => $application->partner_type === 'restaurant' ? $application->business_phone : $application->phone,
            'city' => $application->city,
            'reviewer' => $application->reviewer?->only(['id', 'name']),
            'reviewed_at' => optional($application->reviewed_at)?->toIso8601String(),
            'created_at' => optional($application->created_at)?->toIso8601String(),
            'meta' => $application->onboarding_meta ?: [],
        ];
    }

    private function restaurantPayload(Restaurant $restaurant): array
    {
        return ['id' => $restaurant->id, 'code' => $restaurant->code, 'name' => $restaurant->name, 'email' => $restaurant->email, 'phone' => $restaurant->phone, 'city' => $restaurant->city, 'is_verified' => (bool) $restaurant->is_verified, 'is_open' => (bool) $restaurant->is_open, 'owner' => $restaurant->owner?->only(['id', 'name', 'email', 'phone'])];
    }

    private function driverPayload(User $driver): array
    {
        return ['id' => $driver->id, 'code' => $driver->driver_code, 'name' => $driver->name, 'email' => $driver->email, 'phone' => $driver->phone, 'vehicle_type' => $driver->vehicle_type, 'vehicle_number' => $driver->vehicle_number, 'is_active' => (bool) $driver->is_active, 'delivery_area' => $driver->deliveryArea?->only(['id', 'name'])];
    }

    private function uniqueRestaurantSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'restaurant';
        $slug = $base;
        $counter = 1;
        while (Restaurant::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $counter++;
        }
        return $slug;
    }

    private function applicationNumber(string $type): string
    {
        $prefix = $type === 'driver' ? 'DRVAPP' : 'RESAPP';
        do {
            $number = $prefix . now()->format('ymd') . strtoupper(Str::random(5));
        } while (PartnerApplication::where('application_number', $number)->exists());
        return $number;
    }

    private function weeklyTimings(string $openTime, string $closeTime, array $weeklyOff = []): array
    {
        $closed = collect($weeklyOff)->map(fn ($day) => strtolower((string) $day))->all();

        return collect(Restaurant::getDefaultWeeklyTimings())->mapWithKeys(function ($timing, $day) use ($openTime, $closeTime, $closed) {
            return [$day => array_merge($timing, [
                'is_open' => ! in_array($day, $closed, true),
                'open_time' => $openTime,
                'close_time' => $closeTime,
            ])];
        })->all();
    }

    private function bankDetails(array $data): ?string
    {
        $details = collect(['account_holder_name' => $data['account_holder_name'] ?? null, 'bank_name' => $data['bank_name'] ?? null, 'account_number' => $data['account_number'] ?? null, 'ifsc_code' => $data['ifsc_code'] ?? null, 'upi_id' => $data['upi_id'] ?? null])->filter(fn ($value) => filled($value));
        return $details->isEmpty() ? null : $details->toJson();
    }

    private function resolveDeliveryAreaId($latitude, $longitude): ?int
    {
        if ($latitude === null || $longitude === null || $latitude === '' || $longitude === '') {
            return null;
        }
        return app(DeliveryAreaResolver::class)->resolve((float) $latitude, (float) $longitude)?->id;
    }

    private function canCreateRestaurants(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'marketing_manager']);
    }

    private function canCreateDrivers(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'fleet_manager']);
    }
}




