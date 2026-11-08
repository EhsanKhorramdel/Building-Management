<?php

use App\Models\IncidentalCost;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\BuildingContext;

test('a manager can register an incidental cost with an invoice', function () {
    Storage::fake('local');
    $building = new BuildingContext;

    $this->actingAs($building->manager)->post(route('building.incidentalCost', $building->complex), [
        'title' => 'Lift maintenance', 'cost_explanation' => 'Monthly service', 'total_amount' => 400000,
        'share_amount' => 100000, 'cost_invoice' => UploadedFile::fake()->image('invoice.png'),
    ])->assertSessionHasNoErrors()->assertSessionHas('status', 'success');

    $cost = IncidentalCost::firstOrFail();
    expect($cost->complex_id)->toBe($building->complex->id)->and((float) $cost->share_amount)->toBe(100000.0);
    Storage::disk('local')->assertExists('public/incidentalCostFiles/'.$cost->cost_invoice);
});
