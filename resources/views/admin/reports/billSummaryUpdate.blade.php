@extends('layouts.app')

@section('title', 'Bill Summary Update - EasyLogics')

@section('content')
{{-- Admin > Reports > Bill Summary Update: the Bill Summary Monitor (month-wise bills, reseller-wise, Correct / Wrong
     check, Reconcile) - moved here from the Admin dashboard. All its data calls are the admin.billMonitor.* routes. --}}
@include('admin._bill_monitor')
@endsection
