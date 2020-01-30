<?php

namespace App\Http\Middleware;

use App\BusinessData;
use App\Users;
use Closure;

class ValidateBusinessUpload
{
    protected $valid_extensions = ['pdf','jpg','png','jpeg'];
    protected $valid_size = 1000000;
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user_id = $request->user()->id;
        $business_data = BusinessData::where('user_id',$user_id)->first();

        //check if user has already been created and has uploaded documents

        if(!empty($business_data)) {
            //todo create validation rules for this case
            return $next($request);
        }


        $request->validate([
            'type_passport' => 'required|string',
        ]);

        if(!$request->hasFile('passport')) return response()->json(['message'=>'Request does not contain passport'],400);
        if(!$request->hasFile('taxpayer_form')) return response()->json(['message'=>'Request does not contain taxpayer_form'],400);
        if(!$request->hasFile('bon_doc_5')) return response()->json(['message'=>'Request does not contain bon_doc_5'],400);
        if(!$request->hasFile('biz_doc1')) return response()->json(['message'=>'Request does not contain biz_doc1'],400);

        foreach($request->allFiles() as $file) {
            if(!$this->validateFile($file)) return response()->json(['error'=> $file->getClientOriginalName() .' has not passed validation' ],400);
        }
        return $next($request);
    }
    /**
     * Validate file-size and file-extension
     * @param object $file
     */
    protected function validateFile(object $file)
    {
        if($file->getSize() > $this->valid_size)  return false;
        $ext = $file->getClientOriginalExtension();
        if(!in_array($ext, $this->valid_extensions)) return false;
        return true;
    }
}
