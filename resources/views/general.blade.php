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

    <ul class="nav nav-tabs" id="myTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab" aria-controls="general" aria-selected="true">{{__('adminTemplate.general.generalTab')}}</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="social-tab" data-bs-toggle="tab" data-bs-target="#social" type="button" role="tab" aria-controls="social" aria-selected="false">{{__('adminTemplate.general.socialTab')}}</button>
        </li>
    </ul>
    <div class="tab-content" id="myTabContent" style="margin-top:10px;">
        <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
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
        </div>
        <div class="tab-pane fade" id="social" role="tabpanel" aria-labelledby="social-tab">
            @foreach(\App\Models\SocialNetwork::all() as $sn)
            <div class="input-group mb-3">
                <span class="input-group-text" id="basic-addon1"><img src="{{asset($sn->icon)}}" alt="{{$sn->name}}" title="{{$sn->name}}"></span>
                <input type="url" class="form-control" name="socialnetwork[{{$sn->id}}]" placeholder="{{$sn->name}}" aria-label="{{$sn->name}}">
            </div>
            @endforeach
        </div>
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