@component('mail::message')
# Change Password

<!-- click on the button below to change password. -->
<!-- 
@component('mail::button', ['url' => 'http:/localhost:4200/response-password-reset?token='.$token])
Rest Password
@endcomponent -->
Password Rest Code:<br>
---------------------------<br>
	{{ $token }}
	

<!-- Thanks,<br>
{{ config('app.name') }}
@endcomponent
 -->