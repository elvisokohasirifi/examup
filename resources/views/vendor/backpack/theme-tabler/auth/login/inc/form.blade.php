@section('after_scripts')
<script type="module">
    let input = document.querySelector('.password-visibility-toggler input');
    let buttons = document.querySelectorAll('.password-visibility-toggler a');

    buttons.forEach(button => {
        button.addEventListener('click', () => {
            buttons.forEach(b => b.classList.toggle('d-none'));
            input.type = input.type === 'password' ? 'text' : 'password';
            input.focus();
        });
    });
</script>
@endsection

<h2 class="h2 text-center my-4">{{ trans('backpack::base.login') }}</h2>
<form method="POST" action="{{ route('backpack.auth.login') }}" autocomplete="off" novalidate="">
    @csrf
    <div class="mb-3">
        <label class="form-label" for="{{ $username }}">{{ trans('backpack::base.'.strtolower(config('backpack.base.authentication_column_name'))) }}</label>
        <input autofocus tabindex="1" type="text" name="{{ $username }}" value="{{ old($username) }}" id="{{ $username }}" class="form-control {{ $errors->has($username) ? 'is-invalid' : '' }}">
        @if ($errors->has($username))
            <div class="invalid-feedback">{{ $errors->first($username) }}</div>
        @endif
    </div>
    <div class="mb-2">
        <label class="form-label" for="password">
            {{ trans('backpack::base.password') }}
        </label>
        <div class="input-group input-group-flat password-visibility-toggler">
            <input tabindex="2" type="password" name="password" id="password" class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}" value="">
            @if(backpack_theme_config('options.showPasswordVisibilityToggler'))
            <span class="input-group-text p-0 px-2">
                <a href="#" data-input-type="text" class="link-secondary p-2" data-bs-toggle="tooltip" aria-label="{{ trans('backpack.theme-tabler::theme-tabler.password-show') }}" data-bs-original-title="{{ trans('backpack.theme-tabler::theme-tabler.password-show') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-eye" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"></path><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6"></path></svg>
                </a>
                <a href="#" data-input-type="password" class="link-secondary p-2 d-none" data-bs-toggle="tooltip" aria-label="{{ trans('backpack.theme-tabler::theme-tabler.password-hide') }}" data-bs-original-title="{{ trans('backpack.theme-tabler::theme-tabler.password-hide') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-eye-off" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10.585 10.587a2 2 0 0 0 2.829 2.828" /><path d="M16.681 16.673a8.717 8.717 0 0 1 -4.681 1.327c-3.6 0 -6.6 -2 -9 -6c1.272 -2.12 2.712 -3.678 4.32 -4.674m2.86 -1.146a9.055 9.055 0 0 1 1.82 -.18c3.6 0 6.6 2 9 6c-.666 1.11 -1.379 2.067 -2.138 2.87" /><path d="M3 3l18 18" /></svg>
                </a>
            </span>
            @endif
        </div>
        @if ($errors->has('password'))
            <div class="invalid-feedback">{{ $errors->first('password') }}</div>
        @endif
    </div>
    <div class="d-flex justify-content-between align-items-center mb-2">
        <label class="form-check mb-0">
            <input name="remember" tabindex="3" type="checkbox" class="form-check-input">
            <span class="form-check-label">{{ trans('backpack::base.remember_me') }}</span>
        </label>
        @if (backpack_users_have_email() && backpack_email_column() == 'email' && config('backpack.base.setup_password_recovery_routes', true))
            <div class="form-label-description">
                <a tabindex="4" href="{{ route('backpack.auth.password.reset') }}">{{ trans('backpack::base.forgot_your_password') }}</a>
            </div>
        @endif
    </div>
    <div class="form-footer">
        <button tabindex="5" type="submit" class="btn btn-primary w-100">{{ trans('backpack::base.login') }}</button>
    </div>
</form>

<div class="text-center text-secondary my-3">or</div>

<a href="{{ route('admin.auth.google.redirect') }}" class="btn btn-outline-secondary w-100 d-flex align-items-center justify-content-center gap-2">
    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 48 48" aria-hidden="true">
        <path fill="#FFC107" d="M43.611 20.083H42V20H24v8h11.303C33.654 32.657 29.21 36 24 36c-6.627 0-12-5.373-12-12s5.373-12 12-12c3.059 0 5.842 1.154 7.959 3.041l5.657-5.657C34.053 6.053 29.27 4 24 4C12.955 4 4 12.955 4 24s8.955 20 20 20s20-8.955 20-20c0-1.341-.138-2.65-.389-3.917Z"/>
        <path fill="#FF3D00" d="M6.306 14.691l6.571 4.819C14.655 15.108 18.961 12 24 12c3.059 0 5.842 1.154 7.959 3.041l5.657-5.657C34.053 6.053 29.27 4 24 4c-7.682 0-14.326 4.337-17.694 10.691Z"/>
        <path fill="#4CAF50" d="M24 44c5.168 0 9.868-1.977 13.409-5.192l-6.19-5.238C29.144 35.091 26.679 36 24 36c-5.189 0-9.617-3.317-11.283-7.946l-6.522 5.025C9.525 39.556 16.72 44 24 44Z"/>
        <path fill="#1976D2" d="M43.611 20.083H42V20H24v8h11.303a12.042 12.042 0 0 1-4.084 5.571h.003l6.19 5.238C36.971 39.205 44 34 44 24c0-1.341-.138-2.65-.389-3.917Z"/>
    </svg>
    <span>Continue with Google</span>
</a>
