<?php

namespace App\Http\Controllers;

use App\Code;
use App\Company;
use App\Director;
use App\Traits\SearchCompany;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;


class DirectorController extends Controller
{
    use SearchCompany;

    public function manualCreate(Request $request)
    {
        $data = $request->all();

        $director = DB::table('company_director')->select('*')
            ->join('companies', 'companies.id', '=', 'company_director.company_id')
            ->join('directors', 'directors.id', '=', 'company_director.director_id')
            ->where('companies.id', $data['company_id'])
            ->where('directors.id_number', $data['id_number'])
            ->whereNull('directors.deleted_at')
            ->whereNull('companies.deleted_at')
            ->first();

        $company = Company::findOrFail($data['company_id']);

        if ($director) {
            return $this->errorResponse('Director Already Exists', 400);
        }

        $director = new Director();
        $director->id_number = $data['id_number'];
        $director->firstname = $data['firstname'];
        $director->lastname = $data['lastname'];
        $director->middlename = $data['middlename'] ?? '';
        $director->phone_number = $data['phone_number'];
        $director->verified_phone = 1;
        $director->email = $data['email'];
        $director->save();

        $director->companies()->attach($company->id, [
            'company_id' => $company->id,
            'director_id' => $director->id,
            'verification_code' => md5(uniqid(rand(), true))
        ]);

        $this->sendDirectorCreatedSMS($director, $company, $data);

        $this->checkApprovalStatus($company->crp, $company->id);

        return response()->json(["message" => "Director added successfully to company", "code" => 200]);
    }

    public function createDirector(Request $request)
    {
        $data = $request->all();
        $company = Company::findOrFail($data['companyid']);
        $director = $company->directors()->where('id_number', $data['id_number'])->first();

        if ($director) {
            if ($data['phone_number'] == $data['user_phone']) {
                $this->setDirectorVerified($director, $company);
                $this->checkApprovalStatus($company->crp, $company->id);
                return response()->json(["message" => "Director successfully verified", "code" => 200]);
            }

            $this->setDirectorVerificationCode($director, $company);
            $this->sendDirectorCreatedSMS($director, $company, $data);

            return response()->json([
                "message" => "Director added successfully to company.Please verify using the link sent to " . $director->phone_number,
                "code" => 200
            ]);
        }

        $new_director = $this->getOrCreateDirector($company, $data);

        if (!$new_director['director']) {
            return response()->json(["message" => $new_director['message'], "code" => 400], 400);
        }

        $new_director = $new_director['director'];
        $new_director->save();

        if ($data['phone_number'] == $data['user_phone']) {
            $this->setDirectorVerified($new_director, $company);
            $this->checkApprovalStatus($company->crp, $company->id);
            return response()->json(["message" => "Director successfully verified", "code" => 200]);
        }

        $this->setDirectorVerificationCode($new_director, $company);
        $this->sendDirectorCreatedSMS($new_director, $company, $data);

        return response()->json([
            "message" => "Director added successfully to company.Please verify using the link sent to to " . $new_director->phone_number,
            "code" => 200
        ]);
    }

    private function setDirectorVerificationCode($director, $company): void
    {
        $director->companies()->updateExistingPivot($company->id, [
            'verification_code' => md5(uniqid(rand(), true)),
            'updated_at' => Carbon::now()
        ]);
    }

    private function setDirectorVerified($director, $company): void
    {
        $director->companies()->updateExistingPivot($company->id, ['verified' => 1, 'updated_at' => Carbon::now()]);
    }

    public function approvalCompanyStatus(Request $request)
    {
        $company = $this->checkApprovalStatus($request->crp, $request->company_id);

        if (!$company) {
            return response()->json(['error' => 'Company not found', 'code' => 400], 400);
        }

        return response()->json([
            'code' => 200,
            'company' => $company
        ]);
    }

    function directorCompanyApproval(Request $request)
    {
        DB::table('company_director')->where('company_id', $request->company_id)
            ->where('director_id', $request->director_id)
            ->update(['verified' => 1, 'updated_at' => Carbon::now()]);

        return response()->json([
            'code' => 200,
            'company' => $this->checkApprovalStatus($request->crp, $request->company_id)
        ]);
    }

    public function updateDirector(Request $request)
    {
        $data = $request->all();

        $director = Director::where('email', $data['user_email'])->first();

        if (!$director) {
            return $this->errorResponse('Director not found', 404);
        }

        $director->fill($request->except('user_email', 'password'));

        if ($director->isClean()) {
            return $this->errorResponse('At least one value must change', 422);
        }

        $director->save();

        return response()->json(['director' => $director, 'code' => 200]);
    }

    public function activateEmailFromLink(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'bail|required|exists:codes,code_email'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'error' => ['message' => $validator->errors()->all()]
            ], 422);
        }

        $code = Code::where('code_email', $request->code)->first();

        $director = Director::where("email", $code->email)->first();

        if (!$director) {
            return $this->errorResponse('The account does not exist', 400);
        }

        if ($director->verified_email == 1) {
            return $this->errorResponse('Director email already verified', 422);
        }

        $director->verified_email = 1;
        $director->save();

        return $this->successResponse('Director email has been verified');
    }

    public function registerPhone(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'bail|required|numeric:12',
            'code' => 'bail|required|numeric:6'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'error' => ['message' => $validator->errors()->all()]
            ], 422);
        }

        $phone = $request->phone_number;

        $code = Code::where('phone_number', $phone)->where('code_phone', $request->input('code'))->first();

        if (!$code) {
            return $this->errorResponse('No code found for that phone number', 404);
        }

        $directorExists = Director::where('phone_number', $phone)->first();

        if ($directorExists) {
            $this->successResponse('Director phone number otp verified');
        }

        return $this->errorResponse('No code found for that director number', 404);
    }

    public function getCompanyDirectors($id)
    {
        $current_directors = DB::table('company_director')->select('companies.id', 'directors.id', 'directors.firstname', 'directors.middlename', 'directors.lastname', 'directors.phone_number', 'directors.id_number', 'directors.email')
            ->join('companies', 'companies.id', '=', 'company_director.company_id')
            ->join('directors', 'directors.id', '=', 'company_director.director_id')
            ->where('companies.id', $id)
            ->get();

        return $current_directors;
    }

    public function updateDirectorDetails(Request $request)
    {
        Director::where('id_number', $request->id_number)->update(['email' => $request->email]);

        return 'success';
    }

    public function approveSmsVerification(Request $request)
    {
        $director = Director::findOrFail($request->id);
        $company_director = DB::table('company_director')
            ->where('director_id', $director->id)
            ->where('verification_code', $request->code)
            ->first();

        if (!$company_director) {
            return $this->errorResponse(['error' => true, 'message' => 'Director verification code not found!'], 422);
        }

        DB::table('company_director')
            ->where('director_id', $director->id)
            ->where('verification_code', $request->code)
            ->update(['verified' => 1, 'updated_at' => Carbon::now()]);

        $company = Company::find($company_director->company_id);
        $this->checkApprovalStatus($company->crp, $company->id);
        return $this->successResponse(['error' => false, 'data' => $director, 'company' => $company], 200);
    }
}
