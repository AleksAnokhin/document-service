<?php


namespace App\Http\Controllers\Admin;


use App\Facades\DocumentorEncryptor;
use App\Http\Controllers\Controller;
use App\PersonUploads;
use App\User;
use App\Users;
use App\UsersToCheck;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Storage;

class PersonsAdminController extends Controller
{

    //*********************************** DATA SECTION  ***********************************//

    /**
     * Getting all information about persons to check
     * @return \Illuminate\Http\JsonResponse
     */
    public function allToCheck()
    {
        $users_id = UsersToCheck::usersId();
        if(empty($users_id)) return response()->json(['error' => null, 'status' => 'empty'],200);
        $persons = [];

        foreach($users_id as $id) {
            try {
                $user = Users::findOrFail($id);
            } catch(ModelNotFoundException $e) {
                return response()->json(['error' => 'error occur while finding users in database', 'status' => 'error'],400);
            }

            if($user->type == 'person' || $user->type == 'merchant') {
                $user->person_entity;
                $user->person_data;
                $persons[] = $user;
            }
        }
        return response()->json(['error' => null,'status' => 'ok', 'data' => $persons],200);
    }

    /**
     * Returns array of documents set
     * @return \Illuminate\Http\JsonResponse
     */
    public function allToCheckDocs()
    {
        $users_id = UsersToCheck::usersId();
        if(empty($users_id)) return response()->json(['error' => null, 'status' => 'empty'],200);
        $persons = [];

        foreach($users_id as $id) {
            try {
                $user = Users::findOrFail($id);
            } catch(ModelNotFoundException $e) {
                return response()->json(['error' => 'error occur while finding users in database', 'status' => 'error'],400);
            }

            if($user->type == 'person' || $user->type == 'merchant') {
               $data = unserialize($user->person_data->document_set);
                $persons[$user->id] = $data;
            }
        }
        return response()->json(['error' => null,'status' => 'ok', 'data' => $persons],200);
    }

    /**
     * Get one person data to check
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
        $type_check = $user->type === 'person' || $user->type === 'merchant' ? true : false;
        if(!$type_check) return response()->json(['error' => null, 'status' => 'not allowed'],400);
        $user->person_entity;
        $user->person_data;
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
        $type_check = $user->type === 'person' || $user->type === 'business' ? true : false;
        if(!$type_check) return response()->json(['error' => null, 'status' => 'not allowed'],400);
        if(empty($user->person_data->document_set)) return response()->json(['error' => null, 'status' => 'empty'],200);
        $document_set = unserialize($user->person_data->document_set);
        return response()->json(['error' => null, 'status' => 'ok', 'data' => $document_set],200);
    }

    //*********************************** FILES SECTION  ***********************************//


    /**
     * Get any person 's uploaded file
     * @param int $id
     * @param string $filename
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function getPersonsFile(int $id, string $filename)
    {
        try {
            $user = Users::findOrFail($id);

        } catch(ModelNotFoundException $e) {

            return response()->json(['error' => null, 'status' => 'User has not been found'],400);
        }

        $type_check = $user->type === 'person' || $user->type === 'merchant' ? true : false;
        if(!$type_check) return response()->json(['error' => null, 'status' => 'not allowed'],400);

        if(empty($user->person_data->document_set)) return response()->json(['error' => null, 'status' => 'client documents has not been uploaded yet'],200);
        $document_set = unserialize($user->person_data->document_set);
        if(!in_array($filename, $document_set)) return response()->json(['error' => null, 'status' => 'document is not present in client docset'],200);

        try {
            $persons_upload = PersonUploads::where(['user_id' => $id, 'name' => $filename])->firstOrFail();
        } catch(ModelNotFoundException $exception) {
            return response()->json(['error' => null, 'status' => 'client uploads has not been found'],400);
        }

        $path = $persons_upload->path;
        if(empty($path)) return response()->json(['error' => 'Path to file has not been found', 'status' => 'error'],400);

        $encryptedContent = Storage::get($path);
        $decriptedContent = DocumentorEncryptor::decrypt($encryptedContent,$user->uid);
        $mime_type = transformMimeType( $persons_upload->type);
        return response($decriptedContent,200)->header('Content-Type', $mime_type);
    }



}
