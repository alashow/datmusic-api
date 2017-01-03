@extends('layouts.error')

@section('title', 'Error')

@section('code', $exception->getStatusCode())