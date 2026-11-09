<?php

namespace App\Http\Controllers;

use App\Http\Middleware\AccessMiddleware;
use App\Http\Requests\ComplexLocationRequest;
use App\Models\Complex;
use Illuminate\Http\RedirectResponse;

class ComplexLocationController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', AccessMiddleware::class.':manager']);
    }

    public function update(ComplexLocationRequest $request, Complex $complex): RedirectResponse
    {
        $coordinates = $request->coordinates();

        if ($coordinates !== []) {
            $complex->update($coordinates);
        }

        return redirect()->route('building', $complex)->with([
            'status' => 'success',
            'message' => 'موقعیت ساختمان بروزرسانی شد.',
            'page' => 'setting',
        ]);
    }
}
