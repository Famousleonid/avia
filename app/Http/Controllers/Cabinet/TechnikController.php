<?php

namespace App\Http\Controllers\Cabinet;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class TechnikController extends Controller
{

    public function index()
    {
        $users = User::all();

        return view('cabinet.technik.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function create()
    {

        return view('cabinet.technik.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:155', 'unique:users'],
            'password' => ['required', 'string', 'min:3'],
        ]);

        if (isset($request->is_admin))
            $request->is_admin = 1;
        else {
            $request->is_admin = 0;
        }
        if (isset($request->role))
            $request->role = 1;
        else {
            $request->role = 0;
        }

        $request->password = Hash::make($request->password);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'stamp' => $request->stamp,
            'phone' => $request->phone,
            'password' => $request->password,
            'is_admin' => $request->is_admin,
            'role' => $request->role,
        ]);

        if ($request->hasFile('avatar')) {

            $user->addMedia($request->file('avatar'))->toMediaCollection('avatars');

            $media = $user->getMedia('avatars')->first();
            $path = $media->getPath();
            $url = $media->getUrl();
        }


        return redirect()->route('techniks.index')->with('success', 'Пользователь добавлен');
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function edit($id)
    {
        $user = User::find($id);
        $avatar = $user->getMedia('avatar')->first();

        if (!$avatar) {
            $avatar = 0;
        }

        return view('cabinet.technik.edit', compact('user', 'avatar'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {

        $request->validate([
            'name' => 'required',
            'phone' => '',      // |size:10
            'stamp' => 'required',
            'team' => 'required',
        ]);

        ($request->has('is_admin')) ? $request->request->add(['is_admin' => 1]) : $request->request->add(['is_admin' => 0]);
        ($request->has('role')) ? $request->request->add(['role' => 1]) : $request->request->add(['role' => 0]);
        ($request->has('email_verified_at')) ? $request->request->add(['email_verified_at' => now()]) : $request->request->add(['email_verified_at' => NULL]);

        $user = User::find($id);
        unset($request['_token']);
        unset($request['_method']);

        $user->update($request->all());

        return redirect()->route('techniks.index')->with('success', 'Technik update successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        try {
            $answer = User::destroy($id);
        } catch (\Exception $e) {
            return back()->withErrors('The databases are linked. First remove...');
        }

        return redirect()->route('techniks.index')->with('success', 'Technik deleted');
    }
}
