<?php

namespace App\Traits;

use App\Company;
use App\CompanyCache;
use App\CompanySearch;
use App\Director;
use App\DirectorCache;
use App\Jobs\CreateCompany;
use App\Jobs\SendSMS;
use App\Services\GatewayService;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use function GuzzleHttp\json_decode;

trait SearchCompany
{
    use ApiResponser;


    function companyCR12Search($id)
    {
        info('here--------');
        $company = Company::where('company_unique_id', $id)->firstOrFail();

        $companycache = CompanyCache::where('registration_number', $company->crp)->exists();

        if ($companycache) {
            return response()->json(['error' => 'You have already performed this search before!', 'code' => 400], 400);
        }

        if ($company == null) {
            return response()->json(['error' => 'You have not onboarded your company yet.', 'code' => 400]);
        }

        if ($company->paid == 0) {
            return response()->json([
                'error' => 'You have not paid for company search. Kindly make the payment for the search',
                'code' => 400], 400);
        }

        $client = new Client();

        try {
            $response = $client->get(config("services.informa.url") .
                "/search/company/byRegNo?reg_no=" . $company->crp .
                "&postal_address=29085&postal_code=00100", [
                'headers' => [
                    'Accept' => 'application/vnd.informa.v1+json',
                    'Authorization' => 'Bearer ' . config("services.informa.key"),
                ],
            ]);
            $data = json_decode($response->getBody());
          info('outside company search');
             // company search success
            if ($data->status == "success") {
                info('company search..........');
                info('inside---');
                info(print_r($company,true));
                info('==================');

                $this->saveCompanySearch($company->crp);
                $this->saveCompanyCache($data->data);
                return response()->json(['success' => 'search performed', 'code' => 200]);

            } else {

                $company->approval_status = 'rejected';

                $company->save();

                return response()->json(['error' => 'Company Not Found', 'code' => 400], 404);

            }

        } catch (ClientException $e) {

            if ($e->getCode() == 400) {

                $data = json_decode($e->getResponse()->getBody(), true);

                return response()->json(['error' => $data["error"]["message"], 'code' => 400], 400);
            }
            Log::error($e->getMessage());
            return response()->json(['error' => 'Company search not performed', 'code' => 400], 400);

        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['error' => 'Company search not performed', 'code' => 400], 400);
        }
    }

    function saveCompanyCache($data)
    {
        $companycache = CompanyCache::where('registration_number', $data->registration_number)->first();

        if (!$companycache) {
            $companycache = CompanyCache::create([
                "registration_number" => $data->registration_number,
                "registration_date" => Carbon::createFromFormat('d F Y', $data->registration_date)->format('Y-m-d'),
                "postal_address" => $data->postal_address,
                "status" => $data->status,
                "physical_address" => $data->physical_address,
                "phone_number" => $data->phone_number,
                "email" => $data->email,
                "business_name" => $data->business_name,
                "kra_pin" => $data->kra_pin
            ]);
            $partners = $data->partners;
            for ($i = 0; $i < sizeof($partners); $i++) {
                //ignore secretary, id_type = N/A
                if (strtoupper($partners[$i]->id_type) != "N/A") {
                    DirectorCache::create([
                        "name" => $partners[$i]->name,
                        "id_type" => $partners[$i]->id_type,
                        "id_number" => $partners[$i]->id_number,
                        "company_cache_id" => $companycache->id
                    ]);
                }
            }

        } else {
            $companycache->update([
                "registration_number" => $data->registration_number,
                "registration_date" => Carbon::createFromFormat('d F Y', $data->registration_date)->format('Y-m-d'),
                "postal_address" => $data->postal_address,
                "status" => $data->status,
                "physical_address" => $data->physical_address,
                "phone_number" => $data->phone_number,
                "email" => $data->email,
                "business_name" => $data->business_name,
                "kra_pin" => $data->kra_pin
            ]);
            //delete all old directors and create new
            DirectorCache::where("company_cache_id", $companycache->id)->delete();

            $partners = $data->partners;

            for ($i = 0; $i < sizeof($partners); $i++) {
                //ignore secretary, id_type = N/A
                if (strtoupper($partners[$i]->id_type) != "N/A") {
                    DirectorCache::create([
                        "name" => $partners[$i]->name,
                        "id_type" => $partners[$i]->id_type,
                        "id_number" => $partners[$i]->id_number,
                        "company_cache_id" => $companycache->id
                    ]);
                }
            }
        }
    }

    function saveCompanySearch($data)
    {
        CompanySearch::create(['registration_number' => $data]);
    }

    /**
     * @param $company
     * @param $data
     * @return array
     */
    function getOrCreateDirector($company, $data): array
    {
        $cache_company = CompanyCache::where('registration_number', $company->crp)->first();

        if (!$cache_company) {
            return ["director" => null, "message" => "Company not found in search registry"];
        }

        $cache_director = $cache_company->directors()->Where('id_number', $data['id_number'])->first();
        if (!$cache_director) {
            return ["director" => null, "message" => "Director matching ID Number not found in company search registry"];
        }

        if (!$this->directorMatchedInCache($data, $cache_director)) {
            return ["director" => null, "message" => "Director ID Number does not match the names in company search registry"];
        }

        $director = Director::where('id_number', $data['id_number'])->where('phone_number', $data['phone_number'])->first();

        if (config('services.informa.phone_search_active') && $data['kyc_status'] == 1 && $this->isSafaricomNumber($data['phone_number'])) {
            $valid = $this->searchByPhoneNId($data['phone_number'], $data['id_number']);
            if (!$valid) {
                return ["director" => null, "message" => "Director phone number does not match id number"];
            }
        }

        if ($director) {
            $director->companies()->attach($company->id);
            return ["director" => $director, "message" => "Director linked to company successfully"];
        }

        return ["director" => $this->saveDirector($data, $company), "message" => "Director created successfully"];
    }

    private function isSafaricomNumber($number)
    {
        return preg_match('/^(?:254|\+254|0)?((7(?:(?:[129][0-9])|(?:0[0-8])|(?:[9][0-9])|(?:[6][8-9])|(?:[5][7-9])|(4([0-6]|[8-9]))))|(11[0-1]))[0-9]{6}$/', $number);
    }

    private function directorMatchedInCache($data, $cache_director): bool
    {
        $usernames = collect([
            $data['first_name'],
            $data['last_name'],
            $data['middle_name'],
        ])->map(function ($name) {
            return strtoupper($name);
        });

        $cache_director_names = collect(explode(' ', $cache_director->name));

        $similar_names = $cache_director_names->intersect($usernames);

        return ($similar_names->count() > 1);

    }

    protected function saveDirector($data, $company)
    {
        $director = Director::create([
            'id_number' => $data['id_number'],
            'firstname' => $data['first_name'],
            'lastname' => $data['last_name'],
            'middlename' => $data['middle_name'],
            'phone_number' => $data['phone_number'],
            'email' => $data['email'],
            'verified_phone' => 1
        ]);
        $director->refresh();
        $director->companies()->attach($company->id);
        return $director;
    }

    function sendDirectorCreatedSMS($director, $company, $data): void
    {
        $company_director = $company->directors()->where('director_id', $director->id)->first();
        if (!$company_director) return;
        if (!$company_director->pivot->verification_code) return;

        $msg = 'You have been created as a director on ' . getenv('APP_NAME') . ' for Company '
            . $company->name . ' by ' . $data['user_name'] . ', Click ' . getenv('DIRECTOR_VERIFY_LINK') . ''
            . $company_director->pivot->verification_code . '/' . $director->id . ' to approve the this action';

        Bus::dispatch(new SendSMS($data['phone_number'], $msg));
    }


    /**
     * @param $phone
     * @param $id_number
     * @return mixed user
     */
    function searchByPhoneNId($phone, $id_number)
    {
        $phone = $this->unencodePhone($phone);
        $gateway_service = new GatewayService();

        $response = json_decode($gateway_service->getUserByPhoneNId($phone, $id_number), true);
        return $response["status"];
    }

    /**
     * @param $phone 254712704404
     * @return string eg. 0712704404
     */
    function unencodePhone($phone)
    {
        $prefix = "254";
        if ($prefix == substr($phone, 0, 3)) {
            $phone = "0" . substr($phone, 3, strlen($phone) - 3);
        }
        return $phone;
    }

    function setCompanyApproved($company_unique_id)
    {
        $company = Company::where('company_unique_id', $company_unique_id)->firstOrFail();
        $company->update(['approval_status' => 'approved']);
        Bus::dispatch(new CreateCompany($company));
        SearchCompany::createAccount($company_unique_id);
    }

    /**
     * @param CompanyCache $cached_company
     * @return int director_count
     */
    function getCacheDirectorCount(CompanyCache $cached_company)
    {
        return $cached_company->directors()->count();
    }

    /**
     * @param $crp
     * @param $company_id
     * @return Company | null
     */
    function checkApprovalStatus($crp, $company_id)
    {
        info('check approval');
        $company = Company::findOrFail($company_id);
        $cache_company = CompanyCache::where('registration_number', $crp)->first();

        $expected_directors = 0;
        if ($cache_company) {
            $expected_directors = $this->getCacheDirectorCount($cache_company);
        }

        $registered_directors = $company->directors()->wherePivot('verified', 1)->count();

        if ($registered_directors > 0 && $expected_directors < 3) {
            $this->setCompanyApproved($company->company_unique_id);
        } else if ($expected_directors > 2 && $registered_directors > 1) {
            $this->setCompanyApproved($company->company_unique_id);
        }
        $company->refresh();
        return $company;
    }
    public static function createAccount($company)
    {
        info('passing here----------');
        if (config('services.enable_create_account')) {
            $response = json_decode(GatewayService::init()->registerAccount($company), true);
               if ($response=='account_exists'){
                info('account exists');
                return;
            }
               info('updating account id---');
            Company::uniqueId($company)->first()->update(['customerid' => $response['customer_id'], 'account' => $response['account']]);
        }
    }
}


