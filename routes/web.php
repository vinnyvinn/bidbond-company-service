<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

use Illuminate\Support\Facades\Artisan;
$router->get('companies', 'CompanyController@index');
$router->post('companies', 'CompanyController@store');
$router->get('all-companies', 'CompanyController@allCompanies');
$router->get('approved-companies', 'CompanyController@approvedCompanies');
$router->get('approved-companies/options', 'CompanyController@options');
$router->post('approved-companies/user/options', 'CompanyController@optionsByUser');
$router->post('verify-company-director','DirectorController@approveSmsVerification');
$router->post('getUserCompanies', 'CompanyController@getUserCompanies');
$router->get('company/{company_id}/users','CompanyController@getCompanyUsers');
$router->get('company/{company_id}/{user_id}', 'CompanyController@getCompanyUser');
$router->post('company/filter-companies/{user_id}', 'CompanyController@fetchUserCompanies');
$router->post('getApprovedUserCompanies', 'CompanyController@getApprovedUserCompanies');
$router->get('postalcodes', 'PostalCodeController@index');
$router->post('approve-company/{company_unique_id}','CompanyController@approveCompany');
$router->post('approve-company-by-admin/{company_unique_id}','CompanyController@approveCompanyByAdmin');
$router->post('create-director', 'DirectorController@createDirector');
$router->post('manualCreate', 'DirectorController@manualCreate');
$router->get('companies/deleted', 'CompanyController@deletedCompanies');
$router->post('companies/{id}restore', 'CompanyController@restore');
$router->get('companies/{company_unique_id}', 'CompanyController@show');
$router->get('companies/unique/{company_unique_id}', 'CompanyController@unique');
$router->post('companies/company_id', 'CompanyController@getCompaniesById');
$router->post('company/byregistration', 'CompanyController@getCompanyByRegistrationNumber');
$router->put( 'companies/{company_id}', 'CompanyController@update');
$router->delete( 'companies/{id}', 'CompanyController@destroy');
$router->post('companysearch', 'CompanyController@performCompanySearch');
$router->post('companysearch-by-name','CompanyController@performCompanySearchByName');
$router->get('getUserId/{company_id}', 'CompanyController@getUserId');
$router->post('countUserCompanies', 'CompanyController@countUserCompanies');
$router->post('attach-user', 'CompanyController@attachCompanyUser');
$router->post('detach-user', 'CompanyController@detachUser');
$router->post('add-file-to-company', 'CompanyController@addFileToCompany');
$router->post('update-director', 'DirectorController@updateDirector');

$router->post('create-group', 'MarketingController@createGroup');
$router->post('attach-group', 'MarketingController@attachCompany');
$router->post('detach-group', 'MarketingController@detachCompany');
$router->get('list-groups', 'MarketingController@listGroups');
$router->get('companies-by-groupid/{id}', 'MarketingController@fetchCompaniesByGroupId');
$router->get('check-company-approval', 'DirectorController@approvalCompanyStatus');

$router->get('director-company-approval', 'DirectorController@directorCompanyApproval');
$router->get('company-directors/{id}', 'DirectorController@getCompanyDirectors');
$router->get('company-by-id/{id}', 'CompanyController@getCompanyById');
$router->post('update-director-details', 'DirectorController@updateDirectorDetails');
$router->post('update-payment-status', 'CompanyController@updatePaymentStatus');

$router->get('migrate',function (){
    Artisan::call('migrate:fresh --seed');
    return response()->json(["message"=>"Company migrate fresh success"]);
});













