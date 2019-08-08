@component('mail::message')
# Fellowship Management System

Hello {{ $full_name }},<br><br> Selam {{ $full_name }}, your Fellowship Management System account has been activated for {{ $fellowship_name }} as {{ $role_name }} role.

@component('mail::button', ['url' => 'fellowshipManaegmentSystem.com'])
click here to get started
@endcomponent

Thanks,<br>
Great Commission Digital Strategy Team
@endcomponent
