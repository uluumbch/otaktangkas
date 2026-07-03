# Phase 6: Monetization Strategy

## Overview
Complete monetization implementation for OtakTangkas, focused on ad-based revenue with optional premium subscriptions. Optimized for the Indonesian market with non-intrusive ad placement and local payment considerations.

## Table of Contents
- [Ad Network Setup](#ad-network-setup)
- [Ad Placement Strategy](#ad-placement-strategy)
- [Ad Component Implementation](#ad-component-implementation)
- [Ad Tracking and Analytics](#ad-tracking-and-analytics)
- [Premium Subscription](#premium-subscription)
- [Payment Integration](#payment-integration)
- [Revenue Tracking](#revenue-tracking)
- [Ad-Free Experience](#ad-free-experience)
- [Indonesian Payment Methods](#indonesian-payment-methods)

---

## Ad Network Setup

### Recommended Ad Networks for Indonesia

#### 1. PropellerAds
**Best for:** Pop-unders, Interstitials, Native Ads
**eCPM:** $0.50 - $2.00 (Indonesia)
**Payment:** Net-30, PayPal, Wire Transfer
**Minimum:** No minimum traffic

```env
PROPELLERADS_ZONE_ID=your_zone_id
PROPELLERADS_SITE_ID=your_site_id
```

#### 2. Adsterra
**Best for:** Pop-unders, Banner Ads
**eCPM:** $0.40 - $1.80 (Indonesia)
**Payment:** Net-15, Multiple methods
**Minimum:** No minimum

```env
ADSTERRA_SITE_ID=your_site_id
ADSTERRA_BANNER_ID=your_banner_id
```

#### 3. Google AdSense (Backup)
**Best for:** Display Ads, Auto Ads
**eCPM:** Variable
**Payment:** Net-21, Bank Transfer
**Minimum:** $100 threshold

```env
GOOGLE_ADSENSE_CLIENT=ca-pub-xxxxxxxxxxxxxxxx
GOOGLE_ADSENSE_SLOT=xxxxxxxxxx
```

### Ad Network Configuration

```php
<?php
// config/ads.php

return [
    'enabled' => env('ADS_ENABLED', true),
    
    'networks' => [
        'propellerads' => [
            'enabled' => env('PROPELLERADS_ENABLED', true),
            'zone_id' => env('PROPELLERADS_ZONE_ID'),
            'site_id' => env('PROPELLERADS_SITE_ID'),
        ],
        'adsterra' => [
            'enabled' => env('ADSTERRA_ENABLED', true),
            'site_id' => env('ADSTERRA_SITE_ID'),
            'banner_id' => env('ADSTERRA_BANNER_ID'),
        ],
        'adsense' => [
            'enabled' => env('ADSENSE_ENABLED', false),
            'client' => env('GOOGLE_ADSENSE_CLIENT'),
            'slot' => env('GOOGLE_ADSENSE_SLOT'),
        ],
    ],

    'placements' => [
        'homepage_banner' => [
            'network' => 'adsterra',
            'type' => 'banner',
            'frequency' => 'always',
        ],
        'game_end_interstitial' => [
            'network' => 'propellerads',
            'type' => 'interstitial',
            'frequency' => 'every_3_games',
        ],
        'dashboard_native' => [
            'network' => 'adsense',
            'type' => 'native',
            'frequency' => 'always',
        ],
        'leaderboard_banner' => [
            'network' => 'adsterra',
            'type' => 'banner',
            'frequency' => 'always',
        ],
    ],

    'frequency_caps' => [
        'interstitial' => [
            'max_per_session' => 3,
            'min_time_between' => 300, // 5 minutes in seconds
        ],
        'rewarded' => [
            'max_per_day' => 10,
        ],
    ],

    'skip_ads_for_premium' => true,
];
```

---

## Ad Placement Strategy

### Ad Placement Guidelines

#### ✅ Good Placement Locations
1. **After Game Completion** - Natural break point
2. **Between Game Modes** - Transition points
3. **Leaderboard Page** - Static content viewing
4. **Profile/Stats Page** - Low interaction areas
5. **After Daily Puzzle** - Reward moment

#### ❌ Avoid These Locations
1. During active gameplay
2. During question answering
3. During matchmaking
4. On authentication pages
5. During loading states

### Placement Map

```
┌─────────────────────────────────────┐
│          HOMEPAGE                   │
│  ┌─────────────────────┐           │
│  │   Banner Ad (728x90) │           │
│  └─────────────────────┘           │
│                                     │
│  Dashboard Content                  │
│                                     │
│  ┌─────────────────────┐           │
│  │   Native Ad (300x250)│           │
│  └─────────────────────┘           │
└─────────────────────────────────────┘

┌─────────────────────────────────────┐
│       GAME COMPLETION               │
│                                     │
│    ✅ YOU WON!                      │
│    +50 XP  +50 Coins               │
│                                     │
│  [Continue] [Play Again]           │
│                                     │
│  ► Interstitial Ad (if frequency met)│
└─────────────────────────────────────┘

┌─────────────────────────────────────┐
│       LEADERBOARD                   │
│  ┌─────────────────────┐           │
│  │   Banner Ad (320x50) │           │
│  └─────────────────────┘           │
│                                     │
│  Rankings List                      │
│                                     │
│  ┌─────────────────────┐           │
│  │   Banner Ad (300x250)│           │
│  └─────────────────────┘           │
└─────────────────────────────────────┘
```

### Frequency Control

```php
<?php
// app/Services/AdFrequencyService.php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class AdFrequencyService
{
    /**
     * Check if user should see an ad
     */
    public function shouldShowAd(string $placement, $userId): bool
    {
        // Skip if premium user
        if ($this->isPremiumUser($userId)) {
            return false;
        }

        // Check frequency cap
        $config = config("ads.placements.{$placement}");
        if (!$config) {
            return false;
        }

        switch ($config['frequency']) {
            case 'always':
                return true;
            
            case 'every_3_games':
                return $this->checkGameFrequency($userId, 3);
            
            case 'every_5_games':
                return $this->checkGameFrequency($userId, 5);
            
            case 'once_per_session':
                return $this->checkSessionFrequency($userId, $placement);
            
            default:
                return true;
        }
    }

    /**
     * Check game-based frequency
     */
    protected function checkGameFrequency($userId, int $interval): bool
    {
        $key = "ad_game_count:{$userId}";
        $count = Cache::get($key, 0);
        
        $count++;
        Cache::put($key, $count, 3600); // 1 hour TTL

        return $count % $interval === 0;
    }

    /**
     * Check session-based frequency
     */
    protected function checkSessionFrequency($userId, string $placement): bool
    {
        $key = "ad_shown:{$userId}:{$placement}";
        
        if (Cache::has($key)) {
            return false;
        }

        Cache::put($key, true, 3600); // 1 hour
        return true;
    }

    /**
     * Check if user is premium
     */
    protected function isPremiumUser($userId): bool
    {
        $user = \App\Models\User::find($userId);
        return $user && $user->is_premium;
    }

    /**
     * Record ad impression
     */
    public function recordImpression($userId, string $placement, string $network): void
    {
        \App\Models\AdImpression::create([
            'user_id' => $userId,
            'placement' => $placement,
            'network' => $network,
            'date' => now()->toDateString(),
        ]);
    }
}
```

---

## Ad Component Implementation

### Blade Components

#### Banner Ad Component

```blade
<!-- resources/views/components/ads/banner.blade.php -->
@props(['placement' => 'default', 'size' => '728x90'])

@php
    $adService = app(\App\Services\AdFrequencyService::class);
    $shouldShow = $adService->shouldShowAd($placement, auth()->id());
@endphp

@if($shouldShow && config('ads.enabled'))
    <div class="ad-container ad-banner my-4" data-placement="{{ $placement }}">
        @if(config('ads.networks.adsterra.enabled'))
            <!-- Adsterra Banner -->
            <div class="text-center">
                <script type="text/javascript">
                    atOptions = {
                        'key' : '{{ config('ads.networks.adsterra.banner_id') }}',
                        'format' : 'iframe',
                        'height' : {{ explode('x', $size)[1] }},
                        'width' : {{ explode('x', $size)[0] }},
                        'params' : {}
                    };
                </script>
                <script type="text/javascript" src="//www.highperformanceformat.com/{{ config('ads.networks.adsterra.banner_id') }}/invoke.js"></script>
            </div>
        @elseif(config('ads.networks.adsense.enabled'))
            <!-- Google AdSense -->
            <ins class="adsbygoogle"
                 style="display:inline-block;width:{{ explode('x', $size)[0] }}px;height:{{ explode('x', $size)[1] }}px"
                 data-ad-client="{{ config('ads.networks.adsense.client') }}"
                 data-ad-slot="{{ config('ads.networks.adsense.slot') }}"></ins>
            <script>
                (adsbygoogle = window.adsbygoogle || []).push({});
            </script>
        @endif
        
        <p class="text-xs text-gray-500 text-center mt-2">Advertisement</p>
    </div>
@endif
```

#### Interstitial Ad Component

```blade
<!-- resources/views/components/ads/interstitial.blade.php -->
@props(['placement' => 'game_end'])

@php
    $adService = app(\App\Services\AdFrequencyService::class);
    $shouldShow = $adService->shouldShowAd($placement, auth()->id());
@endphp

@if($shouldShow && config('ads.enabled'))
    <div x-data="{ 
        show: true,
        countdown: 5,
        init() {
            let interval = setInterval(() => {
                this.countdown--;
                if (this.countdown <= 0) {
                    clearInterval(interval);
                }
            }, 1000);
            
            // Record impression
            fetch('/api/ads/impression', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    placement: '{{ $placement }}',
                    network: 'propellerads'
                })
            });
        }
    }" 
         x-show="show"
         class="fixed inset-0 bg-black bg-opacity-90 z-50 flex items-center justify-center p-4">
        
        <div class="bg-white rounded-xl max-w-2xl w-full p-6 relative">
            <!-- Close Button (appears after countdown) -->
            <button @click="show = false" 
                    x-show="countdown <= 0"
                    x-transition
                    class="absolute top-4 right-4 w-10 h-10 bg-gray-200 hover:bg-gray-300 rounded-full flex items-center justify-center">
                <i class="fas fa-times"></i>
            </button>

            <!-- Countdown -->
            <div x-show="countdown > 0" class="text-center mb-4">
                <p class="text-gray-600">Iklan akan bisa ditutup dalam <span x-text="countdown" class="font-bold"></span> detik</p>
            </div>

            <!-- Ad Content -->
            <div class="ad-content min-h-[400px] flex items-center justify-center">
                @if(config('ads.networks.propellerads.enabled'))
                    <script type="text/javascript">
                        var ad_idzone = "{{ config('ads.networks.propellerads.zone_id') }}",
                            ad_width = "800",
                            ad_height = "400";
                    </script>
                    <script type="text/javascript" src="https://ads.propellerads.com/js/show_ads.js"></script>
                @endif
            </div>

            <p class="text-xs text-gray-500 text-center mt-4">
                Iklan membantu kami menjaga game ini gratis untuk semua
            </p>
        </div>
    </div>
@endif
```

#### Rewarded Ad Component

```blade
<!-- resources/views/components/ads/rewarded.blade.php -->
@props(['reward' => 10, 'rewardType' => 'coins'])

<div x-data="{ 
    showOffer: true,
    showAd: false,
    completed: false,
    watchAd() {
        this.showOffer = false;
        this.showAd = true;
        
        // Simulate ad watch (replace with actual ad network code)
        setTimeout(() => {
            this.showAd = false;
            this.completed = true;
            this.claimReward();
        }, 5000);
    },
    claimReward() {
        fetch('/api/ads/rewarded/claim', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                reward_type: '{{ $rewardType }}',
                amount: {{ $reward }}
            })
        }).then(response => response.json())
        .then(data => {
            if (data.success) {
                window.dispatchEvent(new CustomEvent('notify', {
                    detail: `Kamu mendapat {{ $reward }} {{ $rewardType }}!`
                }));
            }
        });
    }
}">
    
    <!-- Offer Card -->
    <div x-show="showOffer" class="bg-gradient-to-r from-yellow-400 to-yellow-600 rounded-xl p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-xl font-bold">Tonton Iklan, Dapat Reward!</h3>
                <p class="text-yellow-100 mt-1">Tonton iklan singkat dan dapatkan {{ $reward }} {{ $rewardType }}</p>
            </div>
            <div class="text-center">
                <div class="text-4xl font-bold">{{ $reward }}</div>
                <div class="text-sm">{{ Str::upper($rewardType) }}</div>
            </div>
        </div>
        <button @click="watchAd()" 
                class="mt-4 w-full bg-white text-yellow-600 font-bold py-3 rounded-lg hover:bg-yellow-50 transition-colors">
            <i class="fas fa-play-circle mr-2"></i>
            Tonton Iklan
        </button>
    </div>

    <!-- Ad Player -->
    <div x-show="showAd" 
         x-transition
         class="fixed inset-0 bg-black bg-opacity-90 z-50 flex items-center justify-center">
        <div class="bg-white rounded-xl p-6 max-w-2xl w-full">
            <p class="text-center mb-4 text-gray-600">Menonton iklan...</p>
            <div class="ad-player min-h-[400px] bg-gray-100 rounded-lg flex items-center justify-center">
                <!-- Ad network code here -->
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin text-4xl text-gray-400"></i>
                    <p class="text-gray-600 mt-4">Memuat iklan...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Message -->
    <div x-show="completed" 
         x-transition
         class="bg-green-50 border-2 border-green-500 rounded-xl p-6 text-center">
        <i class="fas fa-check-circle text-green-500 text-5xl mb-3"></i>
        <h3 class="text-xl font-bold text-gray-900">Reward Diterima!</h3>
        <p class="text-gray-600 mt-2">Kamu mendapat {{ $reward }} {{ $rewardType }}</p>
    </div>
</div>
```

### Livewire Ad Manager Component

```php
<?php
// app/Http/Livewire/AdManager.php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Services\AdFrequencyService;

class AdManager extends Component
{
    public $placement;
    public $showAd = false;

    public function mount($placement)
    {
        $this->placement = $placement;
        $adService = app(AdFrequencyService::class);
        $this->showAd = $adService->shouldShowAd($placement, auth()->id());
    }

    public function closeAd()
    {
        $this->showAd = false;
        $this->emit('adClosed');
    }

    public function render()
    {
        return view('livewire.ad-manager');
    }
}
```

---

## Ad Tracking and Analytics

### Ad Impression Model

```php
<?php
// app/Models/AdImpression.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdImpression extends Model
{
    protected $fillable = [
        'user_id',
        'placement',
        'network',
        'date',
        'revenue_estimate',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get impressions by date range
     */
    public static function getByDateRange($startDate, $endDate)
    {
        return static::whereBetween('date', [$startDate, $endDate])
            ->selectRaw('date, placement, network, COUNT(*) as impressions, SUM(revenue_estimate) as revenue')
            ->groupBy('date', 'placement', 'network')
            ->get();
    }
}
```

### Ad Migration

```php
<?php
// database/migrations/2024_xx_xx_create_ad_impressions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('ad_impressions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('placement');
            $table->string('network');
            $table->date('date');
            $table->decimal('revenue_estimate', 10, 4)->default(0);
            $table->timestamps();

            $table->index(['date', 'placement', 'network']);
            $table->index(['user_id', 'date']);
        });

        Schema::create('ad_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('ad_impression_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('placement');
            $table->string('network');
            $table->timestamps();

            $table->index(['created_at', 'network']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('ad_clicks');
        Schema::dropIfExists('ad_impressions');
    }
};
```

### Analytics API Routes

```php
<?php
// routes/api.php

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/ads/impression', [AdController::class, 'recordImpression']);
    Route::post('/ads/click', [AdController::class, 'recordClick']);
    Route::post('/ads/rewarded/claim', [AdController::class, 'claimRewardedAd']);
});
```

### Ad Controller

```php
<?php
// app/Http/Controllers/AdController.php

namespace App\Http\Controllers;

use App\Models\AdImpression;
use App\Models\AdClick;
use App\Services\CoinService;
use Illuminate\Http\Request;

class AdController extends Controller
{
    public function recordImpression(Request $request)
    {
        $request->validate([
            'placement' => 'required|string',
            'network' => 'required|string',
        ]);

        $impression = AdImpression::create([
            'user_id' => auth()->id(),
            'placement' => $request->placement,
            'network' => $request->network,
            'date' => now()->toDateString(),
            'revenue_estimate' => $this->estimateRevenue($request->network),
        ]);

        return response()->json([
            'success' => true,
            'impression_id' => $impression->id,
        ]);
    }

    public function recordClick(Request $request)
    {
        $request->validate([
            'impression_id' => 'nullable|exists:ad_impressions,id',
            'placement' => 'required|string',
            'network' => 'required|string',
        ]);

        AdClick::create([
            'user_id' => auth()->id(),
            'ad_impression_id' => $request->impression_id,
            'placement' => $request->placement,
            'network' => $request->network,
        ]);

        return response()->json(['success' => true]);
    }

    public function claimRewardedAd(Request $request)
    {
        $request->validate([
            'reward_type' => 'required|in:coins,xp',
            'amount' => 'required|integer|min:1|max:50',
        ]);

        // Check daily limit
        $today = now()->toDateString();
        $todayRewards = AdClick::where('user_id', auth()->id())
            ->where('placement', 'rewarded')
            ->whereDate('created_at', $today)
            ->count();

        if ($todayRewards >= config('ads.frequency_caps.rewarded.max_per_day', 10)) {
            return response()->json([
                'success' => false,
                'message' => 'Batas harian tercapai'
            ], 429);
        }

        // Record the ad view
        AdClick::create([
            'user_id' => auth()->id(),
            'placement' => 'rewarded',
            'network' => 'propellerads',
        ]);

        // Award reward
        if ($request->reward_type === 'coins') {
            $coinService = app(CoinService::class);
            $coinService->award(auth()->user(), $request->amount, 'Rewarded ad');
        } else {
            auth()->user()->awardXP($request->amount, 'Rewarded ad');
        }

        return response()->json([
            'success' => true,
            'reward' => [
                'type' => $request->reward_type,
                'amount' => $request->amount,
            ]
        ]);
    }

    protected function estimateRevenue(string $network): float
    {
        // Rough eCPM estimates for Indonesia
        return match($network) {
            'propellerads' => 1.00 / 1000, // $1 CPM
            'adsterra' => 0.80 / 1000,     // $0.80 CPM
            'adsense' => 0.50 / 1000,      // $0.50 CPM
            default => 0.50 / 1000,
        };
    }
}
```

---

## Premium Subscription

### Premium Features

```php
<?php
// config/premium.php

return [
    'monthly_price_idr' => 29000, // Rp 29,000 per month
    'monthly_price_usd' => 2.00,
    
    'features' => [
        'ad_free' => true,
        'exclusive_badge' => '👑',
        'extra_daily_coins' => 50,
        'extra_daily_xp' => 100,
        'priority_matchmaking' => true,
        'exclusive_avatars' => true,
        'custom_username_color' => true,
    ],

    'trial_days' => 7,
];
```

### Premium Migration

```php
<?php
// database/migrations/2024_xx_xx_add_premium_fields_to_users.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_premium')->default(false);
            $table->timestamp('premium_expires_at')->nullable();
            $table->boolean('premium_trial_used')->default(false);
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('status'); // active, cancelled, expired
            $table->string('plan'); // monthly, yearly
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3);
            $table->timestamp('current_period_start');
            $table->timestamp('current_period_end');
            $table->string('payment_method')->nullable();
            $table->string('external_id')->nullable(); // Payment gateway ID
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('subscriptions');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_premium', 'premium_expires_at', 'premium_trial_used']);
        });
    }
};
```

### Premium Service

```php
<?php
// app/Services/PremiumService.php

namespace App\Services;

use App\Models\User;
use App\Models\Subscription;
use Carbon\Carbon;

class PremiumService
{
    /**
     * Activate premium for user
     */
    public function activatePremium(User $user, int $days = 30): Subscription
    {
        $now = Carbon::now();
        $expiresAt = $now->copy()->addDays($days);

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'status' => 'active',
            'plan' => 'monthly',
            'amount' => config('premium.monthly_price_idr'),
            'currency' => 'IDR',
            'current_period_start' => $now,
            'current_period_end' => $expiresAt,
        ]);

        $user->update([
            'is_premium' => true,
            'premium_expires_at' => $expiresAt,
        ]);

        return $subscription;
    }

    /**
     * Start free trial
     */
    public function startTrial(User $user): ?Subscription
    {
        if ($user->premium_trial_used) {
            return null;
        }

        $subscription = $this->activatePremium($user, config('premium.trial_days'));
        
        $user->update(['premium_trial_used' => true]);

        return $subscription;
    }

    /**
     * Check and expire subscriptions
     */
    public function checkExpirations(): void
    {
        $expired = User::where('is_premium', true)
            ->where('premium_expires_at', '<', Carbon::now())
            ->get();

        foreach ($expired as $user) {
            $this->expirePremium($user);
        }
    }

    /**
     * Expire premium subscription
     */
    protected function expirePremium(User $user): void
    {
        $user->update([
            'is_premium' => false,
            'premium_expires_at' => null,
        ]);

        Subscription::where('user_id', $user->id)
            ->where('status', 'active')
            ->update(['status' => 'expired']);
    }

    /**
     * Give premium daily bonus
     */
    public function giveDailyBonus(User $user): void
    {
        if (!$user->is_premium) {
            return;
        }

        $features = config('premium.features');
        
        $user->awardXP($features['extra_daily_xp'], 'Premium daily bonus');
        
        $coinService = app(CoinService::class);
        $coinService->award($user, $features['extra_daily_coins'], 'Premium daily bonus');
    }
}
```

---

## Payment Integration

### Payment Gateway Options for Indonesia

#### 1. Midtrans (Recommended)
- Supports: Credit Card, Bank Transfer, E-Wallet (GoPay, OVO, DANA)
- Fee: 2.9% + Rp 2,000 per transaction
- Setup: Easy, good documentation

```bash
composer require midtrans/midtrans-php
```

```php
<?php
// config/midtrans.php

return [
    'server_key' => env('MIDTRANS_SERVER_KEY'),
    'client_key' => env('MIDTRANS_CLIENT_KEY'),
    'is_production' => env('MIDTRANS_IS_PRODUCTION', false),
    'is_sanitized' => true,
    'is_3ds' => true,
];
```

#### 2. Xendit
- Supports: Virtual Accounts, E-Wallets, Retail Outlets
- Fee: 2-4% per transaction
- Good for recurring payments

#### 3. PayPal (International)
- For international users
- Fee: 4.4% + Rp 3,000

### Payment Controller (Midtrans Example)

```php
<?php
// app/Http/Controllers/PaymentController.php

namespace App\Http\Controllers;

use App\Services\PremiumService;
use Illuminate\Http\Request;
use Midtrans\Config;
use Midtrans\Snap;

class PaymentController extends Controller
{
    public function __construct()
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.is_sanitized');
        Config::$is3ds = config('midtrans.is_3ds');
    }

    public function subscribe(Request $request)
    {
        $params = [
            'transaction_details' => [
                'order_id' => 'PREMIUM-' . auth()->id() . '-' . time(),
                'gross_amount' => config('premium.monthly_price_idr'),
            ],
            'customer_details' => [
                'first_name' => auth()->user()->name,
                'email' => auth()->user()->email,
            ],
            'item_details' => [
                [
                    'id' => 'premium-monthly',
                    'price' => config('premium.monthly_price_idr'),
                    'quantity' => 1,
                    'name' => 'Premium Subscription - 1 Month',
                ]
            ],
        ];

        try {
            $snapToken = Snap::getSnapToken($params);
            
            return view('premium.checkout', [
                'snapToken' => $snapToken,
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Payment initialization failed');
        }
    }

    public function callback(Request $request)
    {
        $serverKey = config('midtrans.server_key');
        $hashed = hash("sha512", $request->order_id . $request->status_code . $request->gross_amount . $serverKey);

        if ($hashed === $request->signature_key) {
            if ($request->transaction_status == 'settlement' || $request->transaction_status == 'capture') {
                // Payment successful
                $orderId = $request->order_id;
                preg_match('/PREMIUM-(\d+)-/', $orderId, $matches);
                $userId = $matches[1];

                $user = \App\Models\User::find($userId);
                if ($user) {
                    $premiumService = app(PremiumService::class);
                    $premiumService->activatePremium($user);
                }
            }
        }

        return response()->json(['status' => 'ok']);
    }
}
```

---

## Revenue Tracking

### Revenue Dashboard

```php
<?php
// app/Http/Controllers/Admin/RevenueController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdImpression;
use App\Models\Subscription;
use Carbon\Carbon;

class RevenueController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();

        // Ad Revenue
        $adRevenueToday = AdImpression::where('date', $today)
            ->sum('revenue_estimate');
        
        $adRevenueMonth = AdImpression::where('date', '>=', $thisMonth)
            ->sum('revenue_estimate');

        // Subscription Revenue
        $subscriptionRevenueMonth = Subscription::where('status', 'active')
            ->where('current_period_start', '>=', $thisMonth)
            ->sum('amount');

        // Active Subscribers
        $activeSubscribers = Subscription::where('status', 'active')->count();

        // Impressions
        $impressionsToday = AdImpression::where('date', $today)->count();
        $impressionsMonth = AdImpression::where('date', '>=', $thisMonth)->count();

        return view('admin.revenue', compact(
            'adRevenueToday',
            'adRevenueMonth',
            'subscriptionRevenueMonth',
            'activeSubscribers',
            'impressionsToday',
            'impressionsMonth'
        ));
    }
}
```

---

## Ad-Free Experience

### Ad-Free Middleware

```php
<?php
// app/Http/Middleware/CheckPremiumStatus.php

namespace App\Http\Middleware;

use Closure;

class CheckPremiumStatus
{
    public function handle($request, Closure $next)
    {
        if (auth()->check()) {
            $user = auth()->user();
            
            // Check if premium expired
            if ($user->is_premium && $user->premium_expires_at < now()) {
                $premiumService = app(\App\Services\PremiumService::class);
                $premiumService->expirePremium($user);
            }
        }

        return $next($request);
    }
}
```

---

## Indonesian Payment Methods

### Supported Payment Methods

1. **Bank Transfer** (Most Popular)
   - BCA, Mandiri, BNI, BRI
   - Virtual Account numbers
   - Confirmation within 24 hours

2. **E-Wallets**
   - GoPay
   - OVO
   - DANA
   - ShopeePay
   - LinkAja

3. **Retail Outlets**
   - Indomaret
   - Alfamart
   - Pay via receipt code

4. **Credit/Debit Card**
   - Visa
   - Mastercard
   - JCB

---

## Completion Checklist

### Ad Network Setup
- [ ] Register with PropellerAds
- [ ] Register with Adsterra
- [ ] Apply for Google AdSense (optional)
- [ ] Configure ad network credentials
- [ ] Test ad serving

### Ad Implementation
- [ ] Create ad components (banner, interstitial, rewarded)
- [ ] Implement frequency capping
- [ ] Add ad placement to all planned locations
- [ ] Test ad display on mobile and desktop
- [ ] Implement ad tracking

### Premium System
- [ ] Add premium fields to database
- [ ] Implement PremiumService
- [ ] Create premium subscription page
- [ ] Add premium badges/indicators
- [ ] Test free trial system

### Payment Integration
- [ ] Choose payment gateway
- [ ] Set up payment gateway account
- [ ] Implement payment flow
- [ ] Test payment processing
- [ ] Set up payment webhooks/callbacks

### Revenue Tracking
- [ ] Create ad impression tracking
- [ ] Create subscription tracking
- [ ] Build revenue dashboard
- [ ] Set up revenue reports

### Testing
- [ ] Test all ad placements
- [ ] Test premium subscription flow
- [ ] Test payment processing
- [ ] Test ad-free experience
- [ ] Test revenue tracking

---

## Related Documentation
- [PHASE_3_FRONTEND.md](./PHASE_3_FRONTEND.md)
- [PHASE_4_PROGRESSION.md](./PHASE_4_PROGRESSION.md)
- [PHASE_7_LAUNCH.md](./PHASE_7_LAUNCH.md)

---

**Last Updated:** 2024
**Status:** Ready for Implementation
