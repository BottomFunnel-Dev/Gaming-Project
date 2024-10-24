@extends('layouts.main') 
@section('title', 'Organisers')
@section('content')      
    <div class="container-fluid">
    	<div class="page-header">
            <div class="row align-items-end">
                <div class="col-lg-8">
                    <div class="page-header-title">
                        <i class="ik ik-film bg-blue"></i>
                        <div class="d-inline">
                            <h5>{{ __('Organisers')}}</h5>
                            <span>{{ __('List of all organisers')}}</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <nav class="breadcrumb-container" aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="{{route('dashboard')}}"><i class="ik ik-home"></i></a>
                            </li>
                            <li class="breadcrumb-item">
                                <a href="#">{{ __('Organisers')}}</a>
                            </li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
        <div class="row">
            <!-- start message area-->
            @include('include.message')
            <!-- end message area-->
            <div class="col-md-12">
            <div class="card">
                    <div class="card-header ">
                        <h3>{{ __('All Organisers')}}</h3>
                    </div>
                    <div class="card-body p-0 table-border-style">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>{{ __('ID')}}</th>
                                        <th>{{ __('Title')}}</th>
                                        <th>{{ __('Logo')}}</th>
                                        <th>{{ __('Status')}}</th>
                                        <th>{{ __('Created at')}}</th>
                                        <th>{{ __('Action')}}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($organisers as $key => $val)
                                        <tr>
                                            <th scope="row">{{$val->id}}</th>
                                            <td>{{$val->title}}</td>
                                            <td>{{$val->logo}}</td>
                                            <td>{{ $val->status ? 'Active' : 'Inactive' }}</td>
                                            <td>{{$val->created_at}}</td>
                                            <td>
                                            <a href=" {{ url('admin/organiser/edit/'.$val->id) }}" ><i class="ik ik-edit-2 f-16 mr-15 text-green" title="Edit Organiser"></i></a>
                                                @can('manage_organiser')
                                                    @php
                                                        $msg    =   "'Are you sure want to take this action?'";
                                                        if($val->status)
                                                            $sHtml  =   '<a title="Make User Inactive" onclick="return confirm('.$msg.')" href="'.url('admin/organiser/status/0/'.$val->id).'"><i class="ik ik-x f-16 ml-10 text-yellow"></i></a>';
                                                        else
                                                            $sHtml  =   '<a title="Make User Active" onclick="return confirm('.$msg.')" href="'.url('admin/organiser/status/1/'.$val->id).'"><i class="ik ik-check f-16 ml-10 text-blue"></i></a>';
                                                        echo $sHtml;
                                                    @endphp
                                                @endcan
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <div class="d-flex justify-content-center">
                                {{ $organisers->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
