<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{

    public function __construct()
    {
        $this->authorizeResource(Customer::class, 'customer');
    }

    public function index()
    {
        $customers = Customer::all();

        return view('admin.customers.index',compact('customers'));
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:250'
        ]);

        $customer = Customer::create($validated);

        if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
            return response()->json([
                'id' => $customer->id,
                'name' => $customer->name,
            ], 201);
        }

        return redirect()->route('customers.index')->with('success', 'Customer created successfully.');
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        //
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:250'
        ]);

        $customer->update($validated);

        return redirect()->route('customers.index')->with('success', 'Customer updated successfully.');
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();

        return redirect()->route('customers.index')->with('success', 'Customer deleted successfully.');
    }
}
