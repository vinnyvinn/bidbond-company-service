<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponser;
use App\Company;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use App\Group;
use DB;

class MarketingController extends Controller
{
  use ApiResponser;

  public function createGroup(Request $request)
  {
      $validator = Validator::make($request->all(), [
          'name' => 'bail|required|max:191|unique:groups',
      ]);
      if ($validator->fails()) {
          return response()->json([
              'status' => 'error',
              'error' => [
                  'code' => 'input_invalid',
                  'message' => $validator->errors()->all()
              ]
          ], 422);
      }

    $group = Group::create($request->all());

    return $group;
  }

  public function attachCompany(Request $request) {

    $data = $request->all();

    DB::table('group_company')->insert(['group_id' => $data['group_id'], 'company_id' => $data['company_id']]);

    return 'attached';
  }

  public function detachCompany(Request $request) {

    $data = $request->all();

    DB::table('group_company')->where('group_id', $data['group_id'])->where('company_id', $data['company_id'])->delete();

    return 'detached';
  }

  public function listGroups () {

    $groups = Group::all();

    $groups->map(function($group){
      $members = DB::table('group_company')
        ->join('groups', 'groups.id', '=', 'group_company.group_id')
        ->join('companies', 'companies.id', '=', 'group_company.company_id')
        ->where('group_id', $group->id)->get();

        $group['members'] = $members;

        return $group;
    });

    return $groups;

  }

  public function fetchCompaniesByGroupId($id) {

    $members = DB::table('group_company')
      ->join('groups', 'groups.id', '=', 'group_company.group_id')
      ->join('companies', 'companies.id', '=', 'group_company.company_id')
      ->where('group_id', $id)->get();

      return $members;
  }
}
