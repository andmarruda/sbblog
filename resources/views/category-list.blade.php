@extends('templates.adminTemplate')

@section('page')
<div class="mt-3">
    @include('utils.breadcrumb', ['title' => __('adminTemplate.category.index.title')])

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>#</th>
                <th>{{__('adminTemplate.category.list.name')}}</th>
                <th>{{__('adminTemplate.category.list.disabled')}}</th>
                <th>{{__('adminTemplate.category.list.action')}}</th>
            </tr>
        </thead>
        <tbody>
        @foreach ($category as $cat)
            <tr>
                <td>{{$cat->id}}</td>
                <td>{{$cat->category}}</td>
                <td>{{!is_null($cat->deleted_at) ? 'Sim' : 'Não'}}</td>
                <td></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection