<?php

namespace App\Http\Controllers;

use App\Company;
use App\CompanyCache;
use App\Director;
use App\Jobs\CreateCompany;
use App\Mail\CompanyEmailChange;
use App\Services\BidBondService;
use App\Traits\SearchCompany;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class CompanyController extends Controller
{
    use SearchCompany;

    protected $bidBondService;

    public function __construct(BidBondService $bidBondService)
    {
        $this->bidBondService = $bidBondService;
    }

    public function index()
    {
        return Company::latest()->paginate();
    }

    public function approvedCompanies(Request $request)
    {
        return Company::approved()->paginate($request->per_page ?? 15);
    }

    public function deletedCompanies()
    {
        return Company::onlyTrashed()->paginate();
    }

    public function options()
    {
        return Company::approved()->get(['name', 'company_unique_id']);
    }

    public function getUserCompanies(Request $request)
    {
        $company_ids = DB::table('company_user')->select('company_id')->where('user_id', $request->userid)->pluck('company_id');
        $user_companies = Company::whereIn('id', $company_ids->all());
        $perPage = $request->has('per_page') ? $request->per_page : 5;
        $user_companies->limit($perPage);
        $user_companies = $user_companies->get();
        $director = Director::where('email', $request->email)->first();
        if ($director) {
            $director_companies = $director->companies()->whereNotIn('company_unique_id', $company_ids);
            $director_companies->limit($perPage);
            $director_companies->each(function ($company) use ($user_companies) {
                $user_companies->push($company);
            });
        }
        return $user_companies;
    }

    /**
     * @param $company_id
     * @param $user_id
     * @return boolean json
     */
    public function getCompanyUser($company_id, $user_id)
    {
        return response()->json(
            Company::uniqueId($company_id)
                ->join('company_user', 'companies.id', 'company_user.company_id')
                ->where('company_user.user_id', $user_id)
                ->first()
        );
    }

    public function getCompanyUsers($company_id)
    {
        $company = Company::uniqueId($company_id)->firstOrFail();
        return response()->json(
            DB::table('company_user')
                ->select('user_id', 'creator')
                ->where('company_id', $company->id)
                ->get()
        );
    }

    public function getApprovedUserCompanies(Request $request)
    {
        $user_companies = Company::approved()->join('company_user', 'companies.id', 'company_user.company_id')
            ->where('company_user.user_id', $request->userid)
            ->get();
        $director = Director::where('email', $request->email)->first();
        if (!$director) {
            return $user_companies;
        }
        $company_ids = $user_companies->pluck('company_unique_id');
        $director_companies = Company::approved()->join('company_director', 'companies.id', 'company_director.company_id')
            ->whereNotIn('companies.company_unique_id', $company_ids)
            ->where('company_director.director_id', $director->id)
            ->get();
        $director_companies->each(function ($company) use ($user_companies) {
            $user_companies->push($company);
        });
        return $user_companies;
    }

    public function optionsByUser(Request $request)
    {
        $user_companies = Company::approved()->select('name', 'company_unique_id')
            ->join('company_user', 'companies.id', 'company_user.company_id')
            ->where('company_user.user_id', $request->userid)
            ->get();
        $director = Director::where('email', $request->email)->first();
        if (!$director) {
            return $user_companies;
        }
        $company_ids = $user_companies->pluck('company_unique_id');
        $director_companies = Company::approved()->select('name', 'company_unique_id')
            ->join('company_director', 'companies.id', 'company_director.company_id')
            ->whereNotIn('companies.company_unique_id', $company_ids)
            ->where('company_director.director_id', $director->id)
            ->get();
        $director_companies->each(function ($company) use ($user_companies) {
            $user_companies->push($company);
        });
        return $user_companies;
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'bail|required|max:191',
            'email' => 'bail|required|email|max:191',
            'phone_number' => 'bail|required|max:15',
            'postal_address' => 'required',
            'physical_address' => 'required',
            'group_id' => 'bail|required|numeric',
            'postal_code_id' => 'bail|required|numeric|exists:postal_codes,id',
            'crp' => 'required|unique:companies,crp',
            'kra_pin' => 'required',
            'user' => 'bail|required|numeric',
            'kyc_status' => 'bail|required|in:0,1'
        ]);

        do {
            $unique_id = getToken(5);
        } while (Company::uniqueId($unique_id)->count() > 0);

        $request['company_unique_id'] = $unique_id;
        $request['paid'] = 0;
        if (!$request->kyc_status) {
            $request['approval_status'] = 'approved';
        }
        $company = Company::create($request->except('user'));
        if ($company->approval_status == 'approved') {
            Bus::dispatch(new CreateCompany($company));
            SearchCompany::createAccount($company->company_unique_id);
        }
        DB::table('company_user')->insert([
            'user_id' => $request->user,
            'company_id' => $company->id,
            'creator' => 1,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
        return response()->json(["company" => $company, 'code' => 200]);
    }

    public function addFileToCompany(Request $request)
    {
        $company = Company::uniqueId($request->company_id)->first();
        $company->attachments()->create(['name' => $request->filename, 'user_id' => $request->user]);
        return 'Added';
    }

    public function performCompanySearch(Request $request)
    {
        $data = $request->all();
        $json = $this->companyCR12Search($data['companyid']);
        $res = $json->getOriginalContent();
        if (isset($res['error'])) {
            return response()->json($res);
        }

        $company = Company::uniqueId($data['companyid'])->first();
        $companycache = CompanyCache::where('registration_number', $company->crp)->first();
        if (!$companycache) {
            return $this->errorMessage('Company with the registration number provided NOT FOUND', 400);
        }
        $this->updateCompanyFromCache($companycache, $company);
        if ($request->kyc_status) {
            $this->checkApprovalStatus($company->crp, $company->id);
        }
        return $this->successResponse("Company " . $company->name . " is registered");
    }

    /**
     * @param $companycache
     * @param $company
     */
    private function updateCompanyFromCache(CompanyCache $companycache, Company $company): void
    {
        if ($companycache->kra_pin !== "N/A") {
            $company->kra_pin = $companycache->kra_pin;
        }
        $company->name = $companycache->business_name;
        $company->save();
    }

    public function performCompanySearchByName(Request $request)
    {
        return Company::where('name', 'LIKE', '%' . $request->string . '%')->get();
    }

    public function show($company_unique_id)
    {
        $company = Company::uniqueId($company_unique_id)->firstOrFail();
        $company = $company->load('directors');
        $current_directors = $company->directors;
        $docs = DB::table('attachments')
            ->where('attachable_type', '=', 'App\Company')
            ->where('attachable_id', $company->id)->get(['name']);
        $docs->map(function ($doc) {
            return $doc->path = 'app/Company/' . $doc->name;
        });
        $count_of_verified_directors = $current_directors->where('pivot.verified', 0)->count();
        $cached_company = CompanyCache::where('registration_number', $company->crp)->first();
        if ($cached_company) {
            $count_of_directors_in_cache = $this->getCacheDirectorCount($cached_company);
        } else {
            $count_of_directors_in_cache = 0;
        }
        $remaining_dirs = (int)($count_of_directors_in_cache - $count_of_verified_directors);
        $company->current_director = $count_of_verified_directors;
        $company->directors = $current_directors;
        $company->remaining_directors = $remaining_dirs;
        $company->docs = $docs;
        $company->account_id = $company->account;
        $company->company_details = $cached_company;
        $company->director_details = isset($cached_company) ? $cached_company->directors()->first() : [];

        return $this->successResponse($company);
    }

    public function unique($company_unique_id)
    {
        return Company::uniqueId($company_unique_id)->firstOrfail();
    }

    public function update(Request $request, $company_id)
    {
        $data = $this->validate($request, [
            'name' => 'bail|required|max:191',
            'email' => 'bail|required|email|max:191',
            'phone_number' => 'bail|required|max:15',
            'postal_address' => 'required',
            'physical_address' => 'required',
            'postal_code_id' => 'bail|required|numeric|exists:postal_codes,id',
            'crp' => 'required',
            'group_id' => 'bail|required|numeric',
            'kra_pin' => 'required',
            'relationship_manager_id' => 'nullable|numeric'
        ]);
        $company = Company::findOrFail($company_id);
        $old_email = $company->email;
        $company->update($data);
        if ($request->email != $old_email) {
            Mail::to($old_email)->cc($company->email)->queue(new CompanyEmailChange($company, $old_email));
        }
        $this->bidBondService->updateCompany([
            'company_id' => $company->company_unique_id,
            'crp' => $data['crp'],
            'name' => $data['name'],
            'postal_address' => $data['postal_address'],
            'postal_code_id' => $data['postal_code_id']
        ]);
        return response()->json($company);
    }

    public function destroy($id)
    {
        Company::findOrFail($id)->delete();
        return $this->successResponse("Company deleted successfully");
    }

    public function restore($id)
    {
        Company::withTrashed()->findorfail($id)->restore();
        return $this->successResponse("Company restored successfully");
    }

    public function getUserId($company_id)
    {
        return $this->successResponse(DB::table('company_user')
            ->where('company_id', $company_id)->first());
    }

    public function countUserCompanies(Request $request)
    {
        return DB::table('company_user')
            ->join('companies', 'companies.id', 'company_user.company_id')
            ->where('company_user.user_id', $request->userid)
            ->where('companies.created_at', '>=', $request->last_period_date['date'])
            ->whereNull('companies.deleted_at')
            ->count();
    }

    public function fetchUserCompanies($user_id)
    {
           return DB::table('company_user')
            ->join('companies', 'companies.id', 'company_user.company_id')
            ->where('company_user.user_id',$user_id)
            ->whereNull('companies.deleted_at')
            ->get();
    }


    public function attachCompanyUser(Request $request)
    {

        $this->validate($request, [
            'user_id' => 'required', 'company_id' => 'required'
        ]);

        $company = Company::uniqueId($request->company_id)->firstorFail();
        $users = DB::table('company_user')
            ->select('user_id', 'company_id', 'creator')
            ->where('company_id', $company->id)->get();
        $owner_exists = $users->where('creator', 1)->first();
        $user_exists = $users->where('user_id', $request->user_id)->where('company_id', $company->id)->first();

        if ($user_exists) {
            return $this->errorResponse("User already exists", 422);
        }
        DB::table('company_user')->insert([
            'user_id' => $request->user_id,
            'company_id' => $company->id,
            'creator' => $owner_exists ? 0 : 1,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
        return response()->json(["message" => "User attached to company", "code" => 201]);
    }

    public function detachUser(Request $request)
    {
        $this->validate($request, [
            'user_id' => 'required', 'company_id' => 'required'
        ]);
        $company = Company::uniqueId($request->company_id)->firstorFail();
        DB::table('company_user')
            ->where('user_id', $request->user_id)
            ->where('company_id', $company->id)
            ->delete();
        return response()->json(["message" => "User detached from company", "code" => 200]);
    }

    public function getCompanyById($id)
    {
        return Company::findOrFail($id);
    }

    public function approveCompany($company_unique_id)
    {
        $this->setCompanyApproved($company_unique_id);
    }

    public function approveCompanyByAdmin(Request $request, $company_unique_id)
    {
        $this->validate($request, ['user_id' => 'required|numeric']);
        $company = Company::uniqueId($company_unique_id)->first();
        if (!$company) {
            return response()->json(["error" => "Company does not exist", "code" => 400], 400);
        }
        if ($company->approval_status == 'approved') {
            return response()->json(["company" => $company->load('postal_code'), "code" => 200]);
        }
        $exists = DB::table('company_user')->where('user_id', $request->user_id)->where('company_id', $company->id)->exists();
        if (!$exists) {
            DB::table('company_user')->insert([
                'user_id' => $request->user_id,
                'company_id' => $company->id,
                'creator' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
        $company->update(['approval_status' => 'approved']);
        Bus::dispatch(new CreateCompany($company));
        SearchCompany::createAccount($company->company_unique_id);
        return response()->json(["company" => $company->load('postal_code'), "code" => 200]);
    }

    public function updatePaymentStatus(Request $request)
    {
        $company = Company::uniqueId($request->account)->firstorFail();
        $company->update(['paid' => 1]);
        return $company;
    }

    function getCompaniesById(Request $request)
    {
        return $this->successResponse(Company::whereIn('company_unique_id', $request->company_unique_ids)->latest()->get());
    }

    function getCompanyByRegistrationNumber(Request $request)
    {
        return $this->successResponse(Company::where('crp', $request->crp)->first());
    }

}
