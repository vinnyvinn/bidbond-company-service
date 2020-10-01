<?php

namespace App\Jobs;

use App\Company;
use App\Services\BidBondService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;


class CreateCompany implements ShouldQueue
{
    use  InteractsWithQueue, Queueable, SerializesModels;

    public $companyy;

    /**
     * Create a new job instance.
     *
     * @param Company $company
     */
    public function __construct(Company $company)
    {

        $this->companyy = $company;
    }

    /**
     * Execute the job.
     *
     * @param BidBondService $bidbondService
     * @return void
     */
   public function handle(BidBondService $bidbondService)
    {
        info('Creating Company Service..'.$this->companyy->company_unique_id);
        $bidbondService->createCompany([
            'id' => $this->companyy->company_unique_id,
            'name' => $this->companyy->name,
            'crp' => $this->companyy->crp,
            'postal_address' => $this->companyy->postal_address,
            'postal_code_id' => $this->companyy->postal_code_id,
            'type' => 'user',
        ]);
    }
}
