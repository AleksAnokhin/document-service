<?php

namespace App\Http\Controllers\Admin;

use App\BusinessUploads;
use App\Facades\DocumentorEncryptor;
use App\Http\Controllers\Controller;
use App\User;
use App\Users;
use App\UsersToCheck;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Storage;

class BusinessAdminController extends Controller
{


    //*********************************** DATA SECTION  ***********************************//

    /**
     * Getting all information about business to check
     * @return \Illuminate\Http\JsonResponse
     */

    public function allToCheck()
    {
        $users_id = UsersToCheck::usersId();
        if(empty($users_id)) return response()->json(['error' => null, 'status' => 'empty'],200);

        $businesses = [];

        foreach($users_id as $id) {
            try {
                $user = Users::findOrFail($id);
            } catch(ModelNotFoundException $e) {
                return response()->json(['error' => 'error occur while finding users in database', 'status' => 'error'],400);
            }

            if($user->type == 'business') {
                $user->business_entity;
                $user->business_data;
                $businesses[] = $user;
            }
        }
        return response()->json(['error' => null,'status' => 'ok', 'data' => $businesses],200);
    }

    /**
     * Returns array of documents set
     * @return \Illuminate\Http\JsonResponse
     */
    public function allToCheckDocs()
    {
        $users_id = UsersToCheck::usersId();
        if(empty($users_id)) return response()->json(['error' => null, 'status' => 'empty'],200);
        $businesses = [];

        foreach($users_id as $id) {
            try {
                $user = Users::findOrFail($id);
            } catch(ModelNotFoundException $e) {
                return response()->json(['error' => 'error occur while finding users in database', 'status' => 'error'],400);
            }

            if($user->type == 'business') {
                $data = unserialize($user->business_data->document_set);

                $businesses[$user->id] = $data;
            }
        }
        return response()->json(['error' => null,'status' => 'ok', 'data' => $businesses],200);
    }

    /**
     * Get one business data to check
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function oneToCheck(int $id)
    {
        try {
            $user = Users::findOrFail($id);
        } catch(ModelNotFoundException $e) {
            return response()->json(['error' => null, 'status' => 'User has not been found'],200);
        }
        if($user->type !== 'business') return response()->json(['error' => null, 'status' => 'not allowed'],400);
        $user->business_entity;
        $user->business_data;
        return response()->json(['error' => null,'status' => 'ok', 'data' => $user],200);
    }

    /**
     * Docs set of one precise user
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function oneToCheckDocs(int $id)
    {
        try {
            $user = Users::findOrFail($id);
        } catch(ModelNotFoundException $e) {
            return response()->json(['error' => null, 'status' => 'User has not been found'],200);
        }
        if($user->type !== 'business') return response()->json(['error' => null, 'status' => 'not allowed'],400);
        if(empty($user->business_data->document_set)) return response()->json(['error' => null, 'status' => 'empty'],200);
        $document_set = unserialize($user->business_data->document_set);

        return response()->json(['error' => null, 'status' => 'ok', 'data' => $document_set],200);
    }

    //*********************************** FILES SECTION  ***********************************//


    /**
     * Get any business 's uploaded file
     * @param int $id
     * @param string $filename
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function getBusinessFile(int $id, string $filename)
    {
        try {
            $user = Users::findOrFail($id);

        } catch(ModelNotFoundException $e) {

            return response()->json(['error' => null, 'status' => 'User has not been found'],400);
        }

        if($user->type !== 'business') return response()->json(['error' => null, 'status' => 'not allowed'],400);

        if(empty($user->business_data->document_set)) return response()->json(['error' => null, 'status' => 'client documents has not been uploaded yet'],200);
        $document_set = unserialize($user->business_data->document_set);
        if(!in_array($filename, $document_set)) return response()->json(['error' => null, 'status' => 'document is not present in client docset'],200);

        try {
            $business_upload = BusinessUploads::where(['user_id' => $id, 'name' => $filename])->firstOrFail();
        } catch(ModelNotFoundException $exception) {
            return response()->json(['error' => null, 'status' => 'client uploads has not been found'],400);
        }

        $path = $business_upload->path;
        if(empty($path)) return response()->json(['error' => 'Path to file has not been found', 'status' => 'error'],400);

        $encryptedContent = Storage::get($path);
        $decriptedContent = DocumentorEncryptor::decrypt($encryptedContent,$user->uid);
        $mime_type = transformMimeType( $business_upload->type);
        return response($decriptedContent,200)->header('Content-Type', $mime_type);
    }

}
