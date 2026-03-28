@extends('tenant.layouts.app')

@section('title', optional($article)->title ?? 'Статья')

@section('content')
    <article class="article-detail">
        <p>Страница статьи</p>
    </article>
@endsection
