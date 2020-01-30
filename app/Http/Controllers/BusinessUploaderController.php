<?php

namespace App\Http\Controllers;

use App\BusinessData;
use App\BusinessUploads;
use App\Facades\DocumentorEncryptor;
use App\Facades\SumSubConnector;
use App\heritage_models\User;
use App\PersonData;
use App\SumSub;
use App\Users;
use App\UsersToCheck;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use App\heritage_models\User as Heritage_user;

class BusinessUploaderController extends Controller
{
    /**
     * Uploads business files
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function upload(Request $request)
    {
        $user_id = $request->user()->id;
        $business_data = BusinessData::where('user_id',$user_id)->first();

        if(empty($business_data)) {
            $firstCase = $this->firstCase($request, $user_id);
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
     * Upload business docs for the first time
     * @param Request $request
     * @param string $user_id
     * @return \Illuminate\Http\JsonResponse
     */
    protected function firstCase(Request $request, string $user_id)
    {
        $business_data = new BusinessData([
            'user_id' => $user_id,
            'type_passport' => $request->type_passport,
        ]);
        if(!$business_data->save()) return ['error'=> 'Business data has not been saved!'];
        $uid = $request->user()->uid;
        $document_set = [];
        foreach ($request->file() as $name=>$content) {
            $document_set[] = $name;
            $file = $request->file($name);
            $content = $file->get();
            $encryptedContent = DocumentorEncryptor::encrypt($content,$uid);
            $file_random_name = Str::random(6);
            $path = 'business/' .$uid.'/' .$name . '/'. $file_random_name . '.dat';
            $upload = Storage::put($path,$encryptedContent);
            if(!$upload) return ['error'=> 'File '. $name .' has not been uploaded'];

            $business_uploads = new BusinessUploads([
                'user_id' => $user_id,
                'status_id' => 1,
                'name' => $name,
                'type' => $request->file($name)->getClientOriginalExtension(),
                'size' => $request->file($name)->getClientSize(),
                'path' => $path
            ]);

            if(!$business_uploads->save()) return ['error'=> 'Business uploads has not been saved to database!'];

        }
        //storing dataset to business_data table

        $serializedDocSet = serialize($document_set);

        try {
            $business_data_add = BusinessData::where('user_id', $user_id)->firstOrFail();

        } catch(ModelNotFoundException $e) {
            return ['error'=> $e->getMessage()];
        }

        $business_data_add->document_set = $serializedDocSet;
        if(!$business_data_add->save()) return ['error' => 'cant save document set to business data table'];

        return ['error' => null,'status' => 'ok'];

    }

    /**
     * Repeated business uploads
     * @param Request $request
     * @param string $user_id
     */
    protected function repeatedUploads(Request $request, string $user_id)
    {
        //updating business_data
        try {
            $business_data = BusinessData::where('user_id',$user_id)->firstOrFail();
        } catch (ModelNotFoundException $e) {

            return ['error' => 'User data has not been found - ' . $e->getMessage()];
        }

        foreach($request->input() as $key => $value) {
            if($key !== 'q') {
                if($business_data->$key) $business_data->$key = $value;
            }
        }

        if(!$business_data->save()) return ['error' => 'Cant update users data', 'status' => 'error'];

        $document_set = unserialize($business_data->document_set);
        $uid= $request->user()->uid;

        //working with files

        foreach ($request->file() as $name=>$content) {

            if(in_array($name, $document_set)) {

                //deleting old file
                $business_uploads = BusinessUploads::where(['user_id' => $user_id, 'name' => $name])->first();
                if(empty($business_uploads->path)) return ['error' =>'cant find users document path','status' => 'error'];
                $ps_delete = Storage::delete($business_uploads->path);
                if(!$ps_delete) return ['error' => 'Cant delete users old file', 'status' => 'delete'];

                //storing new fixed file

                $file = $request->file($name);
                $content = $file->get();
                $encryptedContent = DocumentorEncryptor::encrypt($content,$uid);
                $file_random_name = Str::random(6);
                $path = 'business/' .$uid.'/' .$name . '/'. $file_random_name . '.dat';
                $upload = Storage::put($path,$encryptedContent);
                if(!$upload) return response()->json(['error'=> 'File '. $name .' has not been uploaded'],400);


                //updating file data in database

                $business_uploads->type = $request->file($name)->getClientOriginalExtension();
                $business_uploads->size = $request->file($name)->getClientSize();
                $business_uploads->path = $path;
                if(!$business_uploads->save()) return ['error'=> 'Business uploads has not been updated to database!'];

            } else {
                //if this file is absolutely new
                $file = $request->file($name);
                $content = $file->get();
                $encryptedContent = DocumentorEncryptor::encrypt($content,$uid);
                $file_random_name = Str::random(6);
                $path = 'business/' .$uid.'/' .$name . '/'. $file_random_name . '.dat';
                $upload = Storage::put($path,$encryptedContent);
                if(!$upload) return ['error'=> 'File '. $name .' has not been uploaded'];

                $business_uploads = new BusinessUploads([
                    'user_id' => $user_id,
                    'status_id' => 1,
                    'name' => $name,
                    'type' => $request->file($name)->getClientOriginalExtension(),
                    'size' => $request->file($name)->getClientSize(),
                    'path' => $path
                ]);

                if(!$business_uploads->save()) return ['error'=> 'Business uploads has not been saved to database!'];

                //updating business document_set

                $document_set = unserialize($business_data->document_set);
                $document_set[] = $name;

                $business_data->document_set = serialize($document_set);
                if(!$business_data->save()) return ['error' => 'Cant update users document_set', 'status' => 'error'];

                return ['error' => null,'status' => 'ok'];
            }
        }

    }
}
