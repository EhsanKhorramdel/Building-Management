<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\BuildingDashboardController;
use App\Http\Controllers\ChargeController;
use App\Http\Controllers\ChatAttachmentController;
use App\Http\Controllers\ComplexLocationController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\IncidentalCostController;
use App\Http\Controllers\OnlinePaymentController;
use App\Http\Controllers\PaymentsReportController;
use App\Http\Controllers\PollController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\VerifyCsrfToken;
use App\Services\Payments\GatewayManager;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('mainPage');
});

Route::get('/chat/attachments/{message}', [ChatAttachmentController::class, 'show'])
    ->middleware('auth')->name('chat.attachments.show');

Route::get('/dashboard/building/{complex}/group/{lastReceivedMessageId}', [GroupController::class, 'index']);
Route::get('/dashboard/building/{complex}/lastMessageSeenId', [GroupController::class, 'lastMessageSeenId']);
Route::get('/dashboard/building/getMessagesBeforeFrom/{complex}/{fromFirstMessageId}', [GroupController::class, 'getMessagesBeforeFrom']);
Route::get('/dashboard/building/getMessagesAfterFrom/{complex}/{fromLastMessageId}', [GroupController::class, 'getMessagesAfterFrom']);
Route::post('/dashboard/building/getMessageViewers', [GroupController::class, 'getMessageViewers']);
Route::post('/dashboard/building/group/send', [GroupController::class, 'store']);
Route::post('/dashboard/building/group/edit', [GroupController::class, 'edit']);
Route::post('/dashboard/building/group/seen/', [GroupController::class, 'seen']);
Route::delete('/dashboard/building/group/{Message}', [GroupController::class, 'destroy']);

Route::get('/dashboard/building/{complex}', [BuildingDashboardController::class, 'index'])->name('building');
Route::post('/dashboard/building/upgradeRole/{userRole}', [RoleController::class, 'upgradeRole'])->name('building.upgradeRole');
Route::post('/dashboard/building/downgradeRole/{complex}', [RoleController::class, 'downgradeRole'])->name('building.downgradeRole');
Route::post('/dashboard/building/{complex}/NotificationRegistration', [AnnouncementController::class, 'NotificationRegistration'])->name('building.NotificationRegistration');
Route::post('/dashboard/building/cancelNotification/{announcement}', [AnnouncementController::class, 'cancelNotification'])->name('building.cancelNotification');
Route::post('/dashboard/building/poll/{complexId}', [PollController::class, 'pollCreate'])->name('building.poll');
Route::post('/dashboard/building/vote', [PollController::class, 'vote'])->name('building.vote');
Route::post('/dashboard/building/vote/Result', [PollController::class, 'voteResult'])->name('building.voteResult');
Route::post('/dashboard/building/{complex}/incidentalCost', [IncidentalCostController::class, 'incidentalCost'])->name('building.incidentalCost');
Route::post('/dashboard/building/incidentalCost/payment', [IncidentalCostController::class, 'incidentalCostPayment'])->name('building.incidentalCostPayment');
Route::post('/dashboard/building/{complex}/buildingCharge', [ChargeController::class, 'buildingCharge'])->name('building.buildingCharge');
Route::post('/dashboard/building/buildingCharge/payment', [ChargeController::class, 'buildingChargePayment'])->name('building.buildingChargePayment');
Route::post('/dashboard/building/payments', [PaymentsReportController::class, 'payments'])->name('building.payments');

Route::get('/dashboard', [UserController::class, 'index'])->name('dashboard');
Route::post('/dashboard/joinBuilding', [UserController::class, 'store'])->name('dashboard.store');
Route::post('/dashboard/registerBuilding', [UserController::class, 'register'])->name('dashboard.register');
Route::post('/dashboard/unit', [UserController::class, 'update'])->name('dashboard.unit');
Route::post('/dashboard/addUnit', [UserController::class, 'addUnit'])->name('dashboard.addUnit');

Route::get('/adminPanel', [AdminController::class, 'index'])->name('adminPanel.index');
Route::post('/adminPanel/accept/{MembershipRequest}', [AdminController::class, 'accept'])->name('adminPanel.accept');
Route::post('/adminPanel/reject/{MembershipRequest}', [AdminController::class, 'reject'])->name('adminPanel.reject');

Route::middleware('auth')->group(function () {
    Route::patch('/dashboard/update', [ProfileController::class, 'update'])->name('profile.update');
});

Route::patch('/dashboard/building/{complex}/location', [ComplexLocationController::class, 'update'])->name('building.location.update');

Route::post('/dashboard/building/{complex}/join-link', [RoleController::class, 'regenerateJoinLink'])->name('building.regenerateJoinLink');

Route::post('/payments/initiate', [OnlinePaymentController::class, 'initiate'])->middleware('auth')->name('payments.initiate');
Route::match(['get', 'post'], '/payments/callback/{driver}', [OnlinePaymentController::class, 'callback'])
    ->whereIn('driver', ['mock', 'gateway'])->withoutMiddleware(VerifyCsrfToken::class)->name('payments.callback');

if (app(GatewayManager::class)->mockSelected()) {
    Route::get('/payments/mock/{transaction}', [OnlinePaymentController::class, 'mock'])->middleware('auth')->name('payments.mock');
}

require __DIR__.'/auth.php';
