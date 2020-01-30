<?php

namespace App\Http\Controllers;

use App\Facades\DocumentorEncryptor;
use App\Facades\SumSubConnector;
use App\heritage_models\Country;
use App\heritage_models\User;
use App\PersonData;
use App\PersonUploads;
use App\SumSub;
use App\Tools\DocumentorClient;
use App\Users;
use App\UsersToCheck;
use http\Env\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


class PersonUploaderController extends Controller
{



    /**
     * Upload users files and send them to sum_sub
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function upload(Request $request)
    {
        $user_id = $request->user()->id;
        $user = Users::find($user_id);
        $sum_sub_id = empty($user->sumsub_entity) ? null : $user->sumsub_entity->sum_sub_id;

        //check if user has already been created and has uploaded documents
        if(empty($sum_sub_id)) {
            $firstCase = $this->firstUpload($request, $user_id);
            if(!is_null($firstCase['error'])) return response()->json(['error' => $firstCase['error']],400);

            $user_to_check = new UsersToCheck([
                'user_id' => $user_id
            ]);

            if(!$user_to_check->save()) return response()->json(['error' => 'error while saving new  user t o check'],400);
            return response()->json(['error' => null,'status' => 'ok'],200);

        } else {

            $repeatedCase = $this->repeatedUploads($request,$user_id);

            if(!is_null($repeatedCase['error'])) return response()->json(['error' => $repeatedCase['error']],400);
            return response()->json(['error' => null, 'status' => 'ok '],200);
        }
    }


    /**
     * First upload 's process of the user
     * @param Request $request
     * @param string $user_id
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function firstUpload(Request $request, string $user_id)
    {
        if(empty(PersonData::where('user_id',$user_id)->first())) {
            $person_data = new PersonData([
                'user_id' => $user_id,
                'type_passport' => $request->type_passport,
                'type_bank' => $request->type_bank,
                'kyc_input' => $request->kyc_input
            ]);
            if(!$person_data->save()) return ['error'=> 'Person data has not been saved!'];
        }
        $document_set = [];
        $uid= $request->user()->uid;
        foreach ($request->file() as $name=>$content) {
            $document_set[] = $name;
            $file = $request->file($name);
            $content = $file->get();
            $encryptedContent = DocumentorEncryptor::encrypt($content,$uid);
            $file_random_name = Str::random(6);
            $path = 'person/' .$uid.'/' .$name . '/'. $file_random_name . '.dat';
            $upload = Storage::put($path,$encryptedContent);
            if(!$upload) return ['error'=> 'File '. $name .' has not been uploaded'];

            $person_uploads = new PersonUploads([
                'user_id' => $user_id,
                'status_id' => 1,
                'name' => $name,
                'type' => $request->file($name)->getClientOriginalExtension(),
                'size' => $request->file($name)->getClientSize(),
                'path' => $path
            ]);

            if(!$person_uploads->save()) return ['error'=> 'Person uploads has not been saved to database!'];

        }

        //storing dataset to person_data table

        $serializedDocSet = serialize($document_set);

        try {
            $person_data_add = PersonData::where('user_id', $user_id)->firstOrFail();
            $person_data_add->document_set = $serializedDocSet;
            $person_data_add->save();

        } catch(ModelNotFoundException $e) {
            return ['error'=> $e->getMessage()];
        }

        //****SUM_SUB_BLOCK****//
        $sum_sub_proc =  SumSubConnector::initPerson($user_id, $document_set);
        if(!is_null($sum_sub_proc['error'])) return $sum_sub_proc;
        //****END_SUM_SUB_BLOCK****//

        return ['error' => null,'status' => 'ok'];
    }


    /**
     * Repeated uploads
     * @param string $user_id
     * @param string $sum_sub_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function repeatedUploads(Request $request, string $user_id)
    {
        //updating person_data
        try {
            $person_data = PersonData::where('user_id',$user_id)->firstOrFail();
        } catch (ModelNotFoundException $e) {

            return ['error' => 'User data has not been found - ' . $e->getMessage()];
        }

        foreach($request->input() as $key => $value) {
            if($key !== 'q') {
                if($person_data->$key) $person_data->$key = $value;
            }
        }

        if(!$person_data->save()) return ['error' => 'Cant update users data', 'status' => 'error'];

        //updating persons files

        $document_set  = unserialize($person_data->document_set);
        $uid= $request->user()->uid;

        foreach ($request->file() as $name=>$content) {

            if(in_array($name, $document_set)) {

                //deleting old file
                $person_uploads = PersonUploads::where(['user_id' => $user_id, 'name' => $name])->first();
                if(empty($person_uploads->path)) return ['error' =>'cant find users document path','status' => 'error'];
                $ps_delete = Storage::delete($person_uploads->path);
                if(!$ps_delete) return ['error' => 'Cant delete users old file', 'status' => 'delete'];

                //storing new fixed file

                $file = $request->file($name);
                $content = $file->get();
                $encryptedContent = DocumentorEncryptor::encrypt($content,$uid);
                $file_random_name = Str::random(6);
                $path = 'person/' .$uid.'/' .$name . '/'. $file_random_name . '.dat';
                $upload = Storage::put($path,$encryptedContent);
                if(!$upload) return response()->json(['error'=> 'File '. $name .' has not been uploaded'],400);


                //updating file data in database

                $person_uploads->type = $request->file($name)->getClientOriginalExtension();
                $person_uploads->size = $request->file($name)->getClientSize();
                $person_uploads->path = $path;
                if(!$person_uploads->save()) return ['error'=> 'Person uploads has not been updated to database!'];

            } else {
                //if this file is absolutely new
                $file = $request->file($name);
                $content = $file->get();
                $encryptedContent = DocumentorEncryptor::encrypt($content,$uid);
                $file_random_name = Str::random(6);
                $path = 'person/' .$uid.'/' .$name . '/'. $file_random_name . '.dat';
                $upload = Storage::put($path,$encryptedContent);
                if(!$upload) return ['error'=> 'File '. $name .' has not been uploaded'];

                $person_uploads = new PersonUploads([
                    'user_id' => $user_id,
                    'status_id' => 1,
                    'name' => $name,
                    'type' => $request->file($name)->getClientOriginalExtension(),
                    'size' => $request->file($name)->getClientSize(),
                    'path' => $path
                ]);

                if(!$person_uploads->save()) return ['error'=> 'Person uploads has not been saved to database!'];

                //updating person document_set

                $document_set = unserialize($person_data->document_set);
                $document_set[] = $name;

                $person_data->document_set = serialize($document_set);
                if(!$person_data->save()) return ['error' => 'Cant update users document_set', 'status' => 'error'];
            }
        }

        //********SUM_SUB_BLOCK********//

        $sum_sub_proc =  SumSubConnector::repeatedPersonUploads($user_id, $document_set);
        if(!is_null($sum_sub_proc['error'])) return $sum_sub_proc;
        //****END_OF_THE_SUM_SUB_BLOCK***//

        return ['error' => null,'status' => 'ok'];

    }


}
