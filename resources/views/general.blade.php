@extends('templates.adminTemplate')

@section('page')
<form method="post" action="{{isset($gen) ? route('general.update', $gen->id) : route('general.store')}}" style="margin-top:30px;" autocomplete="off" enctype="multipart/form-data">
    @csrf
    @isset($gen)
    @method('PUT')
    @endisset

    @include('utils.breadcrumb', ['title' => __('adminTemplate.general.title')])

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
                <label for="page_title" class="form-label">{{__('adminTemplate.general.pageTitle')}}</label>
                <input type="text" minlength="5" maxlength="110" class="form-control" id="page_title" name="page_title" placeholder="{{__('adminTemplate.general.pageTitle')}}" required value="{{$gen->title ?? ''}}">
            </div>
            <div class="mb-3">
                <label for="page_description" class="form-label">{{__('adminTemplate.general.pageDescription')}}</label>
                <input type="text" minlength="45" maxlength="200" class="form-control" id="page_description" name="page_description" placeholder="{{__('adminTemplate.general.pageDescription')}}" required value="{{$gen->description ?? ''}}">
            </div>
            <div class="mb-3">
                <label for="slogan" class="form-label">{{__('adminTemplate.general.form.slogan')}}</label>
                <input type="text" minlength="45" maxlength="200" class="form-control" id="slogan" name="slogan" placeholder="{{__('adminTemplate.general.form.slogan')}}" required value="{{$gen->slogan ?? ''}}">
            </div>

            <div class="mb-3">
                <label for="section" class="form-label">{{__('adminTemplate.general.form.niche')}} <small>{{__('adminTemplate.general.form.niche.small')}}</small></label>
                <input type="text" minlength="5" maxlength="200" class="form-control" id="section" name="section" placeholder="{{__('adminTemplate.general.form.niche')}}" required value="{{$gen->section ?? ''}}">
            </div>

            <div class="mb-3">
                <label for="brand_image" class="form-label">{{__('adminTemplate.general.brandImage')}}</label>
                <input type="file" class="form-control" id="brand_image" name="brand_image">
            </div>

            <div class="mb-3">
                <label for="comment_config_id" class="form-label">{{__('adminTemplate.general.comment_config')}}</label>
                <select class="form-control" name="comment_config_id" id="comment_config_id">
                @foreach ($comment_configs as $config)
                    <option value="{{$config->id}}">{{$config->description}}</option>
                @endforeach
                </select>
            </div>

            @include('utils.comboActive', ['active' => $gen->autoconvert_webp ?? NULL, 'label' => __('adminTemplate.general.autoconvert_webp'), 'comboId' => 'autoconvert_webp'])

            <div class="mb-3">
                <label for="google_analytics" class="form-label">{{__('adminTemplate.general.googleAnalytics')}}</label>
                <input type="text" maxlength="15" class="form-control" id="google_analytics" name="google_analytics" placeholder="{{__('adminTemplate.general.googleAnalytics')}}" value="{{$gen->google_analytics ?? ''}}">
            </div>

            <div class="mb-3">
                <label for="google_ads_script" class="form-label">{{__('adminTemplate.general.googleAds')}}</label>
                <textarea class="form-control" id="google_ads_script" rows="2" style="resize: none;" name="google_ads_script" placeholder="{{__('adminTemplate.general.googleAds')}}">{{$gen->google_ads_script ?? ''}}</textarea>
            </div>

            <div class="mb-3">
                <label for="google_optimize" class="form-label">{{__('adminTemplate.general.googleOptimize')}}</label>
                <textarea class="form-control" id="google_optimize_script" name="google_optimize_script" rows="2" style="resize: none;" placeholder="{{__('adminTemplate.general.googleOptimize')}}">{{$gen->google_optimize_script ?? ''}}</textarea>
            </div>
        </div>
        <div class="tab-pane fade" id="social" role="tabpanel" aria-labelledby="social-tab">
            @foreach(\App\Models\SocialNetwork::all() as $sn)
            <div class="input-group mb-3">
                <span class="input-group-text" id="basic-addon1"><img src="{{asset($sn->icon)}}" alt="{{$sn->name}}" title="{{$sn->name}}"></span>
                <input type="url" class="form-control" name="socialnetwork[{{$sn->id}}]" value="{{$sn->socialNetworkUrl()->where('general_id', '=', 1)->first()->url ?? ''}}" placeholder="{{$sn->name}}" aria-label="{{$sn->name}}">
            </div>
            @endforeach
        </div>
    </div>

    @if(!is_null(session('saved')))
        @include('utils.alertSuccess', ['message' => __('adminTemplate.general.okmessage')])
    @endif

    @include('utils.alertError')

    <div class="mb-3">
        <button type="submit" class="btn btn-primary"><i class="fa-regular fa-floppy-disk"></i> {{__('adminTemplate.form.btn.save')}}</button>
    </div>
</form>
@endsection