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
                <td>
                    <form class="d-flex justify-content-around" method="post" action="{{route('category.destroy', $cat->id)}}">
                        @csrf
                        @method('DELETE')
                        <a href="{{route('category.edit', $cat->id)}}" role="button" class="btn btn-outline-primary btn-sm"><i class="fa-sharp fa-solid fa-pen-to-square"></i></a>
                        @if(is_null($cat->deleted_at))
                        <button type="submit" class="btn btn-outline-danger btn-sm"><i class="fa-solid fa-trash"></i></button>
                        @else
                        <button type="submit" class="btn btn-outline-success btn-sm"><i class="fa-solid fa-trash-arrow-up"></i></button>
                        @endif
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection