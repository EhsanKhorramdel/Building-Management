<div class="buildings-section home-window">
    <div class="buildings-section-header">
        <p>ساکنین ساختمان</p>
    </div>
    <div class="buildings-section-line">
        <div class="buildings-status">
            <div class="item-status">
                <span class="status-number">{{ $managerCount }}</span>
                <span class="status-type">مدیر</span>
            </div>
            <div class="item-status">
                <span class="status-number">{{ $residentCount }}</span>
                <span class="status-type">ساکن</span>
            </div>
            <div class="item-status">
                <span class="status-number">{{ $residentCount + $managerCount }}</span>
                <span class="status-type">کل</span>
            </div>
        </div>
        <div class="view-actions">
            <button class="view-btn list-view" title="List View">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="feather feather-list">
                    <line x1="8" y1="6" x2="21" y2="6" />
                    <line x1="8" y1="12" x2="21" y2="12" />
                    <line x1="8" y1="18" x2="21" y2="18" />
                    <line x1="3" y1="6" x2="3.01" y2="6" />
                    <line x1="3" y1="12" x2="3.01" y2="12" />
                    <line x1="3" y1="18" x2="3.01" y2="18" />
                </svg>
            </button>
            <button class="view-btn grid-view active" title="Grid View">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="feather feather-grid">
                    <rect x="3" y="3" width="7" height="7" />
                    <rect x="14" y="3" width="7" height="7" />
                    <rect x="14" y="14" width="7" height="7" />
                    <rect x="3" y="14" width="7" height="7" />
                </svg>
            </button>
        </div>
    </div>
    <div class="building-boxes jsGridView">
        @php
            $residentsCollection = collect($residents);
            $sortedUnits = $residentsCollection->sortBy(function ($resident) {
                return (int) $resident['unitNumber'];
            });
        @endphp

        @foreach ($sortedUnits as $resident)
            <div class="building-box-wrapper">
                @if ($resident['role'] == 'manager')
                    <div class="building-box">
                    @else
                        <div class="building-box bg-[#fee4cb]">
                @endif

                <div class="building-box-content-header">
                    <p class="hidden">{{ $complex['id'] }}</p>
                    <p class="box-content-header">{{ $resident['name'] }}</p>
                    <p class="box-content-subheader">
                        {{ $resident['unitNumber'] ?? 'N/A' }}
                    </p>
                </div>
                <div class="box-address-wrapper">
                    <p class="box-address-header">{{ $resident['role'] === 'manager' ? 'مدیر' : 'ساکن' }}</p>
                </div>
                <div class="building-box-footer h-full">
                    @if ($isManager && $resident['role'] === 'resident')
                        <a href="{{ URL::route('building.upgradeRole', $resident['roleId']) }}">ارتقاء به مدیر</a>
                    @endif
                </div>
            </div>
    </div>
    @endforeach
</div>
</div>
