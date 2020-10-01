<?php


namespace App\Http\Controllers;


use App\PostalCode;

class PostalCodeController
{
    /**
     * @return mixed
     */
    public function index()
    {
        return response()->json(PostalCode::all());
    }
}
