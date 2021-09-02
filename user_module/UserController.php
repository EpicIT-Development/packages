<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Form;
use App\Models\Log;
use App\Models\Company;
use App\Mail\Feedback;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Auth\ResetsPasswords;


class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Get data from database section 
        // Get all users
        $data['forms'] = form::all();
        $data['clients'] = client::all();
        $data['users'] = User::with('client')->where('admin', 0)->get();
        return $data;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function users()
    {
        // Get all users
        $data = User::with('client')->first();
        return $data;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        // get specified user section

        $data = User::find($id);
        $data->forms_id = unserialize($data->forms_id);
        return $data;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id, $loggedin_name)
    {
        


        $validator = Validator::make($request->all(),  [
            'name' => ['required', 'string', 'max:255'],
            'insertion' => 'nullable | max:255',
            'secondname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $id],
            'password' => ['string', Password::min(8)],
        ]);
        // If validation fails then returen errors with input is required or password should be more than 8 charters
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }   // If valdation input is good than post input to database
        else {
            if( empty($request['password'])){
                $request['password'] = User::where('email',$request['email'])->get('password');
                return $request['password'];
                
            }
            $updateUser = User::find($id);
            $updateUser->update(array(
                'name' => $request['name'],
                'insertion' => $request['insertion'],
                'secondname' => $request['secondname'],
                'email' => $request['email'],
                'password' => Hash::make($request['password']),
                'forms_id' => serialize($request['forms_id']),
                'password_change' => $request['password_change']
            ));
            $Username = $request['name'];
            $status = 'De gebruiker ' . $Username . ' is bewerkt';
            $log = new log();
            $log->user = $loggedin_name;
            $log->ip = HelperClass::getIp();
            $log->status = $status;
            $log->save();
        }
    }
    /**
     * deactivate the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, $loggedin_name)
    {
        // Delete user section  
        // Delete specified user
        $User = User::find($id);
        $Username = $User->name;
        $status = 'De gebruiker ' . $Username . ' is verwijderd';
        $log = new log();
        $log->user = $loggedin_name;
        $log->ip = HelperClass::getIp();
        $log->status = $status;
        $log->save();
        $User->delete();
    }
    use ResetsPasswords;
    public function getChangePasswordForm()
    {
        $view = view('auth.change-password');
        return  $view;
    }
    public function changePassword(Request $request)
    {


        if (!(Hash::check($request->get('currentpassword'), Auth::user()->password))) {
            // The passwords matches
            return  response()->json(["errors" => "Het opgegeven wachtwoord komt niet overeen met je huidige wachtwoord, Probeer opnieuw."]);
        }

        if (strcmp($request->get('currentpassword'), $request->get('newpassword')) == 0) {
            //Current password and new password are same
            return  response()->json(["errors" => "Het opgegeven wachtwoord is hetzelfde als het huidige wachtwoord, Kies een ander wachtwoord."]);
        }

        $validator = Validator::make($request->all(),  [
            'currentpassword' => ['required'],
            'newpassword' =>   ['min:8', 'string', 'required_with:password_confirmation', 'same:password_confirmation'],
            'password_confirmation' => ['min:8']
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 401);
        } else {
            //Change Password
            $user = Auth::user();
            $user->password = bcrypt($request->get('newpassword'));
            $user->password_change = 0;
            $user->save();
            Auth::logout();
            return redirect('/');
        }
    }

    public function saveFeedback(Request $request)
    {
        //Set smtp Info
        HelperClass::smtpInfo();

        $epicit_info = Company::first();

        $validator = Validator::make($request->all(),  [
            'user_name' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'feedback_type' => 'required',
            'description' => ['required', 'string'],
        ]);
        // If validation fails then returen errors with input is required or password should be more than 8 charters
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }   // If valdation input is good than post input to database
        else {
            $feedback = (object)$request->all();

            return Mail::to("mvisser@epicit.nl")->send(new Feedback($epicit_info, $feedback));
        }

        
    }
}
