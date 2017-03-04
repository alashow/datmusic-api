@extends('layouts.error')

@section('title', 'Error')

@section('code', isset($status) ? $status : 500)