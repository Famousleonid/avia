<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Builder;
use App\Models\Customer;
use App\Models\Manual;
use App\Models\Plane;
use App\Models\Tdr;
use App\Models\Unit;
use App\Models\Workorder;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TdrController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return
     */
    public function index()
    {
        $orders=Workorder::all();
        $manuals = Manual::all();
        $units =Unit::with('manuals')->get();
        $tdrs=Tdr::all();
        return view('admin.tdrs.index', compact('orders','units','manuals','tdrs'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
       //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Application|Factory|View
     */
    public function show($id)
    {
        $current_wo = Workorder::findOrFail($id);
        $units = Unit::all();
        $user = Auth::user();
        $customers = Customer::all();
        $manuals = Manual::all();
        $planes = Plane::all();
        $builders = Builder::all();
        return view('admin.tdrs.show', compact(  'current_wo','units','user','customers','manuals','builders','planes'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Application|Factory|View
     */
    public function edit($id)
    {
        $current_wo = Workorder::findOrFail($id);
        $units = Unit::all();
        $user = Auth::user();
        $customers = Customer::all();
        $manuals = Manual::all();
        $planes = Plane::all();
        $builders = Builder::all();
        return view('admin.tdrs.edit', compact(  'current_wo','units','user','customers','manuals','builders','planes'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
