<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{

    public function index()
    {
        $customers = Customer::all();
        return View('cabinet.customer.index', compact('customers'));
    }

    public function create()
    {
        return View('cabinet.customer.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['string', 'max:100'],
        ]);

        $customer = Customer::create($request->all());

        return redirect()->route('cabinet.customer.index')->with('success', 'Customer added');
    }

    public function edit($id)
    {
        $customer = Customer::find($id);

        return view('cabinet.customer.edit', compact('customer'));
    }

    public function update(Request $request, $id)
    {
        $customer = Customer::find($id);
        $customer->name = $request->input('name');
        $customer->save();

        return redirect()->route('cabinet.customer.index')->with('success', 'Changes saved');
    }

    public function destroy($id)
    {
        $customer = Customer::destroy($id);

        return redirect()->route('cabinet.customer.index')->with('success', 'Customer deleted');
    }
}
