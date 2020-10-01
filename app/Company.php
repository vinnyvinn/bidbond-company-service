<?php

namespace App;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $hidden = ['deleted_at'];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function directors()
    {
        return $this->belongsToMany('App\Director')->withPivot('verified', 'verification_code')->withTimestamps();
    }

    public function postal_code()
    {
        return $this->belongsTo('App\PostalCode');
    }

    public function attachments()
    {
        return $this->morphMany('App\Attachment', 'attachable');
    }

    public function scopeWhereAccount($builder, $account): void
    {
        $builder->where('account_id', $account);
    }

    public function scopeApproved($builder): void
    {
        $builder->where('approval_status', 'approved');
    }

    public function scopeUniqueId($builder, $company_id): void
    {
        $builder->where('company_unique_id', $company_id);
    }

}
