<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\BuildingController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;



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

Route::get('/dashboard/building/{complex}/group/{lastReceivedMessageId}', [GroupController::class, 'index']);
Route::get('/dashboard/building/{complex}/lastMessageSeenId', [GroupController::class, 'lastMessageSeenId']);
Route::get('/dashboard/building/getMessagesBeforeFrom/{complex}/{fromFirstMessageId}', [GroupController::class, 'getMessagesBeforeFrom']);
Route::get('/dashboard/building/getMessagesAfterFrom/{complex}/{fromLastMessageId}', [GroupController::class, 'getMessagesAfterFrom']);
Route::post('/dashboard/building/getMessageViewers', [GroupController::class, 'getMessageViewers']);
Route::post('/dashboard/building/group/send', [GroupController::class, 'store']);
Route::post('/dashboard/building/group/edit', [GroupController::class, 'edit']);
Route::post('/dashboard/building/group/seen/', [GroupController::class, 'seen']);
Route::get('/dashboard/building/group/{Message}', [GroupController::class, 'destroy']);



Route::get('/dashboard/building/{complex}', [BuildingController::class, 'index'])->name('building');
Route::get('/dashboard/building/upgradeRole/{userRole}', [BuildingController::class, 'upgradeRole'])->name('building.upgradeRole');
Route::get('/dashboard/building/downgradeRole/{complex}', [BuildingController::class, 'downgradeRole'])->name('building.downgradeRole');
Route::post('/dashboard/building/{complex}/NotificationRegistration', [BuildingController::class, 'NotificationRegistration'])->name('building.NotificationRegistration');
Route::get('/dashboard/building/cancelNotification/{announcement}', [BuildingController::class, 'cancelNotification'])->name('building.cancelNotification');
Route::post('/dashboard/building/poll/{complexId}', [BuildingController::class, 'pollCreate'])->name('building.poll');
Route::post('/dashboard/building/vote', [BuildingController::class, 'vote'])->name('building.vote');
Route::post('/dashboard/building/vote/Result', [BuildingController::class, 'voteResult'])->name('building.voteResult');
Route::post('/dashboard/building/{complex}/incidentalCost', [BuildingController::class, 'incidentalCost'])->name('building.incidentalCost');
Route::post('/dashboard/building/incidentalCost/payment', [BuildingController::class, 'incidentalCostPayment'])->name('building.incidentalCostPayment');
Route::post('/dashboard/building/{complex}/buildingCharge', [BuildingController::class, 'buildingCharge'])->name('building.buildingCharge');
Route::post('/dashboard/building/buildingCharge/payment', [BuildingController::class, 'buildingChargePayment'])->name('building.buildingChargePayment');
Route::post('/dashboard/building/payments', [BuildingController::class, 'payments'])->name('building.payments');


Route::get('/dashboard', [UserController::class, 'index'])->name('dashboard');
Route::post('/dashboard/joinBuilding', [UserController::class, 'store'])->name('dashboard.store');
Route::post('/dashboard/registerBuilding', [UserController::class, 'register'])->name('dashboard.register');
Route::post('/dashboard/unit', [UserController::class, 'update'])->name('dashboard.unit');
Route::post('/dashboard/addUnit', [UserController::class, 'addUnit'])->name('dashboard.addUnit');


Route::get('/adminPanel', [AdminController::class, 'index'])->name('adminPanel.index');
Route::get('/adminPanel/accept/{MembershipRequest}', [AdminController::class, 'accept'])->name('adminPanel.accept');
Route::get('/adminPanel/reject/{MembershipRequest}', [AdminController::class, 'reject'])->name('adminPanel.reject');

Route::middleware('auth')->group(function () {
    // Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/dashboard/update', [ProfileController::class, 'update'])->name('profile.update');
    // Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');


require __DIR__ . '/auth.php';
