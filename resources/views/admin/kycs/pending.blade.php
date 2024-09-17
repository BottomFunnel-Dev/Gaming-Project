@extends('layouts.main')
@section('head')
    <title> KYC </title>
@endsection


@section('content')
    <!-- Begin Page Content -->
    <div class="container-fluid">

        <!-- Page Heading -->
        <h1 class="h3 mb-2 text-gray-800">KYC</h1>
        <!-- DataTales Example -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">PENDING KYC</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>User ID</th>
                                <th>Document Name</th>
                                <th>Document Number</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr>
                                <th>#</th>
                                <th>User ID</th>
                                <th>Document Name</th>
                                <th>Document Number</th>
                                <th>Action</th>
                            </tr>
                        </tfoot>
                        <tbody>

                            <?php $i = 1; ?>
                            @foreach ($pending as $row)
                                <tr>
                                    <td>{{ $i }}</td>
                                    <td>
                                        {{ $row->user_id }}
                                    </td>
                                    <td>
                                        @if ($row->DOCUMENT_NAME == 'UID')
                                            Aadhar Card
                                        @elseif($row->DOCUMENT_NAME == 'DL')
                                            Driving Licence
                                        @else
                                            Voter ID Card
                                        @endif
                                    </td>
                                    <td>
                                        {{ $row->DOCUMENT_NUMBER }}
                                    </td>


                                    <td>
                                        <a href="{{ url('admin/kyc-details/' . $row->user_id) }}"
                                            class="btn btn-info btn-sm btn-xs" title="View ">View</a>

                                    </td>
                                </tr>
                                <?php $i++; ?>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
    <!-- /.container-fluid -->
    <!-- Load jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.js" integrity="sha256-H+K7U5CnXl1h5ywQfKtSj8PCmoN9aaq30gDh27Xc0jk="
        crossorigin="anonymous"></script>


    <!-- Load DataTables -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.js"></script>

    <!-- Load DataTables Buttons -->
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>

@endsection
