@extends('templates.adminTemplate')

@section('page')
<form method="post" action="{{isset($cat->id) ? route('category.update', $cat->id) : route('category.store')}}" style="margin-top:30px;" autocomplete="off">
    @csrf
    @isset($cat->id)
    @method('PUT')
    @endisset

    @include('utils.breadcrumb', ['title' => __('adminTemplate.category.title')])

    <div class="mb-3">
        <label for="categoryName" class="form-label">{{__('adminTemplate.category.form.label')}}</label>
        <input type="text" maxlength="50" class="form-control" id="categoryName" name="categoryName" placeholder="{{__('adminTemplate.category.form.label')}}" required value="{{$cat->category ?? ''}}">
    </div>

    @include('utils.comboActive', ['active' => $cat->active ?? NULL])

    @if(!is_null(session('saved')))
        @include('utils.alertSuccess', ['message' => __('adminTemplate.category.okmessage')])
    @endif

    @include('utils.alertError')

    <div class="mb-3">
        <button type="submit" class="btn btn-primary"><i class="fa-regular fa-floppy-disk"></i> {{__('adminTemplate.form.btn.save')}}</button>
        <a href="{{route('category.index')}}" role="button" class="btn btn-outline-primary"><i class="fa fa-search"></i> {{__('adminTemplate.form.btn.search')}}</a>
    </div>
</form>

@endsection