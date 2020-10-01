@component('mail::message')
<h4>Company Email Change</h4>
<p>The company {{ $company->name }} registered email on {{ config('app.name') }} had been updated from {{ $old_email }} to {{ $company->email }}.</p>

@component('mail::subcopy',[])
<p><b>Kind regards,</b></p>
<p><b>{{ config('app.name') }} Customer Experience Team.</b></p>
@endcomponent

@endcomponent
