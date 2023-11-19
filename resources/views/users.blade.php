@extends('templates.adminTemplate')

@section('page')
<form method="post" action="{{isset($user->id) ? route('user.update', $user->id) : route('user.store')}}" style="margin-top:30px" autocomplete="off">
    @csrf
    @isset($user->id)
    @method('PUT')
    @endisset

    @include('utils.breadcrumb', ['title' => __('adminTemplate.user.title')])

    @if(!is_null(session('configUser')))
    <div class="alert alert-danger">{{__('adminTemplate.user.firstUser.advice')}}</div>
    @endif

    <div class="mb-3">
        <label for="name" class="form-label">{{__('adminTemplate.user.form.name')}}</label>
        <input type="text" maxlength="255" class="form-control" id="name" name="name" placeholder="{{__('adminTemplate.user.form.name')}}" required value="{{$user->name ?? ''}}">
    </div>
    <div class="mb-3">
        <label for="email" class="form-label">{{__('adminTemplate.user.form.user')}}</label>
        <input type="email" maxlength="255" class="form-control" id="email" name="email" placeholder="{{__('adminTemplate.user.form.user')}}" required value="{{$user->email ?? ''}}">
    </div>
    <div class="mb-3">
        <label for="password" class="form-label">{{__('adminTemplate.user.form.pass')}}</label>
        <input type="password" maxlength="150" class="form-control" id="password" name="password" placeholder="{{__('adminTemplate.user.form.pass')}}" required>
    </div>
    <div class="mb-3">
        <label for="password_confirmation" class="form-label">{{__('adminTemplate.user.form.confirmPass')}}</label>
        <input type="password" maxlength="150" class="form-control" id="password_confirmation" name="password_confirmation" placeholder="{{__('adminTemplate.user.form.confirmPass')}}" required>
    </div>

    @if(!is_null(session('saved')))
        @include('utils.alertSuccess', ['message' => __('adminTemplate.user.okmessage')])
    @endif

    @include('utils.alertError')

    <div class="mb-3">
        <button type="submit" class="btn btn-primary"><i class="fa-regular fa-floppy-disk"></i> {{__('adminTemplate.form.btn.save')}}</button>
        <a href="{{route('user.index')}}" role="button" class="btn btn-outline-primary"><i class="fa fa-search"></i> {{__('adminTemplate.form.btn.search')}}</a>
    </div>
</form>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        if(document.getElementById('id').value.length > 0){
            document.getElementById('password').removeAttribute('required');
            document.getElementById('password_confirmation').removeAttribute('required');
        }
    });

    const changePass = () => {
        if(document.getElementById('id').value.length == 0)
            return;

        let p = document.getElementById('password');
        let cp = document.getElementById('password_confirmation');
        if(p.value.length > 0 || cp.value.length > 0){
            p.setAttribute('required', '');
            cp.setAttribute('required', '');
            return;
        }

        p.removeAttribute('required', '');
        cp.removeAttribute('required', '');
    };

    document.getElementById('password').addEventListener('blur', () => changePass());
    document.getElementById('password_confirmation').addEventListener('blur', () => changePass());
</script>
@endsection