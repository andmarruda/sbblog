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
        <label for="username" class="form-label">{{__('adminTemplate.user.form.user')}}</label>
        <input type="email" maxlength="255" class="form-control" id="username" name="username" placeholder="{{__('adminTemplate.user.form.user')}}" required value="{{$user->email ?? ''}}">
    </div>
    <div class="mb-3">
        <label for="pass" class="form-label">{{__('adminTemplate.user.form.pass')}}</label>
        <input type="password" maxlength="150" class="form-control" id="pass" name="pass" placeholder="{{__('adminTemplate.user.form.pass')}}" required>
    </div>
    <div class="mb-3">
        <label for="pass_confirmation" class="form-label">{{__('adminTemplate.user.form.confirmPass')}}</label>
        <input type="password" maxlength="150" class="form-control" id="pass_confirmation" name="pass_confirmation" placeholder="{{__('adminTemplate.user.form.confirmPass')}}" required>
    </div>

    @if(!is_null(session('saved')))
        @include('utils.alertSuccess', ['message' => __('adminTemplate.user.okmessage')])
    @endif

    @include('utils.alertError')

    <div class="mb-3">
        <button type="submit" class="btn btn-primary"><i class="fa-regular fa-floppy-disk"></i> {{__('adminTemplate.form.btn.save')}}</button>
        <button type="button" class="btn btn-outline-primary"><i class="fa fa-search"></i> {{__('adminTemplate.form.btn.search')}}</button>
    </div>
</form>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        if(document.getElementById('id').value.length > 0){
            document.getElementById('pass').removeAttribute('required');
            document.getElementById('pass_confirmation').removeAttribute('required');
        }
    });

    const changePass = () => {
        if(document.getElementById('id').value.length == 0)
            return;

        let p = document.getElementById('pass');
        let cp = document.getElementById('pass_confirmation');
        if(p.value.length > 0 || cp.value.length > 0){
            p.setAttribute('required', '');
            cp.setAttribute('required', '');
            return;
        }

        p.removeAttribute('required', '');
        cp.removeAttribute('required', '');
    };

    document.getElementById('pass').addEventListener('blur', () => changePass());
    document.getElementById('pass_confirmation').addEventListener('blur', () => changePass());
</script>

@if(isset($configUser) && $configUser)
    <script>
        setTimeout(() => {
            location.href = "{{route('admin.logout')}}";
        }, 1000);
    </script>
@endif

@endsection