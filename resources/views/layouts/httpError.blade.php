@extends('layouts.error')

@section('title', 'Error')

@section('code', !empty($exception->getStatusCode()) ? $exception->getStatusCode() : 500)