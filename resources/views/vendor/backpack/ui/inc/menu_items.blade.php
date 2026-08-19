{{-- This file is used for menu items by any Backpack v7 theme --}}
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('dashboard') }}"><i class="la la-home nav-icon"></i> {{ trans('backpack::base.dashboard') }}</a></li>
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('exam') }}"><i class="la la-file-text nav-icon"></i> Exams</a></li>
@if (backpack_user()?->isAdmin())
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('user') }}"><i class="la la-user nav-icon"></i> Users</a></li>
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('setting') }}"><i class="la la-cog nav-icon"></i> Settings</a></li>
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('activity-log') }}"><i class="la la-stream nav-icon"></i> Activity Logs</a></li>
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('log') }}"><i class="la la-terminal nav-icon"></i> Error Logs</a></li>
@endif