<header class="app-header">
    <div class="app-header-left">
        <x-drawer-controls />
        <span class="app-icon" aria-hidden="true"></span>
        <p class="app-name">پنل ساختمان</p>
    </div>
    <div class="app-header-right">
        <span class="profile-btn">{{ $complex->name }}</span>
        <x-theme-button />
        <button type="button" class="register-btn" aria-label="افزودن مورد جدید" hidden><x-icon name="plus" /></button>
    </div>
</header>
