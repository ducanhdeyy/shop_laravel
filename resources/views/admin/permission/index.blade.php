@extends('admin.layout.main')

@section('content')
    <table class="align-middle mb-0 table table-borderless table-striped table-hover">
        <thead>
        <tr>
            <th class="text-center">ID</th>
            <th class="text-center">Name</th>
            <th class="text-center">Display Name</th>
            <th class="text-center">Action</th>
        </tr>
        </thead>
        <tbody>@foreach($permissions as $permission)
            @foreach($permission->getPermissionChild as $permissionChild)
                <tr>
                    <td class="text-center text-muted">{{$permissionChild->id}}</td>
                    <td class="text-center">{{$permissionChild->name}}</td>
                    <td class="text-center">{{$permissionChild->parent_id}}</td>
                    <td class="text-center">{{$permissionChild->key_code}}</td>
                </tr>

            @endforeach
        @endforeach
        </tbody>
    </table>
    <div class="my-3">
        {{$permissions->appends(request()->all())->links()}}
    </div>

@endsection
