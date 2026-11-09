<div class="buildings-section home-window" data-panel="home">
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
            <button type="button" class="view-btn list-view" aria-label="نمایش فهرستی"><x-icon name="list" /></button>
            <button type="button" class="view-btn grid-view" aria-label="نمایش شبکه‌ای"><x-icon name="grid" /></button>
        </div>
    </div>
    <div class="building-boxes jsGridView" data-layout>
        @php
            $residentsCollection = collect($residents);
            $sortedUnits = $residentsCollection->sortBy(function ($resident) {
                return (int) $resident['unitNumber'];
            });
        @endphp

        @foreach ($sortedUnits as $resident)
            <x-building-card>

                <div class="building-box-content-header">

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
                        <form method="POST" action="{{ URL::route('building.upgradeRole', $resident['roleId']) }}">
                    @csrf
                    <button type="submit" >ارتقاء به مدیر</button></form>
                    @endif
                </div>
            </x-building-card>
    @endforeach
</div>
</div>
