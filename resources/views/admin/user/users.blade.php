@extends('layouts.main')
@section('title', 'Users')
@section('content')
    @push('head')
        <link rel="stylesheet" href="{{ asset('plugins/DataTables/datatables.min.css') }}">
    @endpush

    <div class="container-fluid">
        <div class="page-header">
            <div class="row align-items-end">
                <div class="col-lg-8 col-md-12 col-sm-12 mb-3">
                    <div class="page-header-title">
                        <i class="ik ik-users bg-blue"></i>
                        <div class="d-inline">
                            <h5>{{ __('Users') }}</h5>
                            <span>{{ __('List of users') }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-12 col-sm-12 mb-3 text-right">
                    <a href="javascript:void(0)" onclick="exportData()" class="btn btn-outline-primary">
                        <i class="ik ik-download"></i> Download in Excel
                    </a>
                </div>
            </div>
        </div>

        <div class="row mb-2">
            <div class="col-12">
                <form class="form-inline" style="flex-wrap: wrap;">
                    <a class="btn btn-info mb-2 mr-2" href="{{ route('add-user') }}">Add User</a>
                    <input type="text" class="form-control mb-2 mr-sm-2" name="search" placeholder="Search here"
                        value="{{ $search ? $search : '' }}" />
                    <input type="text" class="form-control mb-2 mr-sm-2" name="referral"
                        placeholder="Referral Search" value="{{ $referral ? $referral : '' }}" />
                    <button type="submit" class="btn btn-info mb-2">Search</button>
                </form>
            </div>
            <div class="col-12">
                <h3 class="mt-2">{{ __('Users') }}</h3>
            </div>
        </div>

        <div class="row">
            @include('include.message')
            <div class="col-md-12">
                <div class="card p-3">
                    <div class="card-body table-responsive p-0">
                        <table id="user_table" class="table table-bordered table-hover table-striped">
                            <thead>
                                <tr>
                                    <th>{{ __('#ID') }}</th>
                                    <th>{{ __('Name') }}</th>
                                    <th>{{ __('Username') }}</th>
                                    <th>{{ __('Mobile') }}</th>
                                    <th>{{ __('Wallet Balance') }}</th>
                                    <th>{{ __('Referral') }}</th>
                                    <th>{{ __('Used Referral') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Added at') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($users as $key => $val)
                                    <tr>
                                        <td>{{ $val->id }}</td>
                                        <td>{{ $val->name }}</td>
                                        <td>{{ $val->username }}</td>
                                        <td>{{ $val->mobile }}</td>
                                        <td>{{ @$val->wallet }}</td>
                                        <td>{{ @$val->setting->referral }}</td>
                                        <td>{{ @$val->setting->used_referral }}</td>
                                        <td>
                                            @if (isset($val->setting->status))
                                                @if ($val->setting->status == 1)
                                                    Active
                                                @elseif($val->setting->status == 0)
                                                    Inactive
                                                @elseif($val->setting->status == 2)
                                                    Block
                                                @endif
                                            @else
                                                Inactive
                                            @endif
                                        </td>
                                        <td>{{ $val->created_at }}</td>
                                        <td class="d-flex flex-wrap">
                                            @can('manage_users')
                                                <a title="View Statement" href="{{ url('admin/user/statement/' . $val->id) }}"
                                                    class="btn-action mr-2 mb-2">
                                                    <i class="ik ik-eye f-16 text-blue"></i>
                                                </a>
                                                <a title="Update user record" href="{{ url('admin/user/edit/' . $val->id) }}"
                                                    class="btn-action mr-2 mb-2">
                                                    <i class="ik ik-edit-2 f-16 text-green"></i>
                                                </a>
                                                @php
                                                    $msg = "'Are you sure want to take this action?'";
                                                    if (
                                                        !isset($val->setting->status) ||
                                                        (isset($val->setting->status) && $val->setting->status == 1)
                                                    ) {
                                                        $sHtml =
                                                            '<a title="Make User Inactive" href="' .
                                                            url('admin/user/status/0/' . $val->id) .
                                                            '" class="btn-action mr-2 mb-2"><i class="ik ik-x f-16 text-yellow"></i></a>';
                                                    } else {
                                                        $sHtml =
                                                            '<a title="Make User Active" href="' .
                                                            url('admin/user/status/1/' . $val->id) .
                                                            '" class="btn-action mr-2 mb-2"><i class="ik ik-check f-16 text-blue"></i></a>';
                                                    }
                                                    echo $sHtml;
                                                @endphp
                                                <a title="Update wallet balance"
                                                    href="{{ url('admin/user/wallet/' . $val->id) }}" class="btn-action mr-2 mb-2">
                                                    <i class="ik ik-dollar-sign f-16 text-red"></i>
                                                </a>
                                            @endcan
                                            @can('manage_user')
                                                <a title="View Statement" href="{{ url('admin/user/statement/' . $val->id) }}"
                                                    class="btn-action mr-2 mb-2">
                                                    <i class="ik ik-eye f-16 text-blue"></i>
                                                </a>
                                            @endcan
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        {{ $users->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Responsive button layout for mobile devices */
        .btn-action {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            width: 48%; /* Adjusts buttons to fit two per row on small screens */
        }
    </style>

    <script src="https://unpkg.com/xlsx@0.15.1/dist/xlsx.full.min.js"></script>
    <script>
        function exportData(type, fn, dl) {
            var elt = document.getElementById('user_table');
            var wb = XLSX.utils.table_to_book(elt, { sheet: "sheet1" });
            return dl ? XLSX.write(wb, { bookType: type, bookSST: true, type: 'base64' }) : XLSX.writeFile(wb, fn || ('users.' + (type || 'xlsx')));
        }
    </script>

    @push('script')
        <script src="{{ asset('js/custom.js') }}"></script>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="{{ asset('plugins/select2/dist/js/select2.min.js') }}"></script>
        <script src="{{ asset('plugins/DataTables/datatables.min.js') }}"></script>
    @endpush
@endsection
