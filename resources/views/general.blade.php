@extends('templates.adminTemplate')

@section('page')
<form method="post" action="{{route('admin.generalPost')}}" style="margin-top:30px;" autocomplete="off" enctype="multipart/form-data">
    <input type="hidden" name="registered_file" id="registered_file" value="{{$gen->brand_image}}">
    @csrf
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item active" aria-current="page">{{__('adminTemplate.general.title')}}</li>
        </ol>
    </nav>

    <div class="mb-3">
        <label for="slogan" class="form-label">{{__('adminTemplate.general.form.slogan')}}</label>
        <input type="text" maxlength="200" class="form-control" id="slogan" name="slogan" placeholder="{{__('adminTemplate.general.form.slogan')}}" required value="{{$gen->slogan}}">
    </div>

    <div class="mb-3">
        <label for="section" class="form-label">{{__('adminTemplate.general.form.niche')}} <small>{{__('adminTemplate.general.form.niche.small')}}</small></label>
        <input type="text" maxlength="200" class="form-control" id="section" name="section" placeholder="{{__('adminTemplate.general.form.niche')}}" required value="{{$gen->section}}">
    </div>

    <div class="mb-3">
        <label for="brand_image" class="form-label">{{__('adminTemplate.general.brandImage')}}</label>
        <input type="file" class="form-control" id="brand_image" name="brand_image">
    </div>

    @isset($saved)
        @if($saved)
            @include('utils.alertSuccess', ['message' => __('adminTemplate.general.okmessage')])
        @else
            @include('utils.alertDanger', ['message' => __('adminTemplate.general.errmessage')])
        @endif
    @endisset

    <div class="mb-3">
        <button type="submit" class="btn btn-primary">{{__('adminTemplate.form.btn.save')}}</button>
    </div>
</form>
@endsection