@extends('templates.adminTemplate')

@section('page')
<div class="mt-3">
    @include('utils.breadcrumb', ['title' => __('adminTemplate.user.index.title')])

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>#</th>
                <th>{{__('adminTemplate.user.list.name')}}</th>
                <th>{{__('adminTemplate.user.list.email')}}</th>
                <th>{{__('adminTemplate.user.list.enabled')}}</th>
                <th>{{__('adminTemplate.user.list.action')}}</th>
            </tr>
        </thead>
        <tbody>
        @foreach ($users as $user)
            <tr>
                <td>{{$user->id}}</td>
                <td>{{$user->name}}</td>
                <td>{{$user->email}}</td>
                <td>{{!is_null($user->deleted_at) ? 'Sim' : 'Não'}}</td>
                <td>
                    <form class="d-flex justify-content-around" method="post" action="{{route('user.destroy', $user->id)}}">
                        @csrf
                        @method('DELETE')
                        <a href="{{route('user.edit', $user->id)}}" role="button" class="btn btn-outline-primary btn-sm"><i class="fa-sharp fa-solid fa-pen-to-square"></i></a>
                        @if(is_null($user->deleted_at))
                        <button type="submit" class="btn btn-outline-danger btn-sm"><i class="fa-solid fa-trash"></i></button>
                        @else
                        <button type="submit" class="btn btn-outline-success btn-sm"><i class="fa-solid fa-trash-arrow-up"></i></button>
                        @endif
                    </form>
                </td>
            </tr>
        @endforeach
        <tbody>
    </table>
</div>
@endsection