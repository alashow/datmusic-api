@extends('layouts.error')

@section('title', 'Error')

@section('code', (isset($exception) && !empty($exception->getStatusCode())) ? $exception->getStatusCode() : 500)