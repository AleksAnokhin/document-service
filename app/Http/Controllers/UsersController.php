<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Users as Users;

class UsersController extends Controller
{
    /**
     * Get authenticated user
     *
     * @return [json] user object
     */
    public function user(Request $request)
    {
        return response()->json($request->user());
    }

    /**
     * Get all users
     *
     * @return [json] user object
     */
    public function getAll(Request $request)
    {
        return response()->json(Users::all());
    }

    /**
     * Get precise user
     * @param int $id
     * @return [json] user object
     */
    public function getUser(int $id)
    {
        return response()->json(Users::find($id));
    }

    /**
     * Update precise user
     * @param int $id
     * @param  Request  $request
     * @return [json] user object
     */

    public function updateUser(Request $request, int $id)
    {
        $user = Users::find($id);
        if(!empty($user)) {
            $data = $request->all();
            foreach ($data as $key=>$val) {
                if(isset($user->$key)) $user->$key = $val;
            }
            if($user->save())  return response()->json(['message'=> 'User has been updated successfully'],200);

        }
        return response()->json(['message'=> 'User not found'],404 );
    }
    /**
     * Delete user
     * @param int $id
     * @return [json] user object
     */
    public function deleteUser(int $id)
    {
        $user = Users::find($id);
        if((empty($user))) return response()->json(['message'=> 'User not found'],400 );
        if($user->delete()) return response()->json(['message'=> 'User has been deleted successfully'],200);
        return response()->json(['message'=> 'User has not been deleted successfully'],400);
    }
}
