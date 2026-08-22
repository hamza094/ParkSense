<?php

namespace App\Http\Controllers;

use App\Models\Park;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ParkController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $parks = Park::all();

        return Inertia::render('Dashboard', [
            'parks' => $parks,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Park $park)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Park $park)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Park $park)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Park $park)
    {
        //
    }
}
