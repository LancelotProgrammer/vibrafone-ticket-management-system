@extends('layouts.master')

@section('title')
    Home
@endsection

@section('content')
    {{-- contact alert --}}
    @if (session('status-success'))
        <div class="alert alert-success">
            {{ session('status-success') }}
        </div>
    @endif
    @if (session('status-error'))
        <div class="alert alert-danger">
            {{ session('status-error') }}
        </div>
    @endif
    {{-- contact alert --}}

    <div class="container-fluid position-relative p-0">
        @include('components.carousel')
    </div>
    @include('components.facts')
    @include('components.about')
@endsection
