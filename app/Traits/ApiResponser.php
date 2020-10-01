<?php

namespace App\Traits;

use Illuminate\Http\Response;


trait ApiResponser
{

    //return an illuminate/Http/jsonReponse

    //success response
    public function successResponse ($data, $code = Response::HTTP_OK)
    {
        return response()->json(['data' => $data], $code);
    }

     //  return an illuminate/Http/jsonReponse

    //error response
    public function errorResponse($message, $code)
    {
        return response()->json(['error' => $message, 'code' => $code], $code);
    }

    public function errorMessage($message, $code)
    {
        return response($message, $code)->header('Content-Type', 'application/json');
    }
}

